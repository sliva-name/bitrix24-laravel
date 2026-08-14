<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Bitrix24\SDK\Services\CRM\Deal\Result\DealItemResult;
use Leko\Bitrix24\Contracts\DealClientInterface;

/**
 * Клиент сделок Bitrix24.
 */
class DealClient extends CrmEntityClient implements DealClientInterface
{
    protected function entity(): string
    {
        return 'deal';
    }

    public function list(array $filter = [], array $select = ['*'], array $order = ['ID' => 'DESC'], int $start = 0): array
    {
        return $this->listEntities(
            $filter,
            $select,
            $order,
            $start,
            fn () => $this->serviceBuilder->getCRMScope()->deal()->list($order, $filter, $select, $start)->getDeals()
        );
    }

    public function get(int $id): DealItemResult
    {
        return $this->getEntity($id, fn () => $this->serviceBuilder->getCRMScope()->deal()->get($id)->deal());
    }

    public function add(array $fields): int
    {
        return $this->addEntity($fields, fn () => $this->serviceBuilder->getCRMScope()->deal()->add($fields)->getId());
    }

    public function update(int $id, array $fields): bool
    {
        return $this->updateEntity($id, $fields, fn () => $this->serviceBuilder->getCRMScope()->deal()->update($id, $fields)->isSuccess());
    }

    public function delete(int $id): bool
    {
        return $this->deleteEntity($id, fn () => $this->serviceBuilder->getCRMScope()->deal()->delete($id)->isSuccess());
    }

    public function fields(): array
    {
        return $this->entityFields(fn () => $this->serviceBuilder->getCRMScope()->deal()->fields()->getFieldsDescription());
    }
}
