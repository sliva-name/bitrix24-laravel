<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Support\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * Кеширование результатов запросов в клиентах.
 */
trait HasCaching
{
    protected int $cacheTtl = 3600;
    protected string $cachePrefix = 'bitrix24';

    public function cacheTtl(int $seconds): self
    {
        $this->cacheTtl = $seconds;

        return $this;
    }

    public function cachePrefix(string $prefix): self
    {
        $this->cachePrefix = $prefix;

        return $this;
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    protected function cached(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return Cache::remember($this->cacheKey($key), $ttl ?? $this->cacheTtl, $callback);
    }

    protected function forgetCache(string $key): bool
    {
        return Cache::forget($this->cacheKey($key));
    }

    protected function flushCache(): void
    {
        try {
            Cache::tags([$this->cachePrefix])->flush();
        } catch (\BadMethodCallException) {
            // Драйвер кеша не поддерживает теги.
        }
    }

    private function cacheKey(string $key): string
    {
        return "{$this->cachePrefix}:{$key}";
    }
}
