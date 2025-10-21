<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Facades;

use Illuminate\Support\Facades\Facade;
use Leko\Bitrix24\Bitrix24Service;
use Leko\Bitrix24\Clients\CompanyClient;
use Leko\Bitrix24\Clients\ContactClient;
use Leko\Bitrix24\Clients\CrmClient;
use Leko\Bitrix24\Clients\DealClient;
use Leko\Bitrix24\Clients\LeadClient;
use Leko\Bitrix24\Clients\TaskClient;
use Leko\Bitrix24\Clients\UserClient;

/**
 * Фасад для Bitrix24Service
 *
 * @method static CrmClient crm()
 * @method static LeadClient leads()
 * @method static ContactClient contacts()
 * @method static CompanyClient companies()
 * @method static DealClient deals()
 * @method static TaskClient tasks()
 * @method static UserClient users()
 * @method static string getAuthorizationUrl(array $scopes = [], ?string $state = null)
 * @method static array handleCallback(string $code)
 * @method static Bitrix24Service setConnection(string $connection)
 * @method static Bitrix24Service setUserId(?int $userId)
 * @method static bool hasValidToken(?int $userId = null)
 *
 * @see Bitrix24Service
 */
class Bitrix24 extends Facade
{
    /**
     * Получить зарегистрированное имя компонента.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'bitrix24';
    }
}

