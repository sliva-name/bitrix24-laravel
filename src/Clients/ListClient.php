<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Leko\Bitrix24\Contracts\ListClientInterface;
use Throwable;

/**
 * Клиент для работы с пользовательскими списками Bitrix24
 *
 * Предоставляет методы для работы с пользовательскими списками CRM.
 */
class ListClient extends BaseClient implements ListClientInterface
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
     * @throws Throwable
     */
    public function list(int $listId, array $filter = [], array $select = ['*'], array $order = ['ID' => 'DESC'], int $start = 0): array
    {
        $params = $this->buildParams(
            [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listId,
            ],
            [
                'filter' => $filter,
                'select' => [
                    'value' => $select,
                    'condition' => fn($val) => !empty($val) && !in_array('*', $val)
                ],
                'order' => $order,
                'start' => [
                    'value' => $start,
                    'condition' => fn($val) => $val > 0
                ],
            ]
        );

        return $this->callMethod('lists.element.get', $params) ?? [];
    }

    /**
     * Получить элемент списка по ID.
     *
     * @param int $listId ID списка
     * @param int $elementId ID элемента
     * @return array|null
     * @throws Throwable
     */
    public function get(int $listId, int $elementId): ?array
    {
        $result = $this->callMethod('lists.element.get', [
            'IBLOCK_TYPE_ID' => 'lists',
            'IBLOCK_ID' => $listId,
            'ELEMENT_ID' => $elementId,
        ]);

        return is_array($result) ? ($result[0] ?? null) : null;
    }

    /**
     * Добавить элемент в список.
     *
     * @param int $listId ID списка
     * @param array $fields Поля нового элемента
     * @return int|null
     * @throws Throwable
     */
    public function add(int $listId, array $fields): ?int
    {
        $result = $this->callMethod('lists.element.add', [
            'IBLOCK_TYPE_ID' => 'lists',
            'IBLOCK_ID' => $listId,
            'fields' => $fields
        ]);

        return is_numeric($result) ? (int) $result : null;
    }

    /**
     * Обновить элемент списка.
     *
     * @param int $listId ID списка
     * @param int $elementId ID элемента
     * @param array $fields Обновляемые поля
     * @return bool
     * @throws Throwable
     */
    public function update(int $listId, int $elementId, array $fields): bool
    {
        $result = $this->callMethod('lists.element.update', [
            'IBLOCK_TYPE_ID' => 'lists',
            'IBLOCK_ID' => $listId,
            'ELEMENT_ID' => $elementId,
            'fields' => $fields
        ]);

        return $result === true || $result === 1 || $result === '1';
    }

    /**
     * Удалить элемент списка.
     *
     * @param int $listId ID списка
     * @param int $elementId ID элемента
     * @return bool
     * @throws Throwable
     */
    public function delete(int $listId, int $elementId): bool
    {
        $result = $this->callMethod('lists.element.delete', [
            'IBLOCK_TYPE_ID' => 'lists',
            'IBLOCK_ID' => $listId,
            'ELEMENT_ID' => $elementId
        ]);

        return $result === true || $result === 1 || $result === '1';
    }

    /**
     * Получить поля списка.
     *
     * @param int $listId ID списка
     * @return array
     * @throws Throwable
     */
    public function fields(int $listId): array
    {
        return $this->callMethod('lists.field.get', [
            'IBLOCK_TYPE_ID' => 'lists',
            'IBLOCK_ID' => $listId
        ]) ?? [];
    }

    /**
     * Получить информацию о списке.
     *
     * @param int $listId ID списка
     * @return array|null
     * @throws Throwable
     */
    public function getListInfo(int $listId): ?array
    {
        $result = $this->callMethod('lists.get', [
            'IBLOCK_TYPE_ID' => 'lists',
            'IBLOCK_ID' => $listId
        ]);

        return is_array($result) ? ($result[0] ?? $result) : null;
    }

    /**
     * Получить все списки пользователя.
     *
     * @return array
     * @throws Throwable
     */
    public function getAllLists(): array
    {
        return $this->callMethod('lists.get', [
            'IBLOCK_TYPE_ID' => 'lists'
        ]) ?? [];
    }
}
