<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Support;

use Bitrix24\SDK\Core\Credentials\DefaultOAuthServerUrl;
use Leko\Bitrix24\Exceptions\UnknownConnectionException;

/**
 * Конфигурация именованного подключения Bitrix24.
 *
 * @phpstan-type ConnectionArray array{
 *     type?: string,
 *     domain?: string|null,
 *     client_id?: string|null,
 *     client_secret?: string|null,
 *     redirect_uri?: string|null,
 *     webhook_url?: string|null,
 *     scope?: string|null,
 *     oauth_server?: string|null
 * }
 */
final readonly class ConnectionConfig
{
    /**
     * @param ConnectionArray $raw
     */
    private function __construct(
        public string $name,
        private array $raw
    ) {
    }

    /**
     * Загрузить подключение из config/bitrix24.php.
     */
    public static function load(string $name): self
    {
        $config = config("bitrix24.connections.{$name}");

        if (!is_array($config)) {
            throw new UnknownConnectionException("Подключение Bitrix24 «{$name}» не настроено.");
        }

        /** @var ConnectionArray $config */
        return new self($name, $config);
    }

    public function type(): string
    {
        return (string) ($this->raw['type'] ?? 'oauth');
    }

    public function domain(): string
    {
        return Domain::normalize((string) ($this->raw['domain'] ?? ''));
    }

    public function clientId(): string
    {
        return (string) ($this->raw['client_id'] ?? '');
    }

    public function clientSecret(): string
    {
        return (string) ($this->raw['client_secret'] ?? '');
    }

    public function redirectUri(): string
    {
        return (string) ($this->raw['redirect_uri'] ?? '');
    }

    public function webhookUrl(): string
    {
        return (string) ($this->raw['webhook_url'] ?? '');
    }

    public function isWebhook(): bool
    {
        return $this->type() === 'webhook' && $this->webhookUrl() !== '';
    }

    public function scope(): string
    {
        return (string) ($this->raw['scope'] ?? '');
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', $this->scope())),
            static fn (string $scope): bool => $scope !== ''
        ));
    }

    /**
     * Базовый URL OAuth-сервера со слэшем на конце.
     */
    public function oauthServer(): string
    {
        $server = (string) ($this->raw['oauth_server'] ?? DefaultOAuthServerUrl::default());

        return rtrim($server, '/') . '/';
    }

    public function oauthTokenUrl(): string
    {
        return $this->oauthServer() . 'oauth/token/';
    }
}
