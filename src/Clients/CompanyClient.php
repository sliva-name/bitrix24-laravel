<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Leko\Bitrix24\Contracts\CompanyClientInterface;
use Throwable;

/**
 * Клиент компании для Bitrix24
 *
 * Предоставляет методы для работы с компании CRM.
 */
class CompanyClient extends BaseClient implements CompanyClientInterface
{
    /**
     * Получить список компании.
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
        return $this->callCrmMethod('company', 'list', [
            'filter' => $filter,
            'select' => $select,
            'order' => $order,
            'start' => $start,
        ], fn() => $this->serviceBuilder->getCRMScope()->company()->list($order, $filter, $select, $start)->getCompanies()) ?? [];
    }

    /**
     * Получить компанию по ID.
     *
     * @param int $id ID записи
     * @return array|null
     * @throws Throwable
     */
    public function get(int $id): ?array
    {
        return $this->callCrmMethod('company', 'get', [
            'id' => $id
        ], fn() => $this->serviceBuilder->getCRMScope()->company()->get($id)->company());
    }

    /**
     * Добавить компанию.
     *
     * @param array $fields Поля новой записи
     * @return int|null
     * @throws Throwable
     */
    public function add(array $fields): ?int
    {
        return $this->callCrmMethod('company', 'add', [
            'fields' => $fields
        ], fn() => $this->serviceBuilder->getCRMScope()->company()->add($fields)->getId());
    }

    /**
     * Обновить компанию.
     *
     * @param int $id ID записи
     * @param array $fields Обновляемые поля
     * @return bool
     * @throws Throwable
     */
    public function update(int $id, array $fields): bool
    {
        $result = $this->callCrmMethod('company', 'update', [
            'id' => $id,
            'fields' => $fields
        ], fn() => $this->serviceBuilder->getCRMScope()->company()->update($id, $fields)->isSuccess());
        
        return $result === true;
    }

    /**
     * Удалить компанию.
     *
     * @param int $id ID записи
     * @return bool
     * @throws Throwable
     */
    public function delete(int $id): bool
    {
        $result = $this->callCrmMethod('company', 'delete', [
            'id' => $id
        ], fn() => $this->serviceBuilder->getCRMScope()->company()->delete($id)->isSuccess());
        
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
        return $this->callCrmMethod('company', 'fields', [], 
            fn() => $this->serviceBuilder->getCRMScope()->company()->fields()->getFieldsDescription()
        ) ?? [];
    }
}
