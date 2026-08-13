<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Contracts;

use Bitrix24\SDK\Services\CRM\Deal\Result\DealItemResult;

/**
 * Интерфейс клиента сделок
 */
interface DealClientInterface extends ClientInterface
{
    /**
     * Получить список сделок.
     *
     * @param array $filter Фильтры выборки
     * @param array $select Список полей для выборки
     * @param array $order Сортировка результатов
     * @param int $start Смещение для пагинации
     * @return DealItemResult[]
     */
    public function list(array $filter = [], array $select = ['*'], array $order = ['ID' => 'DESC'], int $start = 0): array;

    /**
     * Получить сделку по ID.
     *
     * @param int $id ID записи
     * @return DealItemResult
     */
    public function get(int $id): DealItemResult;

    /**
     * Добавить сделку.
     *
     * @param array $fields Поля новой записи
     * @return int
     */
    public function add(array $fields): int;

    /**
     * Обновить сделку.
     *
     * @param int $id ID записи
     * @param array $fields Обновляемые поля
     * @return bool
     */
    public function update(int $id, array $fields): bool;

    /**
     * Удалить сделку.
     *
     * @param int $id ID записи
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Получить поля записи.
     *
     * @return array
     */
    public function fields(): array;
}
