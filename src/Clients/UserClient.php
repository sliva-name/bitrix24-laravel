<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Throwable;

/**
 * Клиент пользователей для Bitrix24
 *
 * Предоставляет методы для работы с пользователями.
 */
class UserClient extends BaseClient
{
    /**
     * Получить список пользователей.
     *
     * @param array $filter Фильтры выборки
     * @return array
     * @throws Throwable
     */
    public function list(array $filter = []): array
    {
        try {
            return $this->callMethod('user.get', [
                'filter' => $filter
            ], fn() => $this->serviceBuilder->getMainScope()->call('user.get', ['filter' => $filter])['result']) ?? [];
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return [];
        }
    }

    /**
     * Получить текущего пользователя.
     *
     * @return array|null
     * @throws Throwable
     */
    public function current(): ?array
    {
        try {
            return $this->callMethod('user.current', [], 
                fn() => $this->serviceBuilder->getMainScope()->call('user.current')['result']
            );
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return null;
        }
    }

    /**
     * Получить пользователя по ID.
     *
     * @param int $id ID пользователя
     * @return array|null
     * @throws Throwable
     */
    public function get(int $id): ?array
    {
        try {
            $result = $this->callMethod('user.get', [
                'ID' => $id
            ], fn() => $this->serviceBuilder->getMainScope()->call('user.get', ['ID' => $id])['result']);
            
            return is_array($result) ? ($result[0] ?? null) : null;
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return null;
        }
    }

    /**
     * Поиск пользователей.
     *
     * @param string $query Поисковый запрос
     * @return array
     * @throws Throwable
     */
    public function search(string $query): array
    {
        try {
            return $this->callMethod('user.search', [
                'FIND' => $query
            ], fn() => $this->serviceBuilder->getMainScope()->call('user.search', ['FIND' => $query])['result']) ?? [];
        } catch (Throwable $e) {
            $this->handleException($e, __METHOD__);
            return [];
        }
    }
}
