<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Support;

/**
 * Нормализация домена портала Bitrix24.
 */
final class Domain
{
    /**
     * Убрать схему и завершающий слэш.
     */
    public static function normalize(string $domain): string
    {
        $domain = trim($domain);
        $domain = preg_replace('#^https?://#i', '', $domain) ?? $domain;

        return rtrim($domain, '/');
    }
}
