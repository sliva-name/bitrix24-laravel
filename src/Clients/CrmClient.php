<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Leko\Bitrix24\Contracts\CrmClientInterface;

/**
 * Универсальный CRM-клиент для произвольных сущностей.
 */
class CrmClient extends BaseClient implements CrmClientInterface
{
    public function getFields(string $entityType): array
    {
        return $this->asArray($this->callCrmMethod($entityType, 'fields'));
    }

    public function getList(string $entityType, array $filter = [], array $select = [], array $order = [], int $start = 0): array
    {
        $params = $this->buildParams([], [
            'filter' => $filter,
            'select' => $select,
            'order' => $order,
            'start' => [
                'value' => $start,
                'condition' => static fn ($value): bool => $value > 0,
            ],
        ]);

        return $this->asArray($this->callCrmMethod($entityType, 'list', $params));
    }

    public function get(string $entityType, int $id): array
    {
        return $this->asArray($this->callCrmMethod($entityType, 'get', ['id' => $id]));
    }

    public function add(string $entityType, array $fields): ?int
    {
        return $this->asInt($this->callCrmMethod($entityType, 'add', ['fields' => $fields]));
    }

    public function update(string $entityType, int $id, array $fields): bool
    {
        return $this->isSuccessful($this->callCrmMethod($entityType, 'update', [
            'id' => $id,
            'fields' => $fields,
        ]));
    }

    public function delete(string $entityType, int $id): bool
    {
        return $this->isSuccessful($this->callCrmMethod($entityType, 'delete', ['id' => $id]));
    }
}
