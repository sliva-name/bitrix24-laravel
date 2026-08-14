<?php

declare(strict_types=1);

namespace Leko\Bitrix24;

use Bitrix24\SDK\Events\AuthTokenRenewedEvent;
use Bitrix24\SDK\Services\ServiceBuilder;
use Bitrix24\SDK\Services\ServiceBuilderFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Leko\Bitrix24\Clients\BaseClient;
use Leko\Bitrix24\Clients\CompanyClient;
use Leko\Bitrix24\Clients\ContactClient;
use Leko\Bitrix24\Clients\CrmClient;
use Leko\Bitrix24\Clients\DealClient;
use Leko\Bitrix24\Clients\LeadClient;
use Leko\Bitrix24\Clients\ListClient;
use Leko\Bitrix24\Clients\TaskClient;
use Leko\Bitrix24\Clients\UserClient;
use Leko\Bitrix24\Contracts\Bitrix24ServiceInterface;
use Leko\Bitrix24\Exceptions\AuthenticationException;
use Leko\Bitrix24\Exceptions\UnknownClientException;
use Leko\Bitrix24\Support\ConnectionConfig;
use Leko\Bitrix24\Support\Domain;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Главный сервис Bitrix24.
 */
class Bitrix24Service implements Bitrix24ServiceInterface
{
    /**
     * @var array<string, class-string<BaseClient>>
     */
    private const DEFAULT_CLIENTS = [
        'crm' => CrmClient::class,
        'leads' => LeadClient::class,
        'contacts' => ContactClient::class,
        'companies' => CompanyClient::class,
        'deals' => DealClient::class,
        'tasks' => TaskClient::class,
        'users' => UserClient::class,
        'lists' => ListClient::class,
    ];

    private ?ServiceBuilder $serviceBuilder = null;
    private string $connection;
    private ?int $userId = null;

    /**
     * @var array<string, class-string<BaseClient>>
     */
    private static array $customClients = [];

    public function __construct(
        private readonly TokenManager $tokenManager
    ) {
        $this->connection = (string) config('bitrix24.default', 'main');
    }

    /**
     * @param class-string<BaseClient> $clientClass
     */
    public static function registerClient(string $name, string $clientClass): void
    {
        if (!is_subclass_of($clientClass, BaseClient::class)) {
            throw new InvalidArgumentException("Класс {$clientClass} должен наследовать " . BaseClient::class);
        }

        self::$customClients[$name] = $clientClass;
    }

    /**
     * Сбросить кастомные клиенты (полезно в тестах).
     */
    public static function flushCustomClients(): void
    {
        self::$customClients = [];
    }

    /**
     * @template TClient of BaseClient
     * @param class-string<TClient> $defaultClass
     * @return TClient
     */
    protected function makeClient(string $name, string $defaultClass): BaseClient
    {
        $clientClass = self::$customClients[$name] ?? $defaultClass;

        return new $clientClass($this->sdk());
    }

    public function crm(): CrmClient
    {
        return $this->makeClient('crm', CrmClient::class);
    }

    public function leads(): LeadClient
    {
        return $this->makeClient('leads', LeadClient::class);
    }

    public function contacts(): ContactClient
    {
        return $this->makeClient('contacts', ContactClient::class);
    }

    public function companies(): CompanyClient
    {
        return $this->makeClient('companies', CompanyClient::class);
    }

    public function deals(): DealClient
    {
        return $this->makeClient('deals', DealClient::class);
    }

    public function tasks(): TaskClient
    {
        return $this->makeClient('tasks', TaskClient::class);
    }

    public function users(): UserClient
    {
        return $this->makeClient('users', UserClient::class);
    }

    public function lists(): ListClient
    {
        return $this->makeClient('lists', ListClient::class);
    }

    public function client(string $name): BaseClient
    {
        $class = self::$customClients[$name] ?? self::DEFAULT_CLIENTS[$name] ?? null;

        if ($class === null) {
            throw new UnknownClientException("Клиент «{$name}» не зарегистрирован.");
        }

        return $this->makeClient($name, $class);
    }

    public function sdk(): ServiceBuilder
    {
        return $this->getServiceBuilder();
    }

    public function getAuthorizationUrl(array $scopes = [], ?string $state = null): string
    {
        $config = ConnectionConfig::load($this->connection);
        $state ??= Str::random(32);

        $params = [
            'client_id' => $config->clientId(),
            'response_type' => 'code',
            'redirect_uri' => $config->redirectUri(),
            'state' => $state,
        ];

        if ($scopes === []) {
            $scopes = $config->scopes();
        }

        if ($scopes !== []) {
            $params['scope'] = implode(',', $scopes);
        }

        return 'https://' . $config->domain() . '/oauth/authorize/?' . http_build_query($params);
    }

    public function handleCallback(string $code): array
    {
        $payload = $this->tokenManager->exchangeAuthorizationCode($code, $this->connection);
        $token = $this->tokenManager->storeToken($payload, $this->userId, $this->connection);

        return [
            'token_id' => $token->id,
            'domain' => $token->domain,
            'expires_at' => $token->expires_at,
        ];
    }

    public function setConnection(string $connection): self
    {
        $this->connection = $connection;
        $this->serviceBuilder = null;

        return $this;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        $this->serviceBuilder = null;

        return $this;
    }

    public function hasValidToken(?int $userId = null): bool
    {
        return $this->tokenManager->getToken($userId ?? $this->userId, $this->connection) !== null;
    }

    private function getServiceBuilder(): ServiceBuilder
    {
        if ($this->serviceBuilder instanceof ServiceBuilder) {
            return $this->serviceBuilder;
        }

        $config = ConnectionConfig::load($this->connection);
        $factory = new ServiceBuilderFactory($this->createEventDispatcher(), $this->createLogger());

        if ($config->isWebhook()) {
            return $this->serviceBuilder = $factory->initFromWebhook($config->webhookUrl());
        }

        $token = $this->tokenManager->getToken($this->userId, $this->connection);

        if ($token === null) {
            throw new AuthenticationException('Не найдены валидные учетные данные Bitrix24. Пожалуйста, авторизуйтесь.');
        }

        return $this->serviceBuilder = $factory->init(
            $this->tokenManager->getApplicationProfile($this->connection),
            $this->tokenManager->toAuthToken($token),
            Domain::normalize((string) ($token->domain ?: $config->domain())),
            $config->oauthServer()
        );
    }

    private function createEventDispatcher(): EventDispatcher
    {
        $dispatcher = new EventDispatcher();

        $dispatcher->addListener(
            AuthTokenRenewedEvent::class,
            function (AuthTokenRenewedEvent $event): void {
                $this->tokenManager->persistRenewedToken(
                    $event->getRenewedToken(),
                    $this->userId,
                    $this->connection
                );
            }
        );

        return $dispatcher;
    }

    private function createLogger(): LoggerInterface
    {
        if (!config('bitrix24.logging.enabled')) {
            return new NullLogger();
        }

        return Log::channel(config('bitrix24.logging.channel', 'daily'));
    }
}
