<?php

declare(strict_types=1);

namespace Leko\Bitrix24;

use Bitrix24\SDK\Core\Credentials\ApplicationProfile;
use Bitrix24\SDK\Core\Credentials\AuthToken;
use Bitrix24\SDK\Core\Credentials\DefaultOAuthServerUrl;
use Bitrix24\SDK\Core\Credentials\Scope;
use Bitrix24\SDK\Core\Response\DTO\RenewedAuthToken;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Http;
use Leko\Bitrix24\Models\Bitrix24Token;
use Leko\Bitrix24\Repositories\Bitrix24Token\Bitrix24TokenRepositoryInterface;
use Psr\SimpleCache\InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Сервис управления токенами
 *
 * Управляет OAuth токенами для интеграции с Bitrix24.
 */
readonly class TokenManager
{
    /**
     * Создать новый экземпляр TokenManager.
     *
     * @param Bitrix24TokenRepositoryInterface $tokenRepository Репозиторий токенов
     * @param CacheRepository $cache Репозиторий кеша
     */
    public function __construct(
        private Bitrix24TokenRepositoryInterface $tokenRepository,
        private CacheRepository $cache
    ) {
    }

    /**
     * Получить валидный токен для пользователя и подключения.
     *
     * @param int|null $userId ID пользователя
     * @param string $connection Название подключения
     * @return Bitrix24Token|null
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
     * Сохранить новый токен.
     *
     * @param array $tokenData Данные токена
     * @param int|null $userId ID пользователя
     * @param string $connection Название подключения
     * @return Bitrix24Token
     */
    public function storeToken(array $tokenData, ?int $userId = null, string $connection = 'main'): Bitrix24Token
    {
        $expiresAt = $this->resolveExpiresAt($tokenData);

        $data = [
            'connection' => $connection,
            'user_id' => $userId,
            'domain' => $tokenData['domain'],
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'expires_in' => $tokenData['expires_in'] ?? 3600,
            'expires_at' => $expiresAt,
            'scope' => $tokenData['scope'] ?? null,
            'metadata' => $tokenData['metadata'] ?? null,
            'is_active' => true,
        ];

        $token = $this->tokenRepository->upsert($data);

        $this->cacheToken($token);

        return $token;
    }

    /**
     * Сохранить токен, обновлённый SDK.
     *
     * @param RenewedAuthToken $renewed Обновлённый токен SDK
     * @param int|null $userId ID пользователя
     * @param string $connection Название подключения
     * @return Bitrix24Token
     */
    public function persistRenewedToken(RenewedAuthToken $renewed, ?int $userId = null, string $connection = 'main'): Bitrix24Token
    {
        $authToken = $renewed->authToken;
        $refreshToken = $authToken->refreshToken
            ?? $this->tokenRepository->findActiveToken($userId, $connection)?->refresh_token;

        if ($refreshToken === null || $refreshToken === '') {
            throw new RuntimeException('Не удалось сохранить обновлённый токен: отсутствует refresh_token.');
        }

        return $this->storeToken([
            'domain' => $renewed->domain,
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
     * Обновить access token.
     *
     * @param Bitrix24Token $token Токен для обновления
     * @return Bitrix24Token
     */
    public function refreshToken(Bitrix24Token $token): Bitrix24Token
    {
        try {
            $config = config("bitrix24.connections.{$token->connection}", []);
            $oauthServer = rtrim((string) ($config['oauth_server'] ?? DefaultOAuthServerUrl::default()), '/');

            $response = Http::asForm()->timeout(config('bitrix24.api.timeout', 30))->post(
                $oauthServer . '/oauth/token/',
                [
                    'grant_type' => 'refresh_token',
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'refresh_token' => $token->refresh_token,
                ]
            );

            if ($response->failed()) {
                throw new RuntimeException('Не удалось обновить токен Bitrix24: ' . $response->body());
            }

            $data = $response->json();

            if (!isset($data['access_token'], $data['refresh_token'])) {
                throw new RuntimeException('Ответ Bitrix24 не содержит токены.');
            }

            $this->tokenRepository->update($token->id, [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_in' => $data['expires_in'] ?? 3600,
                'expires_at' => $this->resolveExpiresAt($data),
                'domain' => $data['domain'] ?? $token->domain,
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

    /**
     * Отозвать токен.
     *
     * @param int $tokenId ID токена
     * @return bool
     */
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
     * Получить AuthToken для SDK.
     *
     * @param int|null $userId ID пользователя
     * @param string $connection Название подключения
     * @return AuthToken|null
     * @throws InvalidArgumentException
     */
    public function getAuthToken(?int $userId = null, string $connection = 'main'): ?AuthToken
    {
        $token = $this->getToken($userId, $connection);

        if (!$token) {
            return null;
        }

        return $this->toAuthToken($token);
    }

    /**
     * Собрать AuthToken SDK из модели.
     *
     * @param Bitrix24Token $token Модель токена
     * @return AuthToken
     */
    public function toAuthToken(Bitrix24Token $token): AuthToken
    {
        return new AuthToken(
            $token->access_token,
            $token->refresh_token,
            $token->expires_at?->getTimestamp() ?? (time() + (int) $token->expires_in),
            $token->expires_in
        );
    }

    /**
     * Получить профиль приложения для SDK.
     *
     * @param string $connection Название подключения
     * @return ApplicationProfile
     */
    public function getApplicationProfile(string $connection = 'main'): ApplicationProfile
    {
        $config = config("bitrix24.connections.{$connection}", []);

        return new ApplicationProfile(
            (string) $config['client_id'],
            (string) $config['client_secret'],
            Scope::initFromString((string) ($config['scope'] ?? ''))
        );
    }

    /**
     * Кешировать токен.
     *
     * @param Bitrix24Token $token Токен для кеширования
     */
    private function cacheToken(Bitrix24Token $token): void
    {
        $cacheKey = $this->getCacheKey($token->user_id, $token->connection);
        $ttl = config('bitrix24.cache.ttl', 3600);

        $this->cache->put($cacheKey, $token, $ttl);
    }

    /**
     * Инвалидировать кеш токена.
     *
     * @param Bitrix24Token $token Токен для удаления из кеша
     */
    private function invalidateCache(Bitrix24Token $token): void
    {
        $cacheKey = $this->getCacheKey($token->user_id, $token->connection);
        $this->cache->forget($cacheKey);
    }

    /**
     * Получить ключ кеша для токена.
     *
     * @param int|null $userId ID пользователя
     * @param string $connection Название подключения
     * @return string
     */
    private function getCacheKey(?int $userId, string $connection): string
    {
        $prefix = config('bitrix24.cache.prefix', 'bitrix24_tokens');
        return "{$prefix}:{$connection}:" . ($userId ?? 'guest');
    }

    /**
     * Вычислить дату истечения токена.
     *
     * @param array $tokenData Данные ответа OAuth
     * @return Carbon|null
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
