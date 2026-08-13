<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Contracts;

use Bitrix24\SDK\Services\User\Result\UserItemResult;

/**
 * Интерфейс клиента пользователей
 */
interface UserClientInterface extends ClientInterface
{
    /**
     * Получить список пользователей.
     *
     * @param array $filter Фильтры выборки
     * @return UserItemResult[]
     */
    public function list(array $filter = []): array;

    /**
     * Получить текущего пользователя.
     *
     * @return UserItemResult
     */
    public function current(): UserItemResult;

    /**
     * Получить пользователя по ID.
     *
     * @param int $id ID пользователя
     * @return UserItemResult|null
     */
    public function get(int $id): ?UserItemResult;

    /**
     * Поиск пользователей.
     *
     * @param string $query Поисковый запрос
     * @return UserItemResult[]
     */
    public function search(string $query): array;
}
