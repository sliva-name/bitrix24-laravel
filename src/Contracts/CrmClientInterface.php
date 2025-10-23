<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Contracts;

/**
 * Интерфейс CRM клиента
 */
interface CrmClientInterface extends ClientInterface
{
    /**
     * Получить поля CRM для сущности.
     *
     * @param string $entityType Тип сущности
     * @return array
     */
    public function getFields(string $entityType): array;

    /**
     * Получить список элементов сущности.
     *
     * @param string $entityType Тип сущности
     * @param array $filter Фильтры выборки
     * @param array $select Список полей для выборки
     * @param array $order Сортировка результатов
     * @param int $start Смещение для пагинации
     * @return array
     */
    public function getList(string $entityType, array $filter = [], array $select = [], array $order = [], int $start = 0): array;

    /**
     * Получить сущность по ID.
     *
     * @param string $entityType Тип сущности
     * @param int $id ID записи
     * @return array|null
     */
    public function get(string $entityType, int $id): ?array;

    /**
     * Добавить новую сущность.
     *
     * @param string $entityType Тип сущности
     * @param array $fields Поля новой записи
     * @return int|null
     */
    public function add(string $entityType, array $fields): ?int;

    /**
     * Обновить сущность.
     *
     * @param string $entityType Тип сущности
     * @param int $id ID записи
     * @param array $fields Обновляемые поля
     * @return bool
     */
    public function update(string $entityType, int $id, array $fields): bool;

    /**
     * Удалить сущность.
     *
     * @param string $entityType Тип сущности
     * @param int $id ID записи
     * @return bool
     */
    public function delete(string $entityType, int $id): bool;
}

