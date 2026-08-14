<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Bitrix24\SDK\Services\CRM\Company\Result\CompanyItemResult;
use Leko\Bitrix24\Contracts\CompanyClientInterface;

/**
 * Клиент компаний Bitrix24.
 */
class CompanyClient extends CrmEntityClient implements CompanyClientInterface
{
    protected function entity(): string
    {
        return 'company';
    }

    public function list(array $filter = [], array $select = ['*'], array $order = ['ID' => 'DESC'], int $start = 0): array
    {
        return $this->listEntities(
            $filter,
            $select,
            $order,
            $start,
            fn () => $this->serviceBuilder->getCRMScope()->company()->list($order, $filter, $select, $start)->getCompanies()
        );
    }

    public function get(int $id): CompanyItemResult
    {
        return $this->getEntity($id, fn () => $this->serviceBuilder->getCRMScope()->company()->get($id)->company());
    }

    public function add(array $fields): int
    {
        return $this->addEntity($fields, fn () => $this->serviceBuilder->getCRMScope()->company()->add($fields)->getId());
    }

    public function update(int $id, array $fields): bool
    {
        return $this->updateEntity($id, $fields, fn () => $this->serviceBuilder->getCRMScope()->company()->update($id, $fields)->isSuccess());
    }

    public function delete(int $id): bool
    {
        return $this->deleteEntity($id, fn () => $this->serviceBuilder->getCRMScope()->company()->delete($id)->isSuccess());
    }

    public function fields(): array
    {
        return $this->entityFields(fn () => $this->serviceBuilder->getCRMScope()->company()->fields()->getFieldsDescription());
    }
}
