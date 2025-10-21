<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Repositories\Bitrix24Token;

use Leko\Bitrix24\Models\Bitrix24Token;
use Illuminate\Support\Collection;

/**
 * Интерфейс репозитория токенов Bitrix24
 *
 * Определяет методы для работы с токенами Bitrix24.
 */
interface Bitrix24TokenRepositoryInterface
{
    /**
     * Найти токен по ID.
     *
     * @param int $id Идентификатор токена
     * @return Bitrix24Token|null
     */
    public function find(int $id): ?Bitrix24Token;

    /**
     * Найти валидный токен для пользователя и подключения.
     *
     * @param int|null $userId Идентификатор пользователя
     * @param string $connection Название подключения
     * @return Bitrix24Token|null
     */
    public function findValidToken(?int $userId, string $connection = 'main'): ?Bitrix24Token;

    /**
     * Найти токен по домену и подключению.
     *
     * @param string $domain Домен Bitrix24
     * @param string $connection Название подключения
     * @return Bitrix24Token|null
     */
    public function findByDomain(string $domain, string $connection = 'main'): ?Bitrix24Token;

    /**
     * Создать или обновить токен.
     *
     * @param array $data Данные токена
     * @return Bitrix24Token
     */
    public function upsert(array $data): Bitrix24Token;

    /**
     * Обновить токен.
     *
     * @param int $id Идентификатор токена
     * @param array $data Данные для обновления
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Удалить токен.
     *
     * @param int $id Идентификатор токена
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Получить все токены для пользователя.
     *
     * @param int $userId Идентификатор пользователя
     * @return Collection
     */
    public function getAllForUser(int $userId): Collection;

    /**
     * Получить все истекшие токены.
     *
     * @return Collection
     */
    public function getExpiredTokens(): Collection;

    /**
     * Получить все токены, срок действия которых истекает.
     *
     * @return Collection
     */
    public function getExpiringSoonTokens(): Collection;

    /**
     * Деактивировать токен.
     *
     * @param int $id Идентификатор токена
     * @return bool
     */
    public function deactivate(int $id): bool;

    /**
     * Активировать токен.
     *
     * @param int $id Идентификатор токена
     * @return bool
     */
    public function activate(int $id): bool;
}
