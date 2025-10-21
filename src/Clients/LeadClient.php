<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Bitrix24\SDK\Services\CRM\Lead\Result\LeadItemResult;
use Throwable;

/**
 * Клиент лиды для Bitrix24
 *
 * Предоставляет методы для работы с лиды CRM.
 */
class LeadClient extends BaseClient
{
    /**
     * Получить список лиды.
     *
     * @param array $filter Фильтры выборки
     * @param array $select Список полей для выборки
     * @param array $order Сортировка результатов
     * @param int $start Смещение для пагинации
     * @return array
     * @throws Throwable
     */
    public function list(array $filter = [], array $select = ['*'], array $order = ['ID' => 'DESC'], int $start = 0): array
    {
        try {
            return $this->callCrmMethod('lead', 'list', [
                'filter' => $filter,
                'select' => $select,
                'order' => $order,
                'start' => $start,
            ], fn() => $this->serviceBuilder->getCRMScope()->lead()->list($order, $filter, $select, $start)->getLeads()) ?? [];
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return [];
        }
    }

    /**
     * Получить Лид по ID.
     *
     * @param int $id ID записи
     * @return array|LeadItemResult|null
     * @throws Throwable
     */
    public function get(int $id): array|LeadItemResult|null
    {
        try {
            return $this->callCrmMethod('lead', 'get', [
                'id' => $id
            ], fn() => $this->serviceBuilder->getCRMScope()->lead()->get($id)->lead());
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return null;
        }
    }

    /**
     * Добавить Лид.
     *
     * @param array $fields Поля новой записи
     * @return int|null
     * @throws Throwable
     */
    public function add(array $fields): ?int
    {
        try {
            return $this->callCrmMethod('lead', 'add', [
                'fields' => $fields
            ], fn() => $this->serviceBuilder->getCRMScope()->lead()->add($fields)->getId());
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return null;
        }
    }

    /**
     * Обновить Лид.
     *
     * @param int $id ID записи
     * @param array $fields Обновляемые поля
     * @return bool
     * @throws Throwable
     */
    public function update(int $id, array $fields): bool
    {
        try {
            $result = $this->callCrmMethod('lead', 'update', [
                'id' => $id,
                'fields' => $fields
            ], fn() => $this->serviceBuilder->getCRMScope()->lead()->update($id, $fields)->isSuccess());
            
            return $result === true;
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return false;
        }
    }

    /**
     * Удалить Лид.
     *
     * @param int $id ID записи
     * @return bool
     * @throws Throwable
     */
    public function delete(int $id): bool
    {
        try {
            $result = $this->callCrmMethod('lead', 'delete', [
                'id' => $id
            ], fn() => $this->serviceBuilder->getCRMScope()->lead()->delete($id)->isSuccess());
            
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
     * @throws Throwable
     */
    public function fields(): array
    {
        try {
            return $this->callCrmMethod('lead', 'fields', [], 
                fn() => $this->serviceBuilder->getCRMScope()->lead()->fields()->getFieldsDescription()
            ) ?? [];
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return [];
        }
    }
}
