<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Events;

/**
 * Событие вызова API
 */
class ApiCallEvent
{
    /**
     * Создать новое событие.
     *
     * @param string $method Метод API
     * @param array $params Параметры запроса
     * @param array|object|int|bool|string|null $result Результат выполнения
     * @param float $duration Длительность в секундах
     * @param bool $isWebhook Является ли webhook соединением
     */
    public function __construct(
        public readonly string $method,
        public readonly array $params,
        public readonly array|object|int|bool|string|null $result,
        public readonly float $duration,
        public readonly bool $isWebhook
    ) {
    }
}

