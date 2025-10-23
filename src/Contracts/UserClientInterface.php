<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Contracts;

/**
 * Интерфейс клиента пользователей
 */
interface UserClientInterface extends ClientInterface
{
    /**
     * Получить список пользователей.
     *
     * @param array $filter Фильтры выборки
     * @return array
     */
    public function list(array $filter = []): array;

    /**
     * Получить текущего пользователя.
     *
     * @return array|null
     */
    public function current(): ?array;

    /**
     * Получить пользователя по ID.
     *
     * @param int $id ID пользователя
     * @return array|null
     */
    public function get(int $id): ?array;

    /**
     * Поиск пользователей.
     *
     * @param string $query Поисковый запрос
     * @return array
     */
    public function search(string $query): array;
}

