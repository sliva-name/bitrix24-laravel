<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Throwable;

/**
 * Клиент для работы с пользовательскими списками Bitrix24
 *
 * Предоставляет методы для работы с пользовательскими списками CRM.
 */
class ListClient extends BaseClient
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
        try {
            $params = [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listId,
            ];

            if (!empty($filter)) {
                $params['filter'] = $filter;
            }
            if (!empty($select) && !in_array('*', $select)) {
                $params['select'] = $select;
            }
            if (!empty($order)) {
                $params['order'] = $order;
            }
            if ($start > 0) {
                $params['start'] = $start;
            }

            return $this->callMethod('lists.element.get', $params, fn() => $this->serviceBuilder->getCRMScope()->lists()->getList($listId, $order, $filter, $select, $start)->getListItems()) ?? [];
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return [];
        }
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
        try {
            return $this->callMethod('lists.element.get', [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listId,
                'ELEMENT_ID' => $elementId,
            ], fn() => $this->serviceBuilder->getCRMScope()->lists()->getListElement($listId, $elementId)->getListElement());
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return null;
        }
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
        try {
            return $this->callMethod('lists.element.add', [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listId,
                'fields' => $fields
            ], fn() => $this->serviceBuilder->getCRMScope()->lists()->addListElement($listId, $fields)->getId());
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return null;
        }
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
        try {
            $result = $this->callMethod('lists.element.update', [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listId,
                'ELEMENT_ID' => $elementId,
                'fields' => $fields
            ], fn() => $this->serviceBuilder->getCRMScope()->lists()->updateListElement($listId, $elementId, $fields)->isSuccess());

            return $result === true;
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return false;
        }
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
        try {
            $result = $this->callMethod('lists.element.delete', [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listId,
                'ELEMENT_ID' => $elementId
            ], fn() => $this->serviceBuilder->getCRMScope()->lists()->deleteListElement($listId, $elementId)->isSuccess());

            return $result === true;
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return false;
        }
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
        try {
            return $this->callMethod('lists.field.get', [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listId
            ], fn() => $this->serviceBuilder->getCRMScope()->lists()->getListFields($listId)->getFieldsDescription()) ?? [];
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return [];
        }
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
        try {
            return $this->callMethod('lists.get', [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => $listId
            ], fn() => $this->serviceBuilder->getCRMScope()->lists()->getList($listId)->getList());
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return null;
        }
    }

    /**
     * Получить все списки пользователя.
     *
     * @return array
     * @throws Throwable
     */
    public function getAllLists(): array
    {
        try {
            return $this->callMethod('lists.get', [
                'IBLOCK_TYPE_ID' => 'lists'
            ], fn() => $this->serviceBuilder->getCRMScope()->lists()->getLists()->getLists()) ?? [];
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return [];
        }
    }
}
