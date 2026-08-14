<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Tests;

use Bitrix24\SDK\Core\Contracts\CoreInterface;
use Bitrix24\SDK\Services\ServiceBuilder;
use Leko\Bitrix24\Clients\BaseClient;
use Mockery;

final class TestClient extends BaseClient
{
    public static function fake(bool $webhook = false): self
    {
        $credentials = Mockery::mock();
        $credentials->shouldReceive('isWebhookContext')->andReturn($webhook);

        $apiClient = Mockery::mock();
        $apiClient->shouldReceive('getCredentials')->andReturn($credentials);

        $core = Mockery::mock(CoreInterface::class);
        $core->shouldReceive('getApiClient')->andReturn($apiClient);

        $builder = Mockery::mock(ServiceBuilder::class);
        $builder->core = $core;

        return new self($builder);
    }

    public function invoke(string $method, array $params, ?callable $sdk = null): mixed
    {
        return $this->callMethod($method, $params, $sdk);
    }

    public function invokeCrm(string $entity, string $action, array $params = [], ?callable $sdk = null): mixed
    {
        return $this->callCrmMethod($entity, $action, $params, $sdk);
    }

    public function success(mixed $value): bool
    {
        return $this->isSuccessful($value);
    }

    public function integer(mixed $value): ?int
    {
        return $this->asInt($value);
    }

    public function arrayValue(mixed $value): array
    {
        return $this->asArray($value);
    }

    public function params(array $base, array $conditional): array
    {
        return $this->buildParams($base, $conditional);
    }
}
