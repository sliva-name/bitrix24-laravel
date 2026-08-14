<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Events;

/**
 * Успешный вызов Bitrix24 API.
 */
final readonly class ApiCallEvent
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public string $method,
        public array $params,
        public array|object|int|bool|string|null $result,
        public float $duration,
        public bool $isWebhook
    ) {
    }
}
