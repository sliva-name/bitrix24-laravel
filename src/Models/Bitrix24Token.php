<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель токена Bitrix24
 *
 * Хранит OAuth токены для интеграции с Bitrix24.
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
 */
class Bitrix24Token extends Model
{
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

    /**
     * Получить пользователя, владеющего токеном.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model', 'App\\Models\\User')
        );
    }

    /**
     * Проверить истек ли токен.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Проверить скоро ли истечет токен (в течение 5 минут).
     *
     * @return bool
     */
    public function isExpiringSoon(int $minutes = 5): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->getTimestamp() <= Carbon::now()->copy()->addMinutes($minutes)->getTimestamp();
    }

    /**
     * Scope для активных токенов.
     *
     * @param Builder $query Запрос
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для токенов конкретного подключения.
     *
     * @param Builder $query Запрос
     * @param string $connection Название подключения
     * @return Builder
     */
    public function scopeForConnection(Builder $query, string $connection): Builder
    {
        return $query->where('connection', $connection);
    }

    /**
     * Scope для токенов конкретного домена.
     *
     * @param Builder $query Запрос
     * @param string $domain Домен Bitrix24
     * @return Builder
     */
    public function scopeForDomain(Builder $query, string $domain): Builder
    {
        return $query->where('domain', $domain);
    }

    /**
     * Scope для валидных (активных и неистекших) токенов.
     *
     * @param Builder $query Запрос
     * @return Builder
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
