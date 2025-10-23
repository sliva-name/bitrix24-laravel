<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Leko\Bitrix24\Contracts\ClientInterface;
use Leko\Bitrix24\Support\Macroable;
use Leko\Bitrix24\WebhookServiceBuilder;
use Bitrix24\SDK\Services\ServiceBuilder;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Базовый клиент для Bitrix24 API
 *
 * Предоставляет общую функциональность для всех клиентов API.
 */
abstract class BaseClient implements ClientInterface
{
    use Macroable;
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
    public function isWebhook(): bool
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
        $startTime = microtime(true);
        
        try {
            if ($this->isWebhook()) {
                $response = $this->apiCall($method, $params);
                $result = $response['result'] ?? ($action === 'list' ? [] : null);
                $this->logApiCall($method, $params);
                $this->dispatchApiCallEvent($method, $params, $result, microtime(true) - $startTime);
                return $result;
            }

            if ($oauthCallback) {
                $result = $oauthCallback();
                $this->dispatchApiCallEvent($method, $params, $result, microtime(true) - $startTime);
                return $result;
            }

            return $action === 'list' ? [] : null;
        } catch (\Throwable $e) {
            $this->dispatchApiCallFailedEvent($method, $params, $e, microtime(true) - $startTime);
            throw $e;
        }
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
        $startTime = microtime(true);
        
        try {
            if ($this->isWebhook()) {
                $response = $this->apiCall($method, $params);
                $result = $response['result'] ?? null;
                $this->logApiCall($method, $params);
                $this->dispatchApiCallEvent($method, $params, $result, microtime(true) - $startTime);
                return $result;
            }

            if ($oauthCallback) {
                $result = $oauthCallback();
                $this->dispatchApiCallEvent($method, $params, $result, microtime(true) - $startTime);
                return $result;
            }

            return null;
        } catch (\Throwable $e) {
            $this->dispatchApiCallFailedEvent($method, $params, $e, microtime(true) - $startTime);
            throw $e;
        }
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
     * Залогировать исключение API без выброса.
     *
     * @param Throwable $e Исключение
     * @param string $context Контекст ошибки
     */
    protected function logException(Throwable $e, string $context): void
    {
        Log::error("Ошибка Bitrix24 API в {$context}", [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Безопасный вызов с обработкой исключений.
     *
     * @param callable $callback Callback для выполнения
     * @param mixed $fallback Значение по умолчанию при ошибке
     * @param bool $throwOnError Выбросить исключение или вернуть fallback
     * @return mixed
     * @throws Throwable
     */
    protected function safeCall(callable $callback, mixed $fallback = null, bool $throwOnError = true): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            $this->logException($e, debug_backtrace()[1]['function'] ?? 'unknown');
            
            if ($throwOnError) {
                throw $e;
            }
            
            return $fallback;
        }
    }

    /**
     * Добавить параметр в массив если условие выполнено.
     *
     * @param array $params Массив параметров
     * @param string $key Ключ параметра
     * @param mixed $value Значение параметра
     * @param callable|null $condition Условие для добавления параметра
     * @return array
     */
    protected function addParamIf(array $params, string $key, mixed $value, ?callable $condition = null): array
    {
        $shouldAdd = $condition ? $condition($value) : !empty($value);
        
        if ($shouldAdd) {
            $params[$key] = $value;
        }

        return $params;
    }

    /**
     * Построить массив параметров с условным добавлением значений.
     *
     * @param array $base Базовые параметры
     * @param array $conditional Условные параметры в формате ['key' => ['value' => mixed, 'condition' => callable|null]]
     * @return array
     */
    protected function buildParams(array $base, array $conditional = []): array
    {
        $params = $base;

        foreach ($conditional as $key => $config) {
            if (is_array($config) && isset($config['value'])) {
                $value = $config['value'];
                $condition = $config['condition'] ?? null;
                $params = $this->addParamIf($params, $key, $value, $condition);
            } else {
                $params = $this->addParamIf($params, $key, $config);
            }
        }

        return $params;
    }

    /**
     * Отправить событие успешного вызова API.
     *
     * @param string $method Метод API
     * @param array $params Параметры
     * @param mixed $result Результат
     * @param float $duration Длительность
     * @return void
     */
    protected function dispatchApiCallEvent(string $method, array $params, mixed $result, float $duration): void
    {
        if (!class_exists('Illuminate\Support\Facades\Event')) {
            return;
        }

        \Illuminate\Support\Facades\Event::dispatch(
            new \Leko\Bitrix24\Events\ApiCallEvent($method, $params, $result, $duration, $this->isWebhook())
        );
    }

    /**
     * Отправить событие неудачного вызова API.
     *
     * @param string $method Метод API
     * @param array $params Параметры
     * @param Throwable $exception Исключение
     * @param float $duration Длительность
     * @return void
     */
    protected function dispatchApiCallFailedEvent(string $method, array $params, Throwable $exception, float $duration): void
    {
        if (!class_exists('Illuminate\Support\Facades\Event')) {
            return;
        }

        \Illuminate\Support\Facades\Event::dispatch(
            new \Leko\Bitrix24\Events\ApiCallFailedEvent($method, $params, $exception, $duration)
        );
    }
}
