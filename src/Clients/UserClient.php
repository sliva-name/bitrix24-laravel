<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Bitrix24\SDK\Services\User\Result\UserItemResult;
use Leko\Bitrix24\Contracts\UserClientInterface;

/**
 * Клиент пользователей Bitrix24.
 */
class UserClient extends BaseClient implements UserClientInterface
{
    public function list(array $filter = []): array
    {
        return $this->callMethod(
            'user.get',
            ['filter' => $filter],
            fn () => $this->serviceBuilder->getUserScope()->user()->get(['ID' => 'ASC'], $filter)->getUsers()
        ) ?? [];
    }

    public function current(): UserItemResult
    {
        return $this->callMethod(
            'user.current',
            [],
            fn () => $this->serviceBuilder->getUserScope()->user()->current()->user()
        );
    }

    public function get(int $id): ?UserItemResult
    {
        $users = $this->callMethod(
            'user.get',
            ['ID' => $id],
            fn () => $this->serviceBuilder->getUserScope()->user()->get(['ID' => 'ASC'], ['ID' => $id])->getUsers()
        );

        if (!is_array($users) || $users === []) {
            return null;
        }

        $user = $users[0];

        return $user instanceof UserItemResult ? $user : null;
    }

    public function search(string $query): array
    {
        return $this->callMethod(
            'user.search',
            ['FIND' => $query],
            fn () => $this->serviceBuilder->getUserScope()->user()->search(['FIND' => $query])->getUsers()
        ) ?? [];
    }
}
