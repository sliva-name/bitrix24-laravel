<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Leko\Bitrix24\Contracts\TaskClientInterface;
use Throwable;

/**
 * Клиент задач для Bitrix24
 *
 * Предоставляет методы для работы с задачами.
 */
class TaskClient extends BaseClient implements TaskClientInterface
{
    /**
     * Получить список задач.
     *
     * @param array $filter Фильтры выборки
     * @param array $select Список полей для выборки
     * @param array $order Сортировка результатов
     * @param int $start Смещение для пагинации
     * @return array
     * @throws Throwable
     */
    public function list(array $filter = [], array $select = ['*'], array $order = ['ID' => 'DESC'], int $start = 0): array
    {
        return $this->callMethod('tasks.task.list', [
            'filter' => $filter,
            'select' => $select,
            'order' => $order,
            'start' => $start,
        ], fn() => $this->serviceBuilder->getTasksScope()->list($filter, $select, $order, $start)->getTasks()) ?? [];
    }

    /**
     * Получить задачу по ID.
     *
     * @param int $id ID задачи
     * @return array|null
     * @throws Throwable
     */
    public function get(int $id): ?array
    {
        return $this->callMethod('tasks.task.get', [
            'id' => $id
        ], fn() => $this->serviceBuilder->getTasksScope()->get($id)->task());
    }

    /**
     * Добавить новую задачу.
     *
     * @param array $fields Поля новой задачи
     * @return int|null
     * @throws Throwable
     */
    public function add(array $fields): ?int
    {
        return $this->callMethod('tasks.task.add', [
            'fields' => $fields
        ], fn() => $this->serviceBuilder->getTasksScope()->add($fields)->getId());
    }

    /**
     * Обновить задачу.
     *
     * @param int $id ID задачи
     * @param array $fields Обновляемые поля
     * @return bool
     * @throws Throwable
     */
    public function update(int $id, array $fields): bool
    {
        $result = $this->callMethod('tasks.task.update', [
            'id' => $id,
            'fields' => $fields
        ], fn() => $this->serviceBuilder->getTasksScope()->update($id, $fields)->isSuccess());
        
        return $result === true;
    }

    /**
     * Удалить задачу.
     *
     * @param int $id ID задачи
     * @return bool
     * @throws Throwable
     */
    public function delete(int $id): bool
    {
        $result = $this->callMethod('tasks.task.delete', [
            'id' => $id
        ], fn() => $this->serviceBuilder->getTasksScope()->delete($id)->isSuccess());
        
        return $result === true;
    }
}
