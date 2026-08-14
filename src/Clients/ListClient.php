<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Leko\Bitrix24\Contracts\ListClientInterface;

/**
 * Клиент пользовательских списков Bitrix24.
 */
class ListClient extends BaseClient implements ListClientInterface
{
    private const IBLOCK_TYPE = 'lists';

    public function list(int $listId, array $filter = [], array $select = ['*'], array $order = ['ID' => 'DESC'], int $start = 0): array
    {
        $params = $this->buildParams(
            $this->iblockParams($listId),
            [
                'filter' => $filter,
                'select' => [
                    'value' => $select,
                    'condition' => static fn ($value): bool => !empty($value) && !in_array('*', $value, true),
                ],
                'order' => $order,
                'start' => [
                    'value' => $start,
                    'condition' => static fn ($value): bool => $value > 0,
                ],
            ]
        );

        return $this->asArray($this->callMethod('lists.element.get', $params));
    }

    public function get(int $listId, int $elementId): ?array
    {
        $result = $this->asArray($this->callMethod('lists.element.get', $this->iblockParams($listId, [
            'ELEMENT_ID' => $elementId,
        ])));

        return $result[0] ?? null;
    }

    public function add(int $listId, array $fields): ?int
    {
        return $this->asInt($this->callMethod('lists.element.add', $this->iblockParams($listId, [
            'fields' => $fields,
        ])));
    }

    public function update(int $listId, int $elementId, array $fields): bool
    {
        return $this->isSuccessful($this->callMethod('lists.element.update', $this->iblockParams($listId, [
            'ELEMENT_ID' => $elementId,
            'fields' => $fields,
        ])));
    }

    public function delete(int $listId, int $elementId): bool
    {
        return $this->isSuccessful($this->callMethod('lists.element.delete', $this->iblockParams($listId, [
            'ELEMENT_ID' => $elementId,
        ])));
    }

    public function fields(int $listId): array
    {
        return $this->asArray($this->callMethod('lists.field.get', $this->iblockParams($listId)));
    }

    public function getListInfo(int $listId): ?array
    {
        $result = $this->callMethod('lists.get', $this->iblockParams($listId));

        if (!is_array($result)) {
            return null;
        }

        return $result[0] ?? $result;
    }

    public function getAllLists(): array
    {
        return $this->asArray($this->callMethod('lists.get', [
            'IBLOCK_TYPE_ID' => self::IBLOCK_TYPE,
        ]));
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function iblockParams(int $listId, array $extra = []): array
    {
        return array_merge([
            'IBLOCK_TYPE_ID' => self::IBLOCK_TYPE,
            'IBLOCK_ID' => $listId,
        ], $extra);
    }
}
