<?php

declare(strict_types=1);

namespace Leko\Bitrix24;

use Bitrix24\SDK\Core\Credentials\ApplicationProfile;
use Bitrix24\SDK\Core\Credentials\AuthToken;
use Bitrix24\SDK\Core\Credentials\Scope;
use Bitrix24\SDK\Core\Response\DTO\RenewedAuthToken;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Http;
use Leko\Bitrix24\Exceptions\OAuthException;
use Leko\Bitrix24\Models\Bitrix24Token;
use Leko\Bitrix24\Repositories\Bitrix24Token\Bitrix24TokenRepositoryInterface;
use Leko\Bitrix24\Support\ConnectionConfig;
use Leko\Bitrix24\Support\Domain;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

/**
 * Управление OAuth-токенами Bitrix24.
 */
readonly class TokenManager
{
    public function __construct(
        private Bitrix24TokenRepositoryInterface $tokenRepository,
        private CacheRepository $cache
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getToken(?int $userId = null, string $connection = 'main'): ?Bitrix24Token
    {
        $cacheKey = $this->getCacheKey($userId, $connection);
        $token = $this->cache->get($cacheKey);

        if ($token instanceof Bitrix24Token && !$token->isExpired() && !$token->isExpiringSoon()) {
            return $token;
        }

        $token = $this->tokenRepository->findActiveToken($userId, $connection);

        if ($token && ($token->isExpired() || $token->isExpiringSoon())) {
            try {
                $token = $this->refreshToken($token);
            } catch (Throwable) {
                return null;
            }
        }

        if ($token) {
            $this->cacheToken($token);
        }

        return $token;
    }

    /**
     * @param array<string, mixed> $tokenData
     */
    public function storeToken(array $tokenData, ?int $userId = null, string $connection = 'main'): Bitrix24Token
    {
        $token = $this->tokenRepository->upsert([
            'connection' => $connection,
            'user_id' => $userId,
            'domain' => $this->resolvePortalDomain($tokenData),
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'expires_in' => $tokenData['expires_in'] ?? 3600,
            'expires_at' => $this->resolveExpiresAt($tokenData),
            'scope' => $tokenData['scope'] ?? null,
            'metadata' => $tokenData['metadata'] ?? null,
            'is_active' => true,
        ]);

        $this->cacheToken($token);

        return $token;
    }

    public function persistRenewedToken(RenewedAuthToken $renewed, ?int $userId = null, string $connection = 'main'): Bitrix24Token
    {
        $authToken = $renewed->authToken;
        $refreshToken = $authToken->refreshToken
            ?? $this->tokenRepository->findActiveToken($userId, $connection)?->refresh_token;

        if ($refreshToken === null || $refreshToken === '') {
            throw new OAuthException('Не удалось сохранить обновлённый токен: отсутствует refresh_token.');
        }

        return $this->storeToken([
            'domain' => $renewed->domain,
            'client_endpoint' => $renewed->clientEndpoint,
            'access_token' => $authToken->accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $authToken->expiresIn ?? 3600,
            'expires' => $authToken->expires,
            'metadata' => [
                'member_id' => $renewed->memberId,
                'client_endpoint' => $renewed->clientEndpoint,
                'server_endpoint' => $renewed->serverEndpoint,
            ],
        ], $userId, $connection);
    }

    /**
     * Обменять authorization code на токены и подготовить данные для сохранения.
     *
     * @return array<string, mixed>
     */
    public function exchangeAuthorizationCode(string $code, string $connection = 'main'): array
    {
        $config = ConnectionConfig::load($connection);
        $data = $this->requestOAuthToken($config, [
            'grant_type' => 'authorization_code',
            'client_id' => $config->clientId(),
            'client_secret' => $config->clientSecret(),
            'code' => $code,
            'redirect_uri' => $config->redirectUri(),
        ]);

        return [
            'domain' => $this->resolvePortalDomain($data, $config->domain()),
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_in' => $data['expires_in'] ?? 3600,
            'expires' => $data['expires'] ?? null,
            'scope' => explode(',', (string) ($data['scope'] ?? $config->scope())),
            'client_endpoint' => $data['client_endpoint'] ?? null,
            'metadata' => [
                'member_id' => $data['member_id'] ?? null,
                'client_endpoint' => $data['client_endpoint'] ?? null,
                'server_endpoint' => $data['server_endpoint'] ?? null,
            ],
        ];
    }

    public function refreshToken(Bitrix24Token $token): Bitrix24Token
    {
        try {
            $config = ConnectionConfig::load($token->connection);
            $data = $this->requestOAuthToken($config, [
                'grant_type' => 'refresh_token',
                'client_id' => $config->clientId(),
                'client_secret' => $config->clientSecret(),
                'refresh_token' => $token->refresh_token,
            ]);

            $this->tokenRepository->update($token->id, [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_in' => $data['expires_in'] ?? 3600,
                'expires_at' => $this->resolveExpiresAt($data),
                'domain' => $this->resolvePortalDomain($data, $token->domain),
                'is_active' => true,
            ]);

            $token->refresh();
            $this->cacheToken($token);

            return $token;
        } catch (Throwable $e) {
            $this->tokenRepository->deactivate($token->id);
            $this->invalidateCache($token);

            throw $e;
        }
    }

    public function revokeToken(int $tokenId): bool
    {
        $token = $this->tokenRepository->find($tokenId);

        if (!$token) {
            return false;
        }

        $result = $this->tokenRepository->deactivate($tokenId);

        if ($result) {
            $this->invalidateCache($token);
        }

        return $result;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getAuthToken(?int $userId = null, string $connection = 'main'): ?AuthToken
    {
        $token = $this->getToken($userId, $connection);

        return $token ? $this->toAuthToken($token) : null;
    }

    public function toAuthToken(Bitrix24Token $token): AuthToken
    {
        return new AuthToken(
            $token->access_token,
            $token->refresh_token,
            $token->expires_at?->getTimestamp() ?? (time() + (int) $token->expires_in),
            $token->expires_in
        );
    }

    public function getApplicationProfile(string $connection = 'main'): ApplicationProfile
    {
        $config = ConnectionConfig::load($connection);

        return new ApplicationProfile(
            $config->clientId(),
            $config->clientSecret(),
            Scope::initFromString($config->scope())
        );
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function requestOAuthToken(ConnectionConfig $config, array $params): array
    {
        $attempts = max(1, (int) config('bitrix24.api.retry_attempts', 3));
        $delay = (int) config('bitrix24.api.retry_delay', 1000);

        $response = retry($attempts, function () use ($config, $params) {
            $response = Http::asForm()
                ->timeout((int) config('bitrix24.api.timeout', 30))
                ->post($config->oauthTokenUrl(), $params);

            if ($response->serverError()) {
                throw new OAuthException('OAuth-сервер недоступен: ' . $response->body());
            }

            return $response;
        }, $delay);

        if ($response->failed()) {
            throw new OAuthException('Не удалось выполнить OAuth-запрос: ' . $response->body());
        }

        $data = $response->json();

        if (!is_array($data) || !isset($data['access_token'], $data['refresh_token'])) {
            throw new OAuthException('Ответ Bitrix24 не содержит токены: ' . $response->body());
        }

        return $data;
    }

    private function cacheToken(Bitrix24Token $token): void
    {
        $this->cache->put(
            $this->getCacheKey($token->user_id, $token->connection),
            $token,
            (int) config('bitrix24.cache.ttl', 3600)
        );
    }

    private function invalidateCache(Bitrix24Token $token): void
    {
        $this->cache->forget($this->getCacheKey($token->user_id, $token->connection));
    }

    private function getCacheKey(?int $userId, string $connection): string
    {
        $prefix = config('bitrix24.cache.prefix', 'bitrix24_tokens');

        return "{$prefix}:{$connection}:" . ($userId ?? 'guest');
    }

    /**
     * Portal host for REST calls. Bitrix24's oauth/token JSON sets `domain`
     * to the authorization server (oauth.bitrix.info); the portal is in
     * `client_endpoint`.
     *
     * @param array<string, mixed> $tokenData
     */
    private function resolvePortalDomain(array $tokenData, ?string $fallback = null): string
    {
        $endpoint = $tokenData['client_endpoint']
            ?? (is_array($tokenData['metadata'] ?? null) ? ($tokenData['metadata']['client_endpoint'] ?? null) : null);

        $fromEndpoint = Domain::fromClientEndpoint(is_string($endpoint) ? $endpoint : null);
        if ($fromEndpoint !== null) {
            return $fromEndpoint;
        }

        $domain = Domain::normalize((string) ($tokenData['domain'] ?? ''));
        if ($domain !== '' && !Domain::isAuthorizationServer($domain)) {
            return $domain;
        }

        return Domain::normalize((string) ($fallback ?? ''));
    }

    /**
     * @param array<string, mixed> $tokenData
     */
    private function resolveExpiresAt(array $tokenData): ?Carbon
    {
        if (isset($tokenData['expires']) && is_numeric($tokenData['expires'])) {
            return Carbon::createFromTimestamp((int) $tokenData['expires']);
        }

        if (isset($tokenData['expires_in'])) {
            return Carbon::now()->addSeconds((int) $tokenData['expires_in']);
        }

        return null;
    }
}
