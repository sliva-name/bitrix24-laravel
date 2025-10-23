<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Contracts;

/**
 * Базовый интерфейс для всех клиентов Bitrix24
 */
interface ClientInterface
{
    /**
     * Проверить является ли подключение webhook.
     *
     * @return bool
     */
    public function isWebhook(): bool;
}

