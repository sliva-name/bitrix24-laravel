<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Repositories\Bitrix24Token;

use Leko\Bitrix24\Models\Bitrix24Token;
use Illuminate\Support\Collection;

/**
 * Репозиторий токенов Bitrix24
 *
 * Обрабатывает все операции с базой данных, связанные с токенами Bitrix24.
 */
class Bitrix24TokenRepository implements Bitrix24TokenRepositoryInterface
{
    /**
     * Найти токен по ID.
     *
     * @param int $id Идентификатор токена
     * @return Bitrix24Token|null
     */
    public function find(int $id): ?Bitrix24Token
    {
        return Bitrix24Token::find($id);
    }

    /**
     * Найти валидный токен для пользователя и подключения.
     *
     * @param int|null $userId Идентификатор пользователя
     * @param string $connection Название подключения
     * @return Bitrix24Token|null
     */
    public function findValidToken(?int $userId, string $connection = 'main'): ?Bitrix24Token
    {
        return Bitrix24Token::query()
            ->valid()
            ->forConnection($connection)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Найти токен по домену и подключению.
     *
     * @param string $domain Домен Bitrix24
     * @param string $connection Название подключения
     * @return Bitrix24Token|null
     */
    public function findByDomain(string $domain, string $connection = 'main'): ?Bitrix24Token
    {
        return Bitrix24Token::query()
            ->forConnection($connection)
            ->forDomain($domain)
            ->valid()
            ->first();
    }

    /**
     * Создать или обновить токен.
     *
     * @param array $data Данные токена
     * @return Bitrix24Token
     */
    public function upsert(array $data): Bitrix24Token
    {
        return Bitrix24Token::updateOrCreate(
            [
                'connection' => $data['connection'] ?? 'main',
                'user_id' => $data['user_id'] ?? null,
                'domain' => $data['domain'],
            ],
            [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_in' => $data['expires_in'],
                'expires_at' => $data['expires_at'],
                'scope' => $data['scope'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]
        );
    }

    /**
     * Обновить токен.
     *
     * @param int $id Идентификатор токена
     * @param array $data Данные для обновления
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $token = $this->find($id);

        if (!$token) {
            return false;
        }

        return $token->update($data);
    }

    /**
     * Удалить токен.
     *
     * @param int $id Идентификатор токена
     * @return bool
     */
    public function delete(int $id): bool
    {
        $token = $this->find($id);

        if (!$token) {
            return false;
        }

        return $token->delete();
    }

    /**
     * Получить все токены для пользователя.
     *
     * @param int $userId Идентификатор пользователя
     * @return Collection
     */
    public function getAllForUser(int $userId): Collection
    {
        return Bitrix24Token::query()
            ->where('user_id', $userId)
            ->get();
    }

    /**
     * Получить все истекшие токены.
     *
     * @return Collection
     */
    public function getExpiredTokens(): Collection
    {
        return Bitrix24Token::query()
            ->active()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();
    }

    /**
     * Получить все токены, срок действия которых истекает.
     *
     * @return Collection
     */
    public function getExpiringSoonTokens(): Collection
    {
        return Bitrix24Token::query()
            ->active()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->addMinutes(5))
            ->where('expires_at', '>', now())
            ->get();
    }

    /**
     * Деактивировать токен.
     *
     * @param int $id Идентификатор токена
     * @return bool
     */
    public function deactivate(int $id): bool
    {
        return $this->update($id, ['is_active' => false]);
    }

    /**
     * Активировать токен.
     *
     * @param int $id Идентификатор токена
     * @return bool
     */
    public function activate(int $id): bool
    {
        return $this->update($id, ['is_active' => true]);
    }
}
