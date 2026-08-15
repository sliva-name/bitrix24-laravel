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

    /**
     * Хост портала из client_endpoint OAuth-ответа (`https://portal.bitrix24.ru/rest/`).
     */
    public static function fromClientEndpoint(?string $endpoint): ?string
    {
        if ($endpoint === null || trim($endpoint) === '') {
            return null;
        }

        $host = parse_url($endpoint, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return null;
        }

        $normalized = self::normalize($host);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * Поле `domain` в JSON oauth/token — это сервер авторизации, не портал.
     */
    public static function isAuthorizationServer(string $domain): bool
    {
        $host = strtolower(self::normalize($domain));

        return $host === 'oauth.bitrix.info' || str_starts_with($host, 'oauth.bitrix.');
    }
}
