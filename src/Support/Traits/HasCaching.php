<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Support\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * Trait для добавления кеширования в клиенты
 */
trait HasCaching
{
    /**
     * TTL кеша по умолчанию (в секундах).
     *
     * @var int
     */
    protected int $cacheTtl = 3600;

    /**
     * Префикс ключей кеша.
     *
     * @var string
     */
    protected string $cachePrefix = 'bitrix24';

    /**
     * Установить TTL кеша.
     *
     * @param int $seconds Секунды
     * @return self
     */
    public function cacheTtl(int $seconds): self
    {
        $this->cacheTtl = $seconds;
        return $this;
    }

    /**
     * Установить префикс кеша.
     *
     * @param string $prefix Префикс
     * @return self
     */
    public function cachePrefix(string $prefix): self
    {
        $this->cachePrefix = $prefix;
        return $this;
    }

    /**
     * Выполнить с кешированием.
     *
     * @param string $key Ключ кеша
     * @param callable $callback Функция для выполнения
     * @param int|null $ttl TTL в секундах
     * @return mixed
     */
    protected function cached(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = "{$this->cachePrefix}:{$key}";
        $ttl = $ttl ?? $this->cacheTtl;

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Очистить кеш по ключу.
     *
     * @param string $key Ключ кеша
     * @return bool
     */
    protected function forgetCache(string $key): bool
    {
        $cacheKey = "{$this->cachePrefix}:{$key}";
        return Cache::forget($cacheKey);
    }

    /**
     * Очистить весь кеш по префиксу.
     *
     * @return void
     */
    protected function flushCache(): void
    {
        Cache::tags([$this->cachePrefix])->flush();
    }
}

