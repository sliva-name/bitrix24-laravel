<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Bitrix24\SDK\Services\CRM\Contact\Result\ContactItemResult;
use Leko\Bitrix24\Contracts\ContactClientInterface;

/**
 * Клиент контактов Bitrix24.
 */
class ContactClient extends CrmEntityClient implements ContactClientInterface
{
    protected function entity(): string
    {
        return 'contact';
    }

    public function list(array $filter = [], array $select = ['*'], array $order = ['ID' => 'DESC'], int $start = 0): array
    {
        return $this->listEntities(
            $filter,
            $select,
            $order,
            $start,
            fn () => $this->serviceBuilder->getCRMScope()->contact()->list($order, $filter, $select, $start)->getContacts()
        );
    }

    public function get(int $id): ContactItemResult
    {
        return $this->getEntity($id, fn () => $this->serviceBuilder->getCRMScope()->contact()->get($id)->contact());
    }

    public function add(array $fields): int
    {
        return $this->addEntity($fields, fn () => $this->serviceBuilder->getCRMScope()->contact()->add($fields)->getId());
    }

    public function update(int $id, array $fields): bool
    {
        return $this->updateEntity($id, $fields, fn () => $this->serviceBuilder->getCRMScope()->contact()->update($id, $fields)->isSuccess());
    }

    public function delete(int $id): bool
    {
        return $this->deleteEntity($id, fn () => $this->serviceBuilder->getCRMScope()->contact()->delete($id)->isSuccess());
    }

    public function fields(): array
    {
        return $this->entityFields(fn () => $this->serviceBuilder->getCRMScope()->contact()->fields()->getFieldsDescription());
    }
}
