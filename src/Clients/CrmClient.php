<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Throwable;

/**
 * CRM клиент для Bitrix24
 *
 * Предоставляет доступ к общей функциональности CRM.
 */
class CrmClient extends BaseClient
{
    /**
     * Получить поля CRM для сущности.
     *
     * @param string $entityType Тип сущности
     * @return array
     * @throws Throwable
     */
    public function getFields(string $entityType): array
    {
        try {
            $method = "crm.{$entityType}.fields";
            
            return $this->callMethod($method, [], 
                fn() => $this->serviceBuilder->getCRMScope()->call($method)
            ) ?? [];
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return [];
        }
    }

    /**
     * Получить список элементов сущности.
     *
     * @param string $entityType Тип сущности
     * @param array $filter Фильтры выборки
     * @param array $select Список полей для выборки
     * @param array $order Сортировка результатов
     * @param int $start Смещение для пагинации
     * @return array
     * @throws Throwable
     */
    public function getList(string $entityType, array $filter = [], array $select = [], array $order = [], int $start = 0): array
    {
        try {
            $method = "crm.{$entityType}.list";
            $params = [
                'filter' => $filter,
                'select' => $select,
                'order' => $order,
                'start' => $start,
            ];

            return $this->callMethod($method, $params, 
                fn() => $this->serviceBuilder->getCRMScope()->call($method, $params)
            ) ?? [];
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return [];
        }
    }

    /**
     * Получить сущность по ID.
     *
     * @param string $entityType Тип сущности
     * @param int $id ID записи
     * @return array|null
     * @throws Throwable
     */
    public function get(string $entityType, int $id): ?array
    {
        try {
            $method = "crm.{$entityType}.get";
            
            return $this->callMethod($method, ['id' => $id], 
                fn() => $this->serviceBuilder->getCRMScope()->call($method, ['id' => $id])
            );
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return null;
        }
    }

    /**
     * Добавить новую сущность.
     *
     * @param string $entityType Тип сущности
     * @param array $fields Поля новой записи
     * @return int|null
     * @throws Throwable
     */
    public function add(string $entityType, array $fields): ?int
    {
        try {
            $method = "crm.{$entityType}.add";
            
            return $this->callMethod($method, ['fields' => $fields], 
                fn() => $this->serviceBuilder->getCRMScope()->call($method, ['fields' => $fields])['result'] ?? null
            );
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return null;
        }
    }

    /**
     * Обновить сущность.
     *
     * @param string $entityType Тип сущности
     * @param int $id ID записи
     * @param array $fields Обновляемые поля
     * @return bool
     * @throws Throwable
     */
    public function update(string $entityType, int $id, array $fields): bool
    {
        try {
            $method = "crm.{$entityType}.update";
            $params = [
                'id' => $id,
                'fields' => $fields,
            ];

            $result = $this->callMethod($method, $params, 
                fn() => $this->serviceBuilder->getCRMScope()->call($method, $params)
            );
            
            return isset($result['result']) || is_array($result);
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return false;
        }
    }

    /**
     * Удалить сущность.
     *
     * @param string $entityType Тип сущности
     * @param int $id ID записи
     * @return bool
     * @throws Throwable
     */
    public function delete(string $entityType, int $id): bool
    {
        try {
            $method = "crm.{$entityType}.delete";
            
            $result = $this->callMethod($method, ['id' => $id], 
                fn() => $this->serviceBuilder->getCRMScope()->call($method, ['id' => $id])
            );
            
            return isset($result['result']) || is_array($result);
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return false;
        }
    }
}
