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
 * Базовый клиент Bitrix24 API.
 */
abstract class BaseClient implements ClientInterface
{
    use Macroable;

    public function __construct(
        protected readonly ServiceBuilder $serviceBuilder
    ) {
    }

    public function getServiceBuilder(): ServiceBuilder
    {
        return $this->serviceBuilder;
    }

    public function isWebhook(): bool
    {
        return $this->serviceBuilder->core->getApiClient()->getCredentials()->isWebhookContext();
    }

    /**
     * Вызвать REST-метод через SDK.
     *
     * @param array<string, mixed> $params
     * @return array<mixed>
     */
    protected function apiCall(string $method, array $params = []): array
    {
        return $this->serviceBuilder->core
            ->call($method, $params)
            ->getResponseData()
            ->getResult();
    }

    /**
     * Вызов CRM-метода (`crm.{entity}.{action}`).
     *
     * @template T
     * @param array<string, mixed> $params
     * @param (callable(): T)|null $sdkCallback
     * @return T
     */
    protected function callCrmMethod(string $entity, string $action, array $params = [], ?callable $sdkCallback = null)
    {
        return $this->callMethod("crm.{$entity}.{$action}", $params, $sdkCallback);
    }

    /**
     * Вызов метода API с логированием и событиями.
     *
     * @template T
     * @param array<string, mixed> $params
     * @param (callable(): T)|null $sdkCallback
     * @return T
     */
    protected function callMethod(string $method, array $params = [], ?callable $sdkCallback = null)
    {
        $startedAt = microtime(true);

        try {
            $result = $sdkCallback !== null ? $sdkCallback() : $this->apiCall($method, $params);

            $this->logApiCall($method, $params);
            $this->dispatchApiCallEvent($method, $params, $result, microtime(true) - $startedAt);

            return $result;
        } catch (Throwable $exception) {
            $this->dispatchApiCallFailedEvent($method, $params, $exception, microtime(true) - $startedAt);

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $params
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
     * @param callable(): T $callback
     * @param TFallback $fallback
     * @return T|TFallback
     * @throws Throwable
     */
    protected function safeCall(callable $callback, array|object|int|bool|string|null $fallback = null, bool $throwOnError = true)
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            $this->logException($e, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown');

            if ($throwOnError) {
                throw $e;
            }

            return $fallback;
        }
    }

    /**
     * Bitrix24 REST часто возвращает успех как true, 1 или "1".
     */
    protected function isSuccessful(mixed $result): bool
    {
        return $result === true || $result === 1 || $result === '1';
    }

    protected function asInt(mixed $result): ?int
    {
        return is_numeric($result) ? (int) $result : null;
    }

    /**
     * @return array<mixed>
     */
    protected function asArray(mixed $result): array
    {
        return is_array($result) ? $result : [];
    }

    /**
     * @param array<string, mixed> $params
     * @param array<mixed>|string|int|float|bool|null $value
     * @return array<string, mixed>
     */
    protected function addParamIf(array $params, string $key, array|string|int|float|bool|null $value, ?callable $condition = null): array
    {
        $shouldAdd = $condition !== null ? (bool) $condition($value) : !empty($value);

        if ($shouldAdd) {
            $params[$key] = $value;
        }

        return $params;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, array{value: mixed, condition?: callable}|mixed> $conditional
     * @return array<string, mixed>
     */
    protected function buildParams(array $base, array $conditional = []): array
    {
        $params = $base;

        foreach ($conditional as $key => $config) {
            if (is_array($config) && array_key_exists('value', $config)) {
                $params = $this->addParamIf($params, $key, $config['value'], $config['condition'] ?? null);
                continue;
            }

            $params = $this->addParamIf($params, $key, $config);
        }

        return $params;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function dispatchApiCallEvent(string $method, array $params, array|object|int|bool|string|null $result, float $duration): void
    {
        Event::dispatch(new ApiCallEvent($method, $params, $result, $duration, $this->isWebhook()));
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function dispatchApiCallFailedEvent(string $method, array $params, Throwable $exception, float $duration): void
    {
        Event::dispatch(new ApiCallFailedEvent($method, $params, $exception, $duration));
    }
}
