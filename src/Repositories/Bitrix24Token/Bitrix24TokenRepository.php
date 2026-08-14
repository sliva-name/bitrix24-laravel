<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Repositories\Bitrix24Token;

use Illuminate\Support\Collection;
use Leko\Bitrix24\Models\Bitrix24Token;
use Leko\Bitrix24\Repositories\Concerns\UpdatesEloquentRecords;

/**
 * Репозиторий OAuth-токенов Bitrix24.
 */
class Bitrix24TokenRepository implements Bitrix24TokenRepositoryInterface
{
    use UpdatesEloquentRecords;

    public function find(int $id): ?Bitrix24Token
    {
        return Bitrix24Token::query()->find($id);
    }

    public function findValidToken(?int $userId, string $connection = 'main'): ?Bitrix24Token
    {
        return Bitrix24Token::query()
            ->valid()
            ->forConnection($connection)
            ->where('user_id', $userId)
            ->first();
    }

    public function findActiveToken(?int $userId, string $connection = 'main'): ?Bitrix24Token
    {
        return Bitrix24Token::query()
            ->active()
            ->forConnection($connection)
            ->where('user_id', $userId)
            ->latest('updated_at')
            ->first();
    }

    public function findByDomain(string $domain, string $connection = 'main'): ?Bitrix24Token
    {
        return Bitrix24Token::query()
            ->forConnection($connection)
            ->forDomain($domain)
            ->valid()
            ->first();
    }

    public function upsert(array $data): Bitrix24Token
    {
        return Bitrix24Token::query()->updateOrCreate(
            [
                'connection' => $data['connection'] ?? 'main',
                'user_id' => $data['user_id'] ?? null,
                'domain' => $data['domain'],
            ],
            [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_in' => $data['expires_in'],
                'expires_at' => $data['expires_at'],
                'scope' => $data['scope'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]
        );
    }

    public function getAllForUser(int $userId): Collection
    {
        return Bitrix24Token::query()
            ->where('user_id', $userId)
            ->get();
    }

    public function getExpiredTokens(): Collection
    {
        return Bitrix24Token::query()
            ->active()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();
    }

    public function getExpiringSoonTokens(): Collection
    {
        return Bitrix24Token::query()
            ->active()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->addMinutes(Bitrix24Token::EXPIRING_SOON_MINUTES))
            ->where('expires_at', '>', now())
            ->get();
    }

    public function deactivate(int $id): bool
    {
        return $this->update($id, ['is_active' => false]);
    }

    public function activate(int $id): bool
    {
        return $this->update($id, ['is_active' => true]);
    }
}
