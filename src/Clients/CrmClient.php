<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Leko\Bitrix24\Contracts\CrmClientInterface;
use Throwable;

/**
 * CRM клиент для Bitrix24
 *
 * Предоставляет доступ к общей функциональности CRM.
 */
class CrmClient extends BaseClient implements CrmClientInterface
{
    /**
     * Получить поля CRM для сущности.
     *
     * @param string $entityType Тип сущности
     * @return array
     * @throws Throwable
     */
    public function getFields(string $entityType): array
    {
        $method = "crm.{$entityType}.fields";

        return $this->callMethod($method) ?? [];
    }

    /**
     * Получить список элементов сущности.
     *
     * @param string $entityType Тип сущности
     * @param array $filter Фильтры выборки
     * @param array $select Список полей для выборки
     * @param array $order Сортировка результатов
     * @param int $start Смещение для пагинации
     * @return array
     * @throws Throwable
     */
    public function getList(string $entityType, array $filter = [], array $select = [], array $order = [], int $start = 0): array
    {
        $method = "crm.{$entityType}.list";

        $params = $this->buildParams(
            [],
            [
                'filter' => $filter,
                'select' => $select,
                'order' => $order,
                'start' => [
                    'value' => $start,
                    'condition' => fn($val) => $val > 0
                ],
            ]
        );

        return $this->callMethod($method, $params) ?? [];
    }

    /**
     * Получить сущность по ID.
     *
     * @param string $entityType Тип сущности
     * @param int $id ID записи
     * @return array
     * @throws Throwable
     */
    public function get(string $entityType, int $id): array
    {
        $method = "crm.{$entityType}.get";

        $result = $this->callMethod($method, ['id' => $id]);

        return is_array($result) ? $result : [];
    }

    /**
     * Добавить новую сущность.
     *
     * @param string $entityType Тип сущности
     * @param array $fields Поля новой записи
     * @return int|null
     * @throws Throwable
     */
    public function add(string $entityType, array $fields): ?int
    {
        $method = "crm.{$entityType}.add";

        $result = $this->callMethod($method, ['fields' => $fields]);

        return is_numeric($result) ? (int) $result : null;
    }

    /**
     * Обновить сущность.
     *
     * @param string $entityType Тип сущности
     * @param int $id ID записи
     * @param array $fields Обновляемые поля
     * @return bool
     * @throws Throwable
     */
    public function update(string $entityType, int $id, array $fields): bool
    {
        $method = "crm.{$entityType}.update";
        $params = [
            'id' => $id,
            'fields' => $fields,
        ];

        $result = $this->callMethod($method, $params);

        return $result === true || $result === 1 || $result === '1';
    }

    /**
     * Удалить сущность.
     *
     * @param string $entityType Тип сущности
     * @param int $id ID записи
     * @return bool
     * @throws Throwable
     */
    public function delete(string $entityType, int $id): bool
    {
        $method = "crm.{$entityType}.delete";

        $result = $this->callMethod($method, ['id' => $id]);

        return $result === true || $result === 1 || $result === '1';
    }
}
