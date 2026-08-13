<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Bitrix24\SDK\Services\ServiceBuilder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Leko\Bitrix24\Contracts\ClientInterface;
use Leko\Bitrix24\Events\ApiCallEvent;
use Leko\Bitrix24\Events\ApiCallFailedEvent;
use Leko\Bitrix24\Support\Macroable;
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
     * @param ServiceBuilder $serviceBuilder Построитель сервисов Bitrix24
     */
    public function __construct(
        protected readonly ServiceBuilder $serviceBuilder
    ) {
    }

    /**
     * Получить официальный ServiceBuilder SDK.
     *
     * @return ServiceBuilder
     */
    public function getServiceBuilder(): ServiceBuilder
    {
        return $this->serviceBuilder;
    }

    /**
     * Проверить является ли подключение webhook.
     *
     * @return bool
     */
    public function isWebhook(): bool
    {
        return $this->serviceBuilder->core->getApiClient()->getCredentials()->isWebhookContext();
    }

    /**
     * Вызвать метод REST API через SDK.
     *
     * @param string $method Метод API
     * @param array $params Параметры запроса
     * @return array
     */
    protected function apiCall(string $method, array $params = []): array
    {
        return $this->serviceBuilder->core
            ->call($method, $params)
            ->getResponseData()
            ->getResult();
    }

    /**
     * Универсальный вызов CRM метода.
     *
     * @template T
     * @param string $entity Сущность CRM (lead, deal, contact, company)
     * @param string $action Действие (list, get, add, update, delete, fields)
     * @param array $params Параметры запроса
     * @param (callable(): T)|null $sdkCallback Callback для типизированного SDK-метода
     * @return T
     */
    protected function callCrmMethod(string $entity, string $action, array $params = [], ?callable $sdkCallback = null)
    {
        $method = "crm.{$entity}.{$action}";
        $startTime = microtime(true);

        try {
            $result = $sdkCallback ? $sdkCallback() : $this->apiCall($method, $params);

            $this->logApiCall($method, $params);
            $this->dispatchApiCallEvent($method, $params, $result, microtime(true) - $startTime);

            return $result;
        } catch (Throwable $e) {
            $this->dispatchApiCallFailedEvent($method, $params, $e, microtime(true) - $startTime);
            throw $e;
        }
    }

    /**
     * Универсальный вызов обычного метода API.
     *
     * @template T
     * @param string $method Метод API
     * @param array $params Параметры запроса
     * @param (callable(): T)|null $sdkCallback Callback для типизированного SDK-метода
     * @return T
     */
    protected function callMethod(string $method, array $params = [], ?callable $sdkCallback = null)
    {
        $startTime = microtime(true);

        try {
            $result = $sdkCallback ? $sdkCallback() : $this->apiCall($method, $params);

            $this->logApiCall($method, $params);
            $this->dispatchApiCallEvent($method, $params, $result, microtime(true) - $startTime);

            return $result;
        } catch (Throwable $e) {
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
     * @template T
     * @template TFallback
     * @param callable(): T $callback Callback для выполнения
     * @param TFallback $fallback Значение по умолчанию при ошибке
     * @param bool $throwOnError Выбросить исключение или вернуть fallback
     * @return T|TFallback
     * @throws Throwable
     */
    protected function safeCall(callable $callback, array|object|int|bool|string|null $fallback = null, bool $throwOnError = true)
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
     * @param array|string|int|float|bool|null $value Значение параметра
     * @param callable|null $condition Условие для добавления параметра
     * @return array
     */
    protected function addParamIf(array $params, string $key, array|string|int|float|bool|null $value, ?callable $condition = null): array
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
     * @param array<string, array{value: array|string|int|float|bool|null, condition?: callable}|array|string|int|float|bool|null> $conditional Условные параметры
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
     * @param array|object|int|bool|string|null $result Результат SDK или REST
     * @param float $duration Длительность
     * @return void
     */
    protected function dispatchApiCallEvent(string $method, array $params, array|object|int|bool|string|null $result, float $duration): void
    {
        Event::dispatch(
            new ApiCallEvent($method, $params, $result, $duration, $this->isWebhook())
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
        Event::dispatch(
            new ApiCallFailedEvent($method, $params, $exception, $duration)
        );
    }
}
