<?php

declare(strict_types=1);

namespace Leko\Bitrix24;

use Bitrix24\SDK\Core\Credentials\DefaultOAuthServerUrl;
use Bitrix24\SDK\Events\AuthTokenRenewedEvent;
use Bitrix24\SDK\Services\ServiceBuilder;
use Bitrix24\SDK\Services\ServiceBuilderFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Главный сервис Bitrix24
 *
 * Предоставляет доступ ко всем клиентам API Bitrix24 и методам аутентификации.
 */
class Bitrix24Service implements Bitrix24ServiceInterface
{
    private ?ServiceBuilder $serviceBuilder = null;
    private string $connection = 'main';
    private ?int $userId = null;

    /**
     * Мапинг кастомных классов клиентов.
     *
     * @var array<string, class-string>
     */
    private static array $customClients = [];

    /**
     * Создать новый экземпляр Bitrix24Service.
     *
     * @param TokenManager $tokenManager Менеджер управления токенами
     */
    public function __construct(
        private readonly TokenManager $tokenManager
    ) {
        $this->connection = (string) config('bitrix24.default', 'main');
    }

    /**
     * Зарегистрировать кастомный клиент.
     *
     * @param string $name Название клиента
     * @param class-string $clientClass Класс клиента
     * @return void
     */
    public static function registerClient(string $name, string $clientClass): void
    {
        self::$customClients[$name] = $clientClass;
    }

    /**
     * Создать экземпляр клиента по имени.
     *
     * @template TClient of BaseClient
     * @param string $name Название клиента
     * @param class-string<TClient> $defaultClass Класс клиента по умолчанию
     * @return TClient
     */
    protected function makeClient(string $name, string $defaultClass): BaseClient
    {
        $clientClass = self::$customClients[$name] ?? $defaultClass;

        return new $clientClass($this->sdk());
    }

    /**
     * Получить CRM клиент.
     *
     * @return CrmClient
     */
    public function crm(): CrmClient
    {
        return $this->makeClient('crm', CrmClient::class);
    }

    /**
     * Получить клиент лидов.
     *
     * @return LeadClient
     */
    public function leads(): LeadClient
    {
        return $this->makeClient('leads', LeadClient::class);
    }

    /**
     * Получить клиент контактов.
     *
     * @return ContactClient
     */
    public function contacts(): ContactClient
    {
        return $this->makeClient('contacts', ContactClient::class);
    }

    /**
     * Получить клиент компаний.
     *
     * @return CompanyClient
     */
    public function companies(): CompanyClient
    {
        return $this->makeClient('companies', CompanyClient::class);
    }

    /**
     * Получить клиент сделок.
     *
     * @return DealClient
     */
    public function deals(): DealClient
    {
        return $this->makeClient('deals', DealClient::class);
    }

    /**
     * Получить клиент задач.
     *
     * @return TaskClient
     */
    public function tasks(): TaskClient
    {
        return $this->makeClient('tasks', TaskClient::class);
    }

    /**
     * Получить клиент пользователей.
     *
     * @return UserClient
     */
    public function users(): UserClient
    {
        return $this->makeClient('users', UserClient::class);
    }

    /**
     * Получить клиент пользовательских списков.
     *
     * @return ListClient
     */
    public function lists(): ListClient
    {
        return $this->makeClient('lists', ListClient::class);
    }

    /**
     * Получить кастомный клиент по имени.
     *
     * @param string $name Название клиента
     * @return BaseClient
     * @throws RuntimeException
     */
    public function client(string $name): BaseClient
    {
        if (!isset(self::$customClients[$name])) {
            throw new RuntimeException("Клиент '{$name}' не зарегистрирован.");
        }

        return $this->makeClient($name, self::$customClients[$name]);
    }

    /**
     * Получить официальный ServiceBuilder SDK.
     *
     * @return ServiceBuilder
     */
    public function sdk(): ServiceBuilder
    {
        return $this->getServiceBuilder();
    }

    /**
     * Получить URL авторизации OAuth.
     *
     * @param array $scopes Список прав доступа
     * @param string|null $state Состояние для защиты от CSRF
     * @return string
     */
    public function getAuthorizationUrl(array $scopes = [], ?string $state = null): string
    {
        $config = config("bitrix24.connections.{$this->connection}");
        $state = $state ?? Str::random(32);

        $params = [
            'client_id' => $config['client_id'],
            'response_type' => 'code',
            'redirect_uri' => $config['redirect_uri'],
            'state' => $state,
        ];

        if ($scopes === [] && !empty($config['scope'])) {
            $scopes = array_filter(array_map('trim', explode(',', (string) $config['scope'])));
        }

        if ($scopes !== []) {
            $params['scope'] = implode(',', $scopes);
        }

        return 'https://' . $this->normalizeDomain((string) $config['domain']) . '/oauth/authorize/?' . http_build_query($params);
    }

