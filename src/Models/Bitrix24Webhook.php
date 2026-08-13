<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Модель вебхука Bitrix24
 *
 * Хранит входящие вебхуки от Bitrix24 для обработки.
 *
 * @property int $id
 * @property string $event
 * @property string $handler
 * @property string $domain
 * @property array $payload
 * @property string $status
 * @property string|null $error_message
 * @property int $attempts
 * @property Carbon|null $processed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Bitrix24Webhook extends Model
{
    protected $table = 'bitrix24_webhooks';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'event',
        'handler',
        'domain',
        'payload',
        'status',
        'error_message',
        'attempts',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
        'attempts' => 'integer',
    ];

    /**
     * Отметить вебхук как обрабатываемый.
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'attempts' => $this->attempts + 1,
        ]);
    }

    /**
     * Отметить вебхук как завершенный.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now(),
            'error_message' => null,
        ]);
    }

    /**
     * Отметить вебхук как неудачный.
     *
     * @param string $errorMessage Сообщение об ошибке
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);
    }

    /**
     * Scope для ожидающих вебхуков.
     *
     * @param Builder $query Запрос
     * @return Builder
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope для неудачных вебхуков.
     *
     * @param Builder $query Запрос
     * @return Builder
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope для завершенных вебхуков.
     *
     * @param Builder $query Запрос
     * @return Builder
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope по типу события.
     *
     * @param Builder $query Запрос
     * @param string $event Тип события
     * @return Builder
     */
    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    /**
     * Scope по домену.
     *
     * @param Builder $query Запрос
     * @param string $domain Домен Bitrix24
     * @return Builder
     */
    public function scopeForDomain(Builder $query, string $domain): Builder
    {
        return $query->where('domain', $domain);
    }
}
