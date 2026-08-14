<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

/**
 * Общий CRUD для CRM-сущностей (лиды, сделки, контакты, компании).
 */
abstract class CrmEntityClient extends BaseClient
{
    abstract protected function entity(): string;

    /**
     * @template T
     * @param array<string, mixed> $filter
     * @param list<string> $select
     * @param array<string, string> $order
     * @param callable(): T $sdk
     * @return T
     */
    protected function listEntities(array $filter, array $select, array $order, int $start, callable $sdk): mixed
    {
        return $this->callCrmMethod($this->entity(), 'list', [
            'filter' => $filter,
            'select' => $select,
            'order' => $order,
            'start' => $start,
        ], $sdk) ?? [];
    }

    /**
     * @template T
     * @param callable(): T $sdk
     * @return T
     */
    protected function getEntity(int $id, callable $sdk): mixed
    {
        return $this->callCrmMethod($this->entity(), 'get', ['id' => $id], $sdk);
    }

    /**
     * @param array<string, mixed> $fields
     * @param callable(): int $sdk
     */
    protected function addEntity(array $fields, callable $sdk): int
    {
        return (int) $this->callCrmMethod($this->entity(), 'add', ['fields' => $fields], $sdk);
    }

    /**
     * @param array<string, mixed> $fields
     * @param callable(): bool $sdk
     */
    protected function updateEntity(int $id, array $fields, callable $sdk): bool
    {
        return $this->isSuccessful(
            $this->callCrmMethod($this->entity(), 'update', [
                'id' => $id,
                'fields' => $fields,
            ], $sdk)
        );
    }

    /**
     * @param callable(): bool $sdk
     */
    protected function deleteEntity(int $id, callable $sdk): bool
    {
        return $this->isSuccessful(
            $this->callCrmMethod($this->entity(), 'delete', ['id' => $id], $sdk)
        );
    }

    /**
     * @param callable(): array<mixed> $sdk
     * @return array<mixed>
     */
    protected function entityFields(callable $sdk): array
    {
        return $this->callCrmMethod($this->entity(), 'fields', [], $sdk) ?? [];
    }
}
