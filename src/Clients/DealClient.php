<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Bitrix24\SDK\Services\CRM\Deal\Result\DealItemResult;
use Leko\Bitrix24\Contracts\DealClientInterface;
use Throwable;

/**
 * Клиент сделки для Bitrix24
 *
 * Предоставляет методы для работы со сделки CRM.
 */
class DealClient extends BaseClient implements DealClientInterface
{
    /**
     * Получить список сделки.
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
        return $this->callCrmMethod('deal', 'list', [
            'filter' => $filter,
            'select' => $select,
            'order' => $order,
            'start' => $start,
        ], fn() => $this->serviceBuilder->getCRMScope()->deal()->list($order, $filter, $select, $start)->getDeals()) ?? [];
    }

    /**
     * Получить сделку по ID.
     *
     * @param int $id ID записи
     * @return array|DealItemResult|null
     * @throws Throwable
     */
    public function get(int $id): array|DealItemResult|null
    {
        return $this->callCrmMethod('deal', 'get', [
            'id' => $id
        ], fn() => $this->serviceBuilder->getCRMScope()->deal()->get($id)->deal());
    }

    /**
     * Добавить сделку.
     *
     * @param array $fields Поля новой записи
     * @return int|null
     * @throws Throwable
     */
    public function add(array $fields): ?int
    {
        return $this->callCrmMethod('deal', 'add', [
            'fields' => $fields
        ], fn() => $this->serviceBuilder->getCRMScope()->deal()->add($fields)->getId());
    }

    /**
     * Обновить сделку.
     *
     * @param int $id ID записи
     * @param array $fields Обновляемые поля
     * @return bool
     * @throws Throwable
     */
    public function update(int $id, array $fields): bool
    {
        $result = $this->callCrmMethod('deal', 'update', [
            'id' => $id,
            'fields' => $fields
        ], fn() => $this->serviceBuilder->getCRMScope()->deal()->update($id, $fields)->isSuccess());
        
        return $result === true;
    }

    /**
     * Удалить сделку.
     *
     * @param int $id ID записи
     * @return bool
     * @throws Throwable
     */
    public function delete(int $id): bool
    {
        $result = $this->callCrmMethod('deal', 'delete', [
            'id' => $id
        ], fn() => $this->serviceBuilder->getCRMScope()->deal()->delete($id)->isSuccess());
        
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
        return $this->callCrmMethod('deal', 'fields', [], 
            fn() => $this->serviceBuilder->getCRMScope()->deal()->fields()->getFieldsDescription()
        ) ?? [];
    }
}
