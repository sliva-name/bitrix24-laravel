<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Support\Traits;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Trait для добавления rate limiting в клиенты
 */
trait HasRateLimiting
{
    /**
     * Максимальное количество попыток.
     *
     * @var int
     */
    protected int $maxAttempts = 60;

    /**
     * Время окна в секундах.
     *
     * @var int
     */
    protected int $decaySeconds = 60;

    /**
     * Установить лимит попыток.
     *
     * @param int $attempts Количество попыток
     * @param int $seconds Секунды
     * @return self
     */
    public function rateLimit(int $attempts, int $seconds): self
    {
        $this->maxAttempts = $attempts;
        $this->decaySeconds = $seconds;
        return $this;
    }

    /**
     * Выполнить с rate limiting.
     *
     * @param string $key Ключ для rate limit
     * @param callable $callback Функция для выполнения
     * @return mixed
     * @throws RuntimeException
     */
    protected function rateLimited(string $key, callable $callback): mixed
    {
        $key = Str::slug("bitrix24-rate-limit-{$key}");

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            throw new RuntimeException(
                "Превышен лимит запросов API. Повторите через {$seconds} секунд."
            );
        }

        RateLimiter::hit($key, $this->decaySeconds);

        return $callback();
    }

    /**
     * Очистить rate limit счетчик.
     *
     * @param string $key Ключ
     * @return void
     */
    protected function clearRateLimit(string $key): void
    {
        $key = Str::slug("bitrix24-rate-limit-{$key}");
        RateLimiter::clear($key);
    }
}

