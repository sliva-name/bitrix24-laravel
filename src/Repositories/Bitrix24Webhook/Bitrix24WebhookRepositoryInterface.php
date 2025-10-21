<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Repositories\Bitrix24Webhook;

use Leko\Bitrix24\Models\Bitrix24Webhook;
use Illuminate\Support\Collection;

/**
 * Интерфейс репозитория вебхуков Bitrix24
 *
 * Определяет методы для работы с вебхуками Bitrix24.
 */
interface Bitrix24WebhookRepositoryInterface
{
    /**
     * Найти вебхук по ID.
     *
     * @param int $id Идентификатор вебхука
     * @return Bitrix24Webhook|null
     */
    public function find(int $id): ?Bitrix24Webhook;

    /**
     * Создать запись вебхука.
     *
     * @param array $data Данные вебхука
     * @return Bitrix24Webhook
     */
    public function create(array $data): Bitrix24Webhook;

    /**
     * Обновить вебхук.
     *
     * @param int $id Идентификатор вебхука
     * @param array $data Данные для обновления
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Удалить вебхук.
     *
     * @param int $id Идентификатор вебхука
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Получить ожидающие вебхуки.
     *
     * @param int $limit Максимальное количество записей
     * @return Collection
     */
    public function getPending(int $limit = 100): Collection;

    /**
     * Получить неудачные вебхуки.
     *
     * @param int $limit Максимальное количество записей
     * @return Collection
     */
    public function getFailed(int $limit = 100): Collection;

    /**
     * Получить вебхуки по типу события.
     *
     * @param string $event Тип события
     * @param int $limit Максимальное количество записей
     * @return Collection
     */
    public function getByEvent(string $event, int $limit = 100): Collection;
}
