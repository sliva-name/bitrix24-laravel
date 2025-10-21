<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Repositories\Bitrix24Webhook;

use Leko\Bitrix24\Models\Bitrix24Webhook;
use Illuminate\Support\Collection;

/**
 * Репозиторий вебхуков Bitrix24
 *
 * Обрабатывает все операции с базой данных, связанные с вебхуками Bitrix24.
 */
class Bitrix24WebhookRepository implements Bitrix24WebhookRepositoryInterface
{
    /**
     * Найти вебхук по ID.
     *
     * @param int $id Идентификатор вебхука
     * @return Bitrix24Webhook|null
     */
    public function find(int $id): ?Bitrix24Webhook
    {
        return Bitrix24Webhook::find($id);
    }

    /**
     * Создать запись вебхука.
     *
     * @param array $data Данные вебхука
     * @return Bitrix24Webhook
     */
    public function create(array $data): Bitrix24Webhook
    {
        return Bitrix24Webhook::create($data);
    }

    /**
     * Обновить вебхук.
     *
     * @param int $id Идентификатор вебхука
     * @param array $data Данные для обновления
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $webhook = $this->find($id);

        if (!$webhook) {
            return false;
        }

        return $webhook->update($data);
    }

    /**
     * Удалить вебхук.
     *
     * @param int $id Идентификатор вебхука
     * @return bool
     */
    public function delete(int $id): bool
    {
        $webhook = $this->find($id);

        if (!$webhook) {
            return false;
        }

        return $webhook->delete();
    }

    /**
     * Получить ожидающие вебхуки.
     *
     * @param int $limit Максимальное количество записей
     * @return Collection
     */
    public function getPending(int $limit = 100): Collection
    {
        return Bitrix24Webhook::query()
            ->pending()
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить неудачные вебхуки.
     *
     * @param int $limit Максимальное количество записей
     * @return Collection
     */
    public function getFailed(int $limit = 100): Collection
    {
        return Bitrix24Webhook::query()
            ->failed()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить вебхуки по типу события.
     *
     * @param string $event Тип события
     * @param int $limit Максимальное количество записей
     * @return Collection
     */
    public function getByEvent(string $event, int $limit = 100): Collection
    {
        return Bitrix24Webhook::query()
            ->forEvent($event)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
