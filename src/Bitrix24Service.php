<?php

declare(strict_types=1);

namespace Leko\Bitrix24;

use Leko\Bitrix24\Clients\CompanyClient;
use Leko\Bitrix24\Clients\ContactClient;
use Leko\Bitrix24\Clients\CrmClient;
use Leko\Bitrix24\Clients\DealClient;
use Leko\Bitrix24\Clients\LeadClient;
use Leko\Bitrix24\Clients\ListClient;
use Leko\Bitrix24\Clients\TaskClient;
use Leko\Bitrix24\Clients\UserClient;
use Leko\Bitrix24\Contracts\Bitrix24ServiceInterface;
use Bitrix24\SDK\Services\ServiceBuilder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Главный сервис Bitrix24
 *
 * Предоставляет доступ ко всем клиентам API Bitrix24 и методам аутентификации.
 */
class Bitrix24Service implements Bitrix24ServiceInterface
{
    private ServiceBuilder|WebhookServiceBuilder|null $serviceBuilder = null;
    private string $connection = 'main';
    private ?int $userId = null;

    /**
     * Создать новый экземпляр Bitrix24Service.
     *
     * @param TokenManager $tokenManager Менеджер управления токенами
     */
    public function __construct(
        private readonly TokenManager $tokenManager
    ) {
    }

    /**
     * Получить CRM клиент.
     *
     * @return CrmClient
     */
    public function crm(): CrmClient
    {
        return new CrmClient($this->getServiceBuilder());
    }

    /**
     * Получить клиент лидов.
     *
     * @return LeadClient
     */
    public function leads(): LeadClient
    {
        return new LeadClient($this->getServiceBuilder());
    }

    /**
     * Получить клиент контактов.
     *
     * @return ContactClient
     */
    public function contacts(): ContactClient
    {
        return new ContactClient($this->getServiceBuilder());
    }

    /**
     * Получить клиент компаний.
     *
     * @return CompanyClient
     */
    public function companies(): CompanyClient
    {
        return new CompanyClient($this->getServiceBuilder());
    }

    /**
     * Получить клиент сделок.
     *
     * @return DealClient
     */
    public function deals(): DealClient
    {
        return new DealClient($this->getServiceBuilder());
    }

    /**
     * Получить клиент задач.
     *
     * @return TaskClient
     */
    public function tasks(): TaskClient
    {
        return new TaskClient($this->getServiceBuilder());
    }

    /**
     * Получить клиент пользователей.
     *
     * @return UserClient
     */
    public function users(): UserClient
    {
        return new UserClient($this->getServiceBuilder());
    }

    /**
     * Получить клиент пользовательских списков.
     *
     * @return ListClient
     */
    public function lists(): ListClient
    {
        return new ListClient($this->getServiceBuilder());
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

        if (!empty($scopes)) {
            $params['scope'] = implode(',', $scopes);
        }

        return 'https://' . $config['domain'] . '/oauth/authorize/?' . http_build_query($params);
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
        $token = $this->tokenManager->getToken($userId, $this->connection);

        return $token !== null && !$token->isExpired();
    }

    /**
     * Получить или создать экземпляр service builder.
     *
     * @return ServiceBuilder|WebhookServiceBuilder
     */
    private function getServiceBuilder(): ServiceBuilder|WebhookServiceBuilder
    {
        if ($this->serviceBuilder === null) {
            $config = config("bitrix24.connections.{$this->connection}");
            $authType = $config['type'] ?? 'oauth';

            if ($authType === 'webhook' && !empty($config['webhook_url'])) {
                $this->serviceBuilder = new WebhookServiceBuilder($config['webhook_url']);
            } else {
                $credentials = $this->tokenManager->getCredentials($this->userId, $this->connection);

                if (!$credentials) {
                    throw new RuntimeException('Не найдены валидные учетные данные Bitrix24. Пожалуйста, авторизуйтесь.');
                }

                $this->serviceBuilder = new ServiceBuilder($credentials, null);
            }
        }

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
        $url = 'https://' . $config['domain'] . '/oauth/token/';

        $response = Http::asForm()->post($url, [
            'grant_type' => 'authorization_code',
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'redirect_uri' => $config['redirect_uri'],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Не удалось обменять код на токен: ' . $response->body());
        }

        $data = $response->json();

        return [
            'domain' => $config['domain'],
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_in' => $data['expires_in'] ?? 3600,
            'scope' => explode(',', $data['scope'] ?? ''),
        ];
    }
}

