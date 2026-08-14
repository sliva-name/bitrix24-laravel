<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OAuth-токен Bitrix24.
 *
 * @property int $id
 * @property string $connection
 * @property int|null $user_id
 * @property string $domain
 * @property string $access_token
 * @property string $refresh_token
 * @property int $expires_in
 * @property Carbon|null $expires_at
 * @property array|null $scope
 * @property array|null $metadata
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder<static> active()
 * @method static Builder<static> valid()
 * @method static Builder<static> forConnection(string $connection)
 * @method static Builder<static> forDomain(string $domain)
 */
class Bitrix24Token extends Model
{
    public const EXPIRING_SOON_MINUTES = 5;

    protected $table = 'bitrix24_tokens';

    protected $fillable = [
        'connection',
        'user_id',
        'domain',
        'access_token',
        'refresh_token',
        'expires_in',
        'expires_at',
        'scope',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'scope' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'user_id' => 'integer',
        'expires_in' => 'integer',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model', 'App\\Models\\User')
        );
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function isExpiringSoon(int $minutes = self::EXPIRING_SOON_MINUTES): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->lessThanOrEqualTo(now()->addMinutes($minutes));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForConnection(Builder $query, string $connection): Builder
    {
        return $query->where('connection', $connection);
    }

    public function scopeForDomain(Builder $query, string $domain): Builder
    {
        return $query->where('domain', $domain);
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->active()
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
