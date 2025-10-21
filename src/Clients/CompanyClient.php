<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Throwable;

/**
 * Клиент компании для Bitrix24
 *
 * Предоставляет методы для работы с компании CRM.
 */
class CompanyClient extends BaseClient
{
    /**
     * Получить список компании.
     *
     * @param array $filter Фильтры выборки
     * @param array $select Список полей для выборки
     * @param array $order Сортировка результатов
     * @param int $start Смещение для пагинации
     * @return array
     */
    public function list(array $filter = [], array $select = ['*'], array $order = ['ID' => 'DESC'], int $start = 0): array
    {
        try {
            return $this->callCrmMethod('company', 'list', [
                'filter' => $filter,
                'select' => $select,
                'order' => $order,
                'start' => $start,
            ], fn() => $this->serviceBuilder->getCRMScope()->company()->list($order, $filter, $select, $start)->getCompanies()) ?? [];
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return [];
        }
    }

    /**
     * Получить компанию по ID.
     *
     * @param int $id ID записи
     * @return array|null
     */
    public function get(int $id): ?array
    {
        try {
            return $this->callCrmMethod('company', 'get', [
                'id' => $id
            ], fn() => $this->serviceBuilder->getCRMScope()->company()->get($id)->company());
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return null;
        }
    }

    /**
     * Добавить компанию.
     *
     * @param array $fields Поля новой записи
     * @return int|null
     */
    public function add(array $fields): ?int
    {
        try {
            return $this->callCrmMethod('company', 'add', [
                'fields' => $fields
            ], fn() => $this->serviceBuilder->getCRMScope()->company()->add($fields)->getId());
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return null;
        }
    }

    /**
     * Обновить компанию.
     *
     * @param int $id ID записи
     * @param array $fields Обновляемые поля
     * @return bool
     */
    public function update(int $id, array $fields): bool
    {
        try {
            $result = $this->callCrmMethod('company', 'update', [
                'id' => $id,
                'fields' => $fields
            ], fn() => $this->serviceBuilder->getCRMScope()->company()->update($id, $fields)->isSuccess());
            
            return $result === true;
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return false;
        }
    }

    /**
     * Удалить компанию.
     *
     * @param int $id ID записи
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $result = $this->callCrmMethod('company', 'delete', [
                'id' => $id
            ], fn() => $this->serviceBuilder->getCRMScope()->company()->delete($id)->isSuccess());
            
            return $result === true;
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return false;
        }
    }

    /**
     * Получить поля записи.
     *
     * @return array
     */
    public function fields(): array
    {
        try {
            return $this->callCrmMethod('company', 'fields', [], 
                fn() => $this->serviceBuilder->getCRMScope()->company()->fields()->getFieldsDescription()
            ) ?? [];
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return [];
        }
    }
}
