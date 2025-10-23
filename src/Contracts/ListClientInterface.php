<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Contracts;

/**
 * Интерфейс клиента пользовательских списков
 */
interface ListClientInterface extends ClientInterface
{
    /**
     * Получить список элементов пользовательского списка.
     *
     * @param int $listId ID списка
     * @param array $filter Фильтры выборки
     * @param array $select Список полей для выборки
     * @param array $order Сортировка результатов
     * @param int $start Смещение для пагинации
     * @return array
     */
    public function list(int $listId, array $filter = [], array $select = ['*'], array $order = ['ID' => 'DESC'], int $start = 0): array;

    /**
     * Получить элемент списка по ID.
     *
     * @param int $listId ID списка
     * @param int $elementId ID элемента
     * @return array|null
     */
    public function get(int $listId, int $elementId): ?array;

    /**
     * Добавить элемент в список.
     *
     * @param int $listId ID списка
     * @param array $fields Поля нового элемента
     * @return int|null
     */
    public function add(int $listId, array $fields): ?int;

    /**
     * Обновить элемент списка.
     *
     * @param int $listId ID списка
     * @param int $elementId ID элемента
     * @param array $fields Обновляемые поля
     * @return bool
     */
    public function update(int $listId, int $elementId, array $fields): bool;

    /**
     * Удалить элемент списка.
     *
     * @param int $listId ID списка
     * @param int $elementId ID элемента
     * @return bool
     */
    public function delete(int $listId, int $elementId): bool;

    /**
     * Получить поля списка.
     *
     * @param int $listId ID списка
     * @return array
     */
    public function fields(int $listId): array;

    /**
     * Получить информацию о списке.
     *
     * @param int $listId ID списка
     * @return array|null
     */
    public function getListInfo(int $listId): ?array;

    /**
     * Получить все списки пользователя.
     *
     * @return array
     */
    public function getAllLists(): array;
}

