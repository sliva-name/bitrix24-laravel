<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Events;

use Throwable;

/**
 * Событие неудачного вызова API
 */
class ApiCallFailedEvent
{
    /**
     * Создать новое событие.
     *
     * @param string $method Метод API
     * @param array $params Параметры запроса
     * @param Throwable $exception Исключение
     * @param float $duration Длительность в секундах
     */
    public function __construct(
        public readonly string $method,
        public readonly array $params,
        public readonly Throwable $exception,
        public readonly float $duration
    ) {
    }
}

