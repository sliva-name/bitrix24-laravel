<?php

declare(strict_types=1);

namespace Leko\Bitrix24;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Service Builder для Bitrix24
 *
 * Эмулирует поведение SDK ServiceBuilder для работы через webhook.
 */
class WebhookServiceBuilder
{
    private string $webhookUrl;
    private array $lastResponse = [];

    /**
     * Создать новый экземпляр WebhookServiceBuilder.
     *
     * @param string $webhookUrl URL входящего вебхука Bitrix24
     */
    public function __construct(string $webhookUrl)
    {
        $this->webhookUrl = rtrim($webhookUrl, '/') . '/';
    }

    /**
     * Вызвать метод API через webhook.
     *
     * @param string $method Название метода API
     * @param array $params Параметры запроса
     * @return array
     */
    public function call(string $method, array $params = []): array
    {
        try {
            if (config('bitrix24.logging.enabled')) {
                Log::channel(config('bitrix24.logging.channel', 'daily'))->info('Bitrix24 Webhook Call', [
                    'method' => $method,
                    'params' => $params,
                    'url' => $this->webhookUrl . $method,
                ]);
            }

            $response = Http::timeout(config('bitrix24.api.timeout', 30))
                ->post($this->webhookUrl . $method, $params);

            if ($response->failed()) {
                throw new \RuntimeException(
                    "Bitrix24 API Error [{$method}]: " . $response->body()
                );
            }

            $data = $response->json();
            $this->lastResponse = $data;

            if (isset($data['error'])) {
                throw new \RuntimeException(
                    "Bitrix24 API Error [{$method}]: {$data['error_description']} (Code: {$data['error']})"
                );
            }

            return $data;
        } catch (\Throwable $e) {
            if (config('bitrix24.logging.enabled')) {
                Log::channel(config('bitrix24.logging.channel', 'daily'))->error('Bitrix24 Webhook Error', [
                    'method' => $method,
                    'params' => $params,
                    'error' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Получить CRM Scope.
     *
     * @return WebhookCrmScope
     */
    public function getCRMScope(): WebhookCrmScope
    {
        return new WebhookCrmScope($this);
    }

    /**
     * Получить Main Scope.
     *
     * @return WebhookMainScope
     */
    public function getMainScope(): WebhookMainScope
    {
        return new WebhookMainScope($this);
    }

    /**
     * Получить Tasks Scope.
     *
     * @return WebhookTasksScope
     */
    public function getTasksScope(): WebhookTasksScope
    {
        return new WebhookTasksScope($this);
    }

    /**
     * Получить результат последнего запроса.
     *
     * @return array
     */
    public function getLastResponse(): array
    {
        return $this->lastResponse;
    }

    /**
     * Получить URL вебхука.
     *
     * @return string
     */
    public function getWebhookUrl(): string
    {
        return $this->webhookUrl;
    }
}

/**
 * Webhook CRM Scope
 *
 * Эмулирует CRM Scope для работы через webhook.
 */
class WebhookCrmScope
{
    /**
     * Создать новый экземпляр WebhookCrmScope.
     *
     * @param WebhookServiceBuilder $builder Экземпляр webhook builder
     */
    public function __construct(private WebhookServiceBuilder $builder)
    {
    }

    /**
     * Магический метод для эмуляции методов SDK.
     *
     * @param string $name Название метода
     * @param array $arguments Аргументы
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this;
    }
}

/**
 * Webhook Main Scope
 *
 * Эмулирует Main Scope для работы через webhook.
 */
class WebhookMainScope
{
    /**
     * Создать новый экземпляр WebhookMainScope.
     *
     * @param WebhookServiceBuilder $builder Экземпляр webhook builder
     */
    public function __construct(private WebhookServiceBuilder $builder)
    {
    }

    /**
     * Вызвать метод API.
     *
     * @param string $method Название метода
     * @param array $params Параметры
     * @return array
     */
    public function call(string $method, array $params = []): array
    {
        return $this->builder->call($method, $params);
    }
}

/**
 * Webhook Tasks Scope
 *
 * Эмулирует Tasks Scope для работы через webhook.
 */
class WebhookTasksScope
{
    /**
     * Создать новый экземпляр WebhookTasksScope.
     *
     * @param WebhookServiceBuilder $builder Экземпляр webhook builder
     */
    public function __construct(private WebhookServiceBuilder $builder)
    {
    }

    /**
     * Магический метод для эмуляции методов SDK.
     *
     * @param string $name Название метода
     * @param array $arguments Аргументы
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this;
    }
}

