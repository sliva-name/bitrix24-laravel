<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use LogicException;

/**
 * Общий CRUD для CRM-сущностей (лиды, сделки, контакты, компании).
 */
abstract class CrmEntityClient extends BaseClient
{
    abstract protected function entity(): string;

    /**
     * @param array<string, mixed> $filter
     * @param list<string> $select
     * @param array<string, string> $order
     * @return array<int, object>
     */
    public function list(array $filter = [], array $select = ['*'], array $order = ['ID' => 'DESC'], int $start = 0): array
    {
        return $this->callCrmMethod($this->entity(), 'list', [
            'filter' => $filter,
            'select' => $select,
            'order' => $order,
            'start' => $start,
        ], fn () => $this->extractList(
            $this->crmService()->list($order, $filter, $select, $start)
        )) ?? [];
    }

    public function get(int $id): object
    {
        return $this->callCrmMethod(
            $this->entity(),
            'get',
            ['id' => $id],
            fn () => $this->extractItem($this->crmService()->get($id))
        );
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function add(array $fields): int
    {
        return (int) $this->callCrmMethod(
            $this->entity(),
            'add',
            ['fields' => $fields],
            fn () => $this->crmService()->add($fields)->getId()
        );
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function update(int $id, array $fields): bool
    {
        return $this->isSuccessful($this->callCrmMethod(
            $this->entity(),
            'update',
            ['id' => $id, 'fields' => $fields],
            fn () => $this->crmService()->update($id, $fields)->isSuccess()
        ));
    }

    public function delete(int $id): bool
    {
        return $this->isSuccessful($this->callCrmMethod(
            $this->entity(),
            'delete',
            ['id' => $id],
            fn () => $this->crmService()->delete($id)->isSuccess()
        ));
    }

    /**
     * @return array<mixed>
     */
    public function fields(): array
    {
        return $this->callCrmMethod(
            $this->entity(),
            'fields',
            [],
            fn () => $this->crmService()->fields()->getFieldsDescription()
        ) ?? [];
    }

    private function crmService(): object
    {
        return $this->serviceBuilder->getCRMScope()->{$this->entity()}();
    }

    /**
     * @return array<int, object>
     */
    private function extractList(object $result): array
    {
        return match ($this->entity()) {
            'lead' => $result->getLeads(),
            'deal' => $result->getDeals(),
            'contact' => $result->getContacts(),
            'company' => $result->getCompanies(),
            default => throw new LogicException("Неизвестная CRM-сущность «{$this->entity()}»."),
        };
    }

    private function extractItem(object $result): object
    {
        return match ($this->entity()) {
            'lead' => $result->lead(),
            'deal' => $result->deal(),
            'contact' => $result->contact(),
            'company' => $result->company(),
            default => throw new LogicException("Неизвестная CRM-сущность «{$this->entity()}»."),
        };
    }
}
