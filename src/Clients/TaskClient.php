<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Bitrix24\SDK\Services\Task\Result\TaskItemResult;
use Leko\Bitrix24\Contracts\TaskClientInterface;

/**
 * Клиент задач Bitrix24.
 */
class TaskClient extends BaseClient implements TaskClientInterface
{
    public function list(array $filter = [], array $select = ['*'], array $order = ['ID' => 'DESC'], int $start = 0): array
    {
        return $this->callMethod('tasks.task.list', [
            'filter' => $filter,
            'select' => $select,
            'order' => $order,
            'start' => $start,
        ], fn () => $this->serviceBuilder->getTaskScope()->task()->list($order, $filter, $select, $start)->getTasks()) ?? [];
    }

    public function get(int $id): TaskItemResult
    {
        return $this->callMethod(
            'tasks.task.get',
            ['id' => $id],
            fn () => $this->serviceBuilder->getTaskScope()->task()->get($id)->task()
        );
    }

    public function add(array $fields): int
    {
        return $this->callMethod(
            'tasks.task.add',
            ['fields' => $fields],
            fn () => $this->serviceBuilder->getTaskScope()->task()->add($fields)->getId()
        );
    }

    public function update(int $id, array $fields): bool
    {
        return $this->isSuccessful($this->callMethod(
            'tasks.task.update',
            ['id' => $id, 'fields' => $fields],
            fn () => $this->serviceBuilder->getTaskScope()->task()->update($id, $fields)->isSuccess()
        ));
    }

    public function delete(int $id): bool
    {
        return $this->isSuccessful($this->callMethod(
            'tasks.task.delete',
            ['id' => $id],
            fn () => $this->serviceBuilder->getTaskScope()->task()->delete($id)->isSuccess()
        ));
    }
}
