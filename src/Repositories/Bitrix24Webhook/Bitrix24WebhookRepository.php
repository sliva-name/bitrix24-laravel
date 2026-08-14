<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Repositories\Bitrix24Webhook;

use Illuminate\Support\Collection;
use Leko\Bitrix24\Models\Bitrix24Webhook;
use Leko\Bitrix24\Repositories\Concerns\UpdatesEloquentRecords;

/**
 * Репозиторий входящих вебхуков Bitrix24.
 */
class Bitrix24WebhookRepository implements Bitrix24WebhookRepositoryInterface
{
    use UpdatesEloquentRecords;

    public function find(int $id): ?Bitrix24Webhook
    {
        return Bitrix24Webhook::query()->find($id);
    }

    public function create(array $data): Bitrix24Webhook
    {
        return Bitrix24Webhook::query()->create($data);
    }

    public function getPending(int $limit = 100): Collection
    {
        return Bitrix24Webhook::query()
            ->pending()
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    public function getFailed(int $limit = 100): Collection
    {
        return Bitrix24Webhook::query()
            ->failed()
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function getByEvent(string $event, int $limit = 100): Collection
    {
        return Bitrix24Webhook::query()
            ->forEvent($event)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
