<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Contracts;

/**
 * Интерфейс клиента задач
 */
interface TaskClientInterface extends ClientInterface
{
    /**
     * Получить список задач.
     *
     * @param array $filter Фильтры выборки
     * @param array $select Список полей для выборки
     * @param array $order Сортировка результатов
     * @param int $start Смещение для пагинации
     * @return array
     */
    public function list(array $filter = [], array $select = ['*'], array $order = ['ID' => 'DESC'], int $start = 0): array;

    /**
     * Получить задачу по ID.
     *
     * @param int $id ID задачи
     * @return array|null
     */
    public function get(int $id): ?array;

    /**
     * Добавить новую задачу.
     *
     * @param array $fields Поля новой задачи
     * @return int|null
     */
    public function add(array $fields): ?int;

    /**
     * Обновить задачу.
     *
     * @param int $id ID задачи
     * @param array $fields Обновляемые поля
     * @return bool
     */
    public function update(int $id, array $fields): bool;

    /**
     * Удалить задачу.
     *
     * @param int $id ID задачи
     * @return bool
     */
    public function delete(int $id): bool;
}

