<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Events;

use Throwable;

/**
 * Неудачный вызов Bitrix24 API.
 */
final readonly class ApiCallFailedEvent
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public string $method,
        public array $params,
        public Throwable $exception,
        public float $duration
    ) {
    }
}
