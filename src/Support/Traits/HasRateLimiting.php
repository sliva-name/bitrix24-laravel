<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Support\Traits;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Ограничение частоты запросов в клиентах.
 */
trait HasRateLimiting
{
    protected int $maxAttempts = 60;
    protected int $decaySeconds = 60;

    public function rateLimit(int $attempts, int $seconds): self
    {
        $this->maxAttempts = $attempts;
        $this->decaySeconds = $seconds;

        return $this;
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    protected function rateLimited(string $key, callable $callback): mixed
    {
        $limitKey = $this->rateLimitKey($key);

        if (RateLimiter::tooManyAttempts($limitKey, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($limitKey);

            throw new RuntimeException(
                "Превышен лимит запросов API. Повторите через {$seconds} секунд."
            );
        }

        RateLimiter::hit($limitKey, $this->decaySeconds);

        return $callback();
    }

    protected function clearRateLimit(string $key): void
    {
        RateLimiter::clear($this->rateLimitKey($key));
    }

    private function rateLimitKey(string $key): string
    {
        return Str::slug("bitrix24-rate-limit-{$key}");
    }
}
