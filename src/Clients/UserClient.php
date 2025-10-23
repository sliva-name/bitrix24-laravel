<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Leko\Bitrix24\Contracts\UserClientInterface;
use Throwable;

/**
 * Клиент пользователей для Bitrix24
 *
 * Предоставляет методы для работы с пользователями.
 */
class UserClient extends BaseClient implements UserClientInterface
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
        return $this->callMethod('user.get', [
            'filter' => $filter
        ], fn() => $this->serviceBuilder->getMainScope()->call('user.get', ['filter' => $filter])['result']) ?? [];
    }

    /**
     * Получить текущего пользователя.
     *
     * @return array|null
     * @throws Throwable
     */
    public function current(): ?array
    {
        return $this->callMethod('user.current', [], 
            fn() => $this->serviceBuilder->getMainScope()->call('user.current')['result']
        );
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
        $result = $this->callMethod('user.get', [
            'ID' => $id
        ], fn() => $this->serviceBuilder->getMainScope()->call('user.get', ['ID' => $id])['result']);
        
        return is_array($result) ? ($result[0] ?? null) : null;
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
        return $this->callMethod('user.search', [
            'FIND' => $query
        ], fn() => $this->serviceBuilder->getMainScope()->call('user.search', ['FIND' => $query])['result']) ?? [];
    }
}
