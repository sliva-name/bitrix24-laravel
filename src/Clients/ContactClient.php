<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Bitrix24\SDK\Services\CRM\Contact\Result\ContactItemResult;
use Throwable;

/**
 * Клиент контакты для Bitrix24
 *
 * Предоставляет методы для работы с контакты CRM.
 */
class ContactClient extends BaseClient
{
    /**
     * Получить список контакты.
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
            return $this->callCrmMethod('contact', 'list', [
                'filter' => $filter,
                'select' => $select,
                'order' => $order,
                'start' => $start,
            ], fn() => $this->serviceBuilder->getCRMScope()->contact()->list($order, $filter, $select, $start)->getContacts()) ?? [];
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return [];
        }
    }

    /**
     * Получить контакт по ID.
     *
     * @param int $id ID записи
     * @return array|ContactItemResult|null
     */
    public function get(int $id): array|ContactItemResult|null
    {
        try {
            return $this->callCrmMethod('contact', 'get', [
                'id' => $id
            ], fn() => $this->serviceBuilder->getCRMScope()->contact()->get($id)->contact());
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return null;
        }
    }

    /**
     * Добавить контакт.
     *
     * @param array $fields Поля новой записи
     * @return int|null
     * @throws Throwable
     */
    public function add(array $fields): ?int
    {
        try {
            return $this->callCrmMethod('contact', 'add', [
                'fields' => $fields
            ], fn() => $this->serviceBuilder->getCRMScope()->contact()->add($fields)->getId());
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return null;
        }
    }

    /**
     * Обновить контакт.
     *
     * @param int $id ID записи
     * @param array $fields Обновляемые поля
     * @return bool
     * @throws Throwable
     */
    public function update(int $id, array $fields): bool
    {
        try {
            $result = $this->callCrmMethod('contact', 'update', [
                'id' => $id,
                'fields' => $fields
            ], fn() => $this->serviceBuilder->getCRMScope()->contact()->update($id, $fields)->isSuccess());
            
            return $result === true;
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return false;
        }
    }

    /**
     * Удалить контакт.
     *
     * @param int $id ID записи
     * @return bool
     * @throws Throwable
     */
    public function delete(int $id): bool
    {
        try {
            $result = $this->callCrmMethod('contact', 'delete', [
                'id' => $id
            ], fn() => $this->serviceBuilder->getCRMScope()->contact()->delete($id)->isSuccess());
            
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
            return $this->callCrmMethod('contact', 'fields', [], 
                fn() => $this->serviceBuilder->getCRMScope()->contact()->fields()->getFieldsDescription()
            ) ?? [];
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return [];
        }
    }
}
