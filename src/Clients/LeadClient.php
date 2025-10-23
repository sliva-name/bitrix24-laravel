<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Bitrix24\SDK\Services\CRM\Lead\Result\LeadItemResult;
use Leko\Bitrix24\Contracts\LeadClientInterface;
use Throwable;

/**
 * Клиент лиды для Bitrix24
 *
 * Предоставляет методы для работы с лиды CRM.
 */
class LeadClient extends BaseClient implements LeadClientInterface
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
        return $this->callCrmMethod('lead', 'list', [
            'filter' => $filter,
            'select' => $select,
            'order' => $order,
            'start' => $start,
        ], fn() => $this->serviceBuilder->getCRMScope()->lead()->list($order, $filter, $select, $start)->getLeads()) ?? [];
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
        return $this->callCrmMethod('lead', 'get', [
            'id' => $id
        ], fn() => $this->serviceBuilder->getCRMScope()->lead()->get($id)->lead());
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
        return $this->callCrmMethod('lead', 'add', [
            'fields' => $fields
        ], fn() => $this->serviceBuilder->getCRMScope()->lead()->add($fields)->getId());
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
        $result = $this->callCrmMethod('lead', 'update', [
            'id' => $id,
            'fields' => $fields
        ], fn() => $this->serviceBuilder->getCRMScope()->lead()->update($id, $fields)->isSuccess());
        
        return $result === true;
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
        $result = $this->callCrmMethod('lead', 'delete', [
            'id' => $id
        ], fn() => $this->serviceBuilder->getCRMScope()->lead()->delete($id)->isSuccess());
        
        return $result === true;
    }

    /**
     * Получить поля записи.
     *
     * @return array
     * @throws Throwable
     */
    public function fields(): array
    {
        return $this->callCrmMethod('lead', 'fields', [], 
            fn() => $this->serviceBuilder->getCRMScope()->lead()->fields()->getFieldsDescription()
        ) ?? [];
    }
}
