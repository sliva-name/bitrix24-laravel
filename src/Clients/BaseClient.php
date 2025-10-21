<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Leko\Bitrix24\WebhookServiceBuilder;
use Bitrix24\SDK\Services\ServiceBuilder;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Базовый клиент для Bitrix24 API
 *
 * Предоставляет общую функциональность для всех клиентов API.
 */
abstract class BaseClient
{
    /**
     * Создать новый экземпляр клиента.
     *
     * @param ServiceBuilder|WebhookServiceBuilder $serviceBuilder Построитель сервисов Bitrix24
     */
    public function __construct(
        protected readonly ServiceBuilder|WebhookServiceBuilder $serviceBuilder
    ) {
    }

    /**
     * Проверить является ли подключение webhook.
     *
     * @return bool
     */
    protected function isWebhook(): bool
    {
        return $this->serviceBuilder instanceof WebhookServiceBuilder;
    }

    /**
     * Вызвать метод API (универсально для OAuth и Webhook).
     *
     * @param string $method Метод API
     * @param array $params Параметры запроса
     * @return array
     */
    protected function apiCall(string $method, array $params = []): array
    {
        if ($this->isWebhook()) {
            return $this->serviceBuilder->call($method, $params);
        }

        return [];
    }

    /**
     * Универсальный вызов CRM метода с автоматическим определением режима.
     *
     * @param string $entity Сущность CRM (lead, deal, contact, company)
     * @param string $action Действие (list, get, add, update, delete, fields)
     * @param array $params Параметры запроса
     * @param callable|null $oauthCallback Callback для OAuth режима
     * @return mixed
     */
    protected function callCrmMethod(string $entity, string $action, array $params = [], ?callable $oauthCallback = null): mixed
    {
        $method = "crm.{$entity}.{$action}";
        
        if ($this->isWebhook()) {
            $response = $this->apiCall($method, $params);
            $this->logApiCall($method, $params);
            return $response['result'] ?? ($action === 'list' ? [] : null);
        }

        // OAuth режим
        if ($oauthCallback) {
            return $oauthCallback();
        }

        return $action === 'list' ? [] : null;
    }

    /**
     * Универсальный вызов обычного метода API.
     *
     * @param string $method Метод API
     * @param array $params Параметры запроса
     * @param callable|null $oauthCallback Callback для OAuth режима
     * @return mixed
     */
    protected function callMethod(string $method, array $params = [], ?callable $oauthCallback = null): mixed
    {
        if ($this->isWebhook()) {
            $response = $this->apiCall($method, $params);
            $this->logApiCall($method, $params);
            return $response['result'] ?? null;
        }

        // OAuth режим
        if ($oauthCallback) {
            return $oauthCallback();
        }

        return null;
    }

    /**
     * Логирует вызов API если логирование включено.
     *
     * @param string $method Метод API
     * @param array $params Параметры запроса
     * @param string|null $result Результат выполнения запроса
     */
    protected function logApiCall(string $method, array $params = [], ?string $result = null): void
    {
        if (!config('bitrix24.logging.enabled', false)) {
            return;
        }

        Log::channel(config('bitrix24.logging.channel', 'daily'))->info('Вызов Bitrix24 API', [
            'method' => $method,
            'params' => $params,
            'result' => $result,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Обработать исключение API.
     *
     * @param Throwable $e Исключение
     * @param string $context Контекст ошибки
     * @throws Throwable
     */
    protected function handleException(Throwable $e, string $context): void
    {
        Log::error("Ошибка Bitrix24 API в {$context}", [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        throw $e;
    }
}
