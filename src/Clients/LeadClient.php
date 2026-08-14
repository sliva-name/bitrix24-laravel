<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Bitrix24\SDK\Services\CRM\Lead\Result\LeadItemResult;
use Leko\Bitrix24\Contracts\LeadClientInterface;

/**
 * Клиент лидов Bitrix24.
 */
class LeadClient extends CrmEntityClient implements LeadClientInterface
{
    protected function entity(): string
    {
        return 'lead';
    }

    public function list(array $filter = [], array $select = ['*'], array $order = ['ID' => 'DESC'], int $start = 0): array
    {
        return $this->listEntities(
            $filter,
            $select,
            $order,
            $start,
            fn () => $this->serviceBuilder->getCRMScope()->lead()->list($order, $filter, $select, $start)->getLeads()
        );
    }

    public function get(int $id): LeadItemResult
    {
        return $this->getEntity($id, fn () => $this->serviceBuilder->getCRMScope()->lead()->get($id)->lead());
    }

    public function add(array $fields): int
    {
        return $this->addEntity($fields, fn () => $this->serviceBuilder->getCRMScope()->lead()->add($fields)->getId());
    }

    public function update(int $id, array $fields): bool
    {
        return $this->updateEntity($id, $fields, fn () => $this->serviceBuilder->getCRMScope()->lead()->update($id, $fields)->isSuccess());
    }

    public function delete(int $id): bool
    {
        return $this->deleteEntity($id, fn () => $this->serviceBuilder->getCRMScope()->lead()->delete($id)->isSuccess());
    }

    public function fields(): array
    {
        return $this->entityFields(fn () => $this->serviceBuilder->getCRMScope()->lead()->fields()->getFieldsDescription());
    }
}
