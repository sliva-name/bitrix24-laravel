<?php

declare(strict_types=1);

namespace Leko\Bitrix24;

use Leko\Bitrix24\Models\Bitrix24Token;
use Leko\Bitrix24\Repositories\Bitrix24Token\Bitrix24TokenRepositoryInterface;
use Bitrix24\SDK\Core\Credentials\AccessToken;
use Bitrix24\SDK\Core\Credentials\Credentials;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\SimpleCache\InvalidArgumentException;

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
        private CacheRepository                  $cache
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

        if ($token instanceof Bitrix24Token && !$token->isExpired()) {
            return $token;
        }

        $token = $this->tokenRepository->findValidToken($userId, $connection);

        if ($token) {
            if ($token->isExpiringSoon()) {
                $token = $this->refreshToken($token);
            }

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
        $expiresAt = isset($tokenData['expires_in'])
            ? Carbon::now()->addSeconds($tokenData['expires_in'])
            : null;

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
     * Обновить access token.
     *
     * @param Bitrix24Token $token Токен для обновления
     * @return Bitrix24Token
     */
    public function refreshToken(Bitrix24Token $token): Bitrix24Token
    {
        try {
            $config = config("bitrix24.connections.{$token->connection}");

            $credentials = Credentials::createFromOAuth(
                new AccessToken(
                    $token->access_token,
                    $token->refresh_token,
                    $token->expires_in
                ),
                $config['client_id'],
                $config['client_secret'],
                $token->domain
            );

            $expiresAt = Carbon::now()->addSeconds($token->expires_in);

            $this->tokenRepository->update($token->id, [
                'expires_at' => $expiresAt,
            ]);

            $token->refresh();

            $this->cacheToken($token);

            return $token;
        } catch (\Exception $e) {
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
     * Получить credentials для SDK.
     *
     * @param int|null $userId ID пользователя
     * @param string $connection Название подключения
     * @return Credentials|null
     * @throws InvalidArgumentException|\Bitrix24\SDK\Core\Exceptions\InvalidArgumentException
     */
    public function getCredentials(?int $userId = null, string $connection = 'main'): ?Credentials
    {
        $token = $this->getToken($userId, $connection);

        if (!$token) {
            return null;
        }

        $config = config("bitrix24.connections.{$connection}");

        return Credentials::createFromOAuth(
            new AccessToken(
                $token->access_token,
                $token->refresh_token,
                $token->expires_in
            ),
            $config['client_id'],
            $config['client_secret'],
            $token->domain
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
}

