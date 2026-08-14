<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Repositories\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Общие update/delete для Eloquent-репозиториев.
 */
trait UpdatesEloquentRecords
{
    abstract public function find(int $id): ?Model;

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $record = $this->find($id);

        return $record !== null && $record->update($data);
    }

    public function delete(int $id): bool
    {
        $record = $this->find($id);

        return $record !== null && (bool) $record->delete();
    }
}