    /**
     * Обработать OAuth callback.
     *
     * @param string $code Код авторизации
     * @return array
     */
    public function handleCallback(string $code): array
    {
        $config = config("bitrix24.connections.{$this->connection}");

        $response = $this->exchangeCodeForToken($code, $config);

        $token = $this->tokenManager->storeToken($response, $this->userId, $this->connection);

        return [
            'token_id' => $token->id,
            'domain' => $token->domain,
            'expires_at' => $token->expires_at,
        ];
    }

    /**
     * Установить используемое подключение.
     *
     * @param string $connection Название подключения
     * @return self
     */
    public function setConnection(string $connection): self
    {
        $this->connection = $connection;
        $this->serviceBuilder = null;

        return $this;
    }

    /**
     * Установить ID пользователя для управления токенами.
     *
     * @param int|null $userId ID пользователя
     * @return self
     */
    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        $this->serviceBuilder = null;

        return $this;
    }

    /**
     * Проверить наличие валидного токена у пользователя.
     *
     * @param int|null $userId ID пользователя
     * @return bool
     */
    public function hasValidToken(?int $userId = null): bool
    {
        $userId = $userId ?? $this->userId;

        return $this->tokenManager->getToken($userId, $this->connection) !== null;
    }

    /**
     * Получить или создать экземпляр service builder.
     *
     * @return ServiceBuilder
     */
    private function getServiceBuilder(): ServiceBuilder
    {
        if ($this->serviceBuilder instanceof ServiceBuilder) {
            return $this->serviceBuilder;
        }

        $config = config("bitrix24.connections.{$this->connection}", []);
        $authType = $config['type'] ?? 'oauth';
        $factory = new ServiceBuilderFactory($this->createEventDispatcher(), $this->createLogger());

        if ($authType === 'webhook' && !empty($config['webhook_url'])) {
            $this->serviceBuilder = $factory->initFromWebhook($config['webhook_url']);

            return $this->serviceBuilder;
        }

        $token = $this->tokenManager->getToken($this->userId, $this->connection);

        if (!$token) {
            throw new RuntimeException('Не найдены валидные учетные данные Bitrix24. Пожалуйста, авторизуйтесь.');
        }

        $oauthServer = $config['oauth_server'] ?? DefaultOAuthServerUrl::default();

        $this->serviceBuilder = $factory->init(
            $this->tokenManager->getApplicationProfile($this->connection),
            $this->tokenManager->toAuthToken($token),
            $this->normalizeDomain((string) ($token->domain ?: $config['domain'] ?? '')),
            rtrim((string) $oauthServer, '/') . '/'
        );

        return $this->serviceBuilder;
    }

    /**
     * Обменять код авторизации на access token.
     *
     * @param string $code Код авторизации
     * @param array $config Конфигурация подключения
     * @return array
     */
    private function exchangeCodeForToken(string $code, array $config): array
    {
        $oauthServer = rtrim((string) ($config['oauth_server'] ?? DefaultOAuthServerUrl::default()), '/');

        $response = Http::asForm()->timeout(config('bitrix24.api.timeout', 30))->post(
            $oauthServer . '/oauth/token/',
            [
                'grant_type' => 'authorization_code',
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'code' => $code,
                'redirect_uri' => $config['redirect_uri'],
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException('Не удалось обменять код на токен: ' . $response->body());
        }

        $data = $response->json();

        if (!isset($data['access_token'], $data['refresh_token'])) {
            throw new RuntimeException('Ответ Bitrix24 не содержит токены: ' . $response->body());
        }

        return [
            'domain' => $this->normalizeDomain((string) ($data['domain'] ?? $config['domain'] ?? '')),
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_in' => $data['expires_in'] ?? 3600,
            'expires' => $data['expires'] ?? null,
            'scope' => explode(',', $data['scope'] ?? (string) ($config['scope'] ?? '')),
            'metadata' => [
                'member_id' => $data['member_id'] ?? null,
                'client_endpoint' => $data['client_endpoint'] ?? null,
                'server_endpoint' => $data['server_endpoint'] ?? null,
            ],
        ];
    }

    /**
     * Создать диспетчер событий SDK с сохранением обновлённых токенов.
     *
     * @return EventDispatcher
     */
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

    /**
     * Создать PSR-3 логгер для SDK.
     *
     * @return LoggerInterface
     */
    private function createLogger(): LoggerInterface
    {
        if (!config('bitrix24.logging.enabled')) {
            return new NullLogger();
        }

        return Log::channel(config('bitrix24.logging.channel', 'daily'));
    }

    /**
     * Нормализовать домен Bitrix24 без схемы.
     *
     * @param string $domain Домен
     * @return string
     */
    private function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);
        $domain = preg_replace('#^https?://#i', '', $domain) ?? $domain;

        return rtrim($domain, '/');
    }
}
