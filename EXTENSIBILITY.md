# Расширение пакета Bitrix24 Laravel

## Кастомный клиент

Класс должен наследовать `BaseClient`:

```php
namespace App\Services\Bitrix24;

use Leko\Bitrix24\Clients\BaseClient;

class AnalyticsClient extends BaseClient
{
    public function getSalesReport(string $period): array
    {
        return $this->callMethod('crm.analytics.report', [
            'period' => $period,
        ]) ?? [];
    }
}
```

Для новой CRM-сущности удобнее `CrmEntityClient`: достаточно вернуть имя сущности в `entity()` и при необходимости сузить `get()`.

Регистрация в `AppServiceProvider::boot()`:

```php
use Leko\Bitrix24\Bitrix24Service;
use Leko\Bitrix24\Facades\Bitrix24;

Bitrix24Service::registerClient('analytics', AnalyticsClient::class);
Bitrix24Service::registerClient('leads', MyLeadClient::class);

$report = Bitrix24::client('analytics')->getSalesReport('month');
$leads = Bitrix24::leads(); // MyLeadClient
```

`registerClient()` принимает только наследников `BaseClient`.

## Dependency Injection

Все клиенты забинжены на интерфейсы:

```php
use Leko\Bitrix24\Contracts\LeadClientInterface;

class SalesService
{
    public function __construct(private LeadClientInterface $leads) {}
}
```

## Макросы, события, traits

См. [ADVANCED_USAGE.md](ADVANCED_USAGE.md): `Macroable`, `ApiCallEvent` / `ApiCallFailedEvent`, `HasCaching`, `HasRateLimiting`, `BatchRequest`.

Хелперы `BaseClient`: `callMethod()`, `callCrmMethod()`, `buildParams()`, `safeCall()`, `logException()`.
