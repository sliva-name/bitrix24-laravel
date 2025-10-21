<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Contracts;

use Leko\Bitrix24\Clients\CompanyClient;
use Leko\Bitrix24\Clients\ContactClient;
use Leko\Bitrix24\Clients\CrmClient;
use Leko\Bitrix24\Clients\DealClient;
use Leko\Bitrix24\Clients\LeadClient;
use Leko\Bitrix24\Clients\TaskClient;
use Leko\Bitrix24\Clients\UserClient;

/**
 * Интерфейс сервиса Bitrix24
 *
 * Главный интерфейс для интеграции с Bitrix24.
 */
interface Bitrix24ServiceInterface
{
    /**
     * Получить CRM клиент.
     *
     * @return CrmClient
     */
    public function crm(): CrmClient;

    /**
     * Получить клиент лидов.
     *
     * @return LeadClient
     */
    public function leads(): LeadClient;

    /**
     * Получить клиент контактов.
     *
     * @return ContactClient
     */
    public function contacts(): ContactClient;

    /**
     * Получить клиент компаний.
     *
     * @return CompanyClient
     */
    public function companies(): CompanyClient;

    /**
     * Получить клиент сделок.
     *
     * @return DealClient
     */
    public function deals(): DealClient;

    /**
     * Получить клиент задач.
     *
     * @return TaskClient
     */
    public function tasks(): TaskClient;

    /**
     * Получить клиент пользователей.
     *
     * @return UserClient
     */
    public function users(): UserClient;

    /**
     * Получить URL авторизации для OAuth.
     *
     * @param array $scopes Скоупы
     * @param null|string $state Состояние
     * @return string
     */
    public function getAuthorizationUrl(array $scopes = [], ?string $state = null): string;

    /**
     * Обработать OAuth callback.
     *
     * @param string $code Код
     * @return array
     */
    public function handleCallback(string $code): array;

    /**
     * Установить используемое подключение.
     *
     * @param string $connection Подключение
     * @return self
     */
    public function setConnection(string $connection): self;

    /**
     * Установить ID пользователя для управления токенами.
     *
     * @param null|int $userId Идентификатор пользователя
     * @return self
     */
    public function setUserId(?int $userId): self;

    /**
     * Проверить наличие валидного токена у пользователя.
     *
     * @param null|int $userId Идентификатор пользователя
     * @return bool
     */
    public function hasValidToken(?int $userId = null): bool;
}
