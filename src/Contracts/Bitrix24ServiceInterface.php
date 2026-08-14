<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Contracts;

use Bitrix24\SDK\Services\ServiceBuilder;
use Leko\Bitrix24\Clients\BaseClient;

/**
 * Главный интерфейс интеграции с Bitrix24.
 */
interface Bitrix24ServiceInterface
{
    public function crm(): CrmClientInterface;

    public function leads(): LeadClientInterface;

    public function contacts(): ContactClientInterface;

    public function companies(): CompanyClientInterface;

    public function deals(): DealClientInterface;

    public function tasks(): TaskClientInterface;

    public function users(): UserClientInterface;

    public function lists(): ListClientInterface;

    public function client(string $name): BaseClient;

    public function sdk(): ServiceBuilder;

    /**
     * @param list<string> $scopes
     */
    public function getAuthorizationUrl(array $scopes = [], ?string $state = null): string;

    /**
     * @return array{token_id: mixed, domain: mixed, expires_at: mixed}
     */
    public function handleCallback(string $code): array;

    public function setConnection(string $connection): self;

    public function setUserId(?int $userId): self;

    public function hasValidToken(?int $userId = null): bool;
}
