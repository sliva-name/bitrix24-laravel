<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Bitrix24\SDK\Services\User\Result\UserItemResult;
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
     * @return UserItemResult[]
     * @throws Throwable
     */
    public function list(array $filter = []): array
    {
        return $this->callMethod('user.get', [
            'filter' => $filter
        ], fn() => $this->serviceBuilder->getUserScope()->user()->get(['ID' => 'ASC'], $filter)->getUsers()) ?? [];
    }

    /**
     * Получить текущего пользователя.
     *
     * @return UserItemResult
     * @throws Throwable
     */
    public function current(): UserItemResult
    {
        return $this->callMethod('user.current', [],
            fn() => $this->serviceBuilder->getUserScope()->user()->current()->user()
        );
    }

    /**
     * Получить пользователя по ID.
     *
     * @param int $id ID пользователя
     * @return UserItemResult|null
     * @throws Throwable
     */
    public function get(int $id): ?UserItemResult
    {
        $users = $this->callMethod('user.get', [
            'ID' => $id
        ], fn() => $this->serviceBuilder->getUserScope()->user()->get(['ID' => 'ASC'], ['ID' => $id])->getUsers());

        if (!is_array($users) || $users === []) {
            return null;
        }

        $user = $users[0];

        return $user instanceof UserItemResult ? $user : null;
    }

    /**
     * Поиск пользователей.
     *
     * @param string $query Поисковый запрос
     * @return UserItemResult[]
     * @throws Throwable
     */
    public function search(string $query): array
    {
        return $this->callMethod('user.search', [
            'FIND' => $query
        ], fn() => $this->serviceBuilder->getUserScope()->user()->search(['FIND' => $query])->getUsers()) ?? [];
    }
}
