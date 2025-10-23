# Руководство по расширению пакета Bitrix24 Laravel

## Обзор гибкости

Пакет Bitrix24 Laravel спроектирован с учетом максимальной гибкости и возможности расширения. Вы можете:

1. **Переопределить существующие клиенты**
2. **Создать свои собственные клиенты**
3. **Расширить функциональность базовых классов**
4. **Интегрировать кастомную логику через интерфейсы**

---

## 1. Создание кастомного клиента

### Шаг 1: Создайте свой клиент

```php
<?php

namespace App\Services\Bitrix24;

use Leko\Bitrix24\Clients\BaseClient;
use Leko\Bitrix24\Contracts\ClientInterface;

class CustomAnalyticsClient extends BaseClient implements ClientInterface
{
    /**
     * Получить аналитику продаж за период.
     */
    public function getSalesAnalytics(string $dateFrom, string $dateTo): array
    {
        return $this->callMethod('crm.analytics.get', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], fn() => []) ?? [];
    }
    
    /**
     * Получить статистику по лидам.
     */
    public function getLeadStats(): array
    {
        $params = $this->buildParams(
            ['type' => 'lead'],
            [
                'period' => 'month',
                'groupBy' => 'status'
            ]
        );
        
        return $this->callMethod('crm.stats.get', $params) ?? [];
    }
}
```

### Шаг 2: Зарегистрируйте клиент в Service Provider

```php
<?php

namespace App\Providers;

use App\Services\Bitrix24\CustomAnalyticsClient;
use Illuminate\Support\ServiceProvider;
use Leko\Bitrix24\Bitrix24Service;

class Bitrix24ExtensionProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Регистрация кастомного клиента
        Bitrix24Service::registerClient('analytics', CustomAnalyticsClient::class);
    }
}
```

### Шаг 3: Используйте клиент

```php
use Leko\Bitrix24\Facades\Bitrix24;

// Получение кастомного клиента
$analytics = Bitrix24::client('analytics');
$stats = $analytics->getSalesAnalytics('2024-01-01', '2024-12-31');
```

---

## 2. Переопределение существующих клиентов

### Расширение функциональности Lead клиента

```php
<?php

namespace App\Services\Bitrix24;

use Leko\Bitrix24\Clients\LeadClient;

class EnhancedLeadClient extends LeadClient
{
    /**
     * Получить лиды с дополнительной обработкой.
     */
    public function listWithEnrichment(array $filter = []): array
    {
        $leads = parent::list($filter);
        
        // Добавляем дополнительную обработку
        return array_map(function ($lead) {
            $lead['enriched'] = true;
            $lead['score'] = $this->calculateLeadScore($lead);
            return $lead;
        }, $leads);
    }
    
    /**
     * Кастомная логика подсчета.
     */
    private function calculateLeadScore(array $lead): int
    {
        // Ваша логика
        return rand(1, 100);
    }
}
```

### Регистрация переопределенного клиента

```php
// В AppServiceProvider или отдельном провайдере
use App\Services\Bitrix24\EnhancedLeadClient;
use Leko\Bitrix24\Bitrix24Service;

public function boot(): void
{
    // Переопределяем стандартный клиент лидов
    Bitrix24Service::registerClient('leads', EnhancedLeadClient::class);
}
```

Теперь все вызовы `Bitrix24::leads()` будут использовать ваш расширенный клиент!

---

## 3. Использование интерфейсов для Type Hinting

### Создание сервиса с использованием интерфейсов

```php
<?php

namespace App\Services;

use Leko\Bitrix24\Contracts\LeadClientInterface;
use Leko\Bitrix24\Contracts\DealClientInterface;

class SalesService
{
    public function __construct(
        private LeadClientInterface $leadClient,
        private DealClientInterface $dealClient
    ) {}
    
    public function convertLeadToDeal(int $leadId): ?int
    {
        $lead = $this->leadClient->get($leadId);
        
        if (!$lead) {
            return null;
        }
        
        return $this->dealClient->add([
            'TITLE' => $lead['TITLE'],
            'CONTACT_ID' => $lead['CONTACT_ID'] ?? null,
        ]);
    }
}
```

### Биндинг в Service Container

```php
// В AppServiceProvider
use Leko\Bitrix24\Contracts\LeadClientInterface;
use Leko\Bitrix24\Facades\Bitrix24;

public function register(): void
{
    $this->app->bind(LeadClientInterface::class, function () {
        return Bitrix24::leads();
    });
}
```

---

## 4. Расширение BaseClient

### Добавление глобальной функциональности

```php
<?php

namespace App\Services\Bitrix24;

use Leko\Bitrix24\Clients\BaseClient as PackageBaseClient;
use Illuminate\Support\Facades\Cache;

abstract class BaseClient extends PackageBaseClient
{
    /**
     * Кеширующий вызов API.
     */
    protected function cachedCall(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember($key, $ttl, function () use ($callback) {
            return $callback();
        });
    }
    
    /**
     * Логирование с дополнительным контекстом.
     */
    protected function logWithContext(string $action, array $data): void
    {
        $this->logApiCall($action, [
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ]);
    }
}
```

Теперь все ваши клиенты могут наследоваться от вашего `BaseClient` и получить доступ к общим методам!

---

## 5. Создание собственных интерфейсов

```php
<?php

namespace App\Contracts;

use Leko\Bitrix24\Contracts\ClientInterface;

interface ReportClientInterface extends ClientInterface
{
    public function generateReport(string $type): array;
    public function exportToExcel(int $reportId): string;
}
```

```php
<?php

namespace App\Services\Bitrix24;

use App\Contracts\ReportClientInterface;
use Leko\Bitrix24\Clients\BaseClient;

class ReportClient extends BaseClient implements ReportClientInterface
{
    public function generateReport(string $type): array
    {
        return $this->callMethod('reports.generate', [
            'type' => $type
        ]) ?? [];
    }
    
    public function exportToExcel(int $reportId): string
    {
        $result = $this->callMethod('reports.export', [
            'id' => $reportId,
            'format' => 'xlsx'
        ]);
        
        return $result['downloadUrl'] ?? '';
    }
}
```

---

## 6. Использование buildParams для гибкости

Метод `buildParams()` в `BaseClient` позволяет создавать гибкие условные параметры:

```php
public function advancedSearch(array $criteria): array
{
    $params = $this->buildParams(
        ['entityType' => 'lead'], // Базовые параметры
        [
            'filter' => $criteria['filter'] ?? [],
            'select' => [
                'value' => $criteria['fields'] ?? [],
                'condition' => fn($val) => !empty($val) && count($val) > 0
            ],
            'limit' => [
                'value' => $criteria['limit'] ?? 50,
                'condition' => fn($val) => $val > 0 && $val <= 500
            ],
        ]
    );
    
    return $this->callMethod('crm.lead.search', $params) ?? [];
}
```

---

## 7. Примеры использования

### Пример 1: Интеграция с очередями

```php
<?php

namespace App\Services\Bitrix24;

use Leko\Bitrix24\Clients\DealClient;
use App\Jobs\ProcessDealJob;

class QueueableDealClient extends DealClient
{
    public function addAsync(array $fields): void
    {
        ProcessDealJob::dispatch($fields);
    }
}

// Регистрация
Bitrix24Service::registerClient('deals', QueueableDealClient::class);
```

### Пример 2: Добавление валидации

```php
<?php

namespace App\Services\Bitrix24;

use Leko\Bitrix24\Clients\ContactClient;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class ValidatedContactClient extends ContactClient
{
    public function add(array $fields): ?int
    {
        $validator = Validator::make($fields, [
            'NAME' => 'required|string|max:255',
            'PHONE' => 'required|array',
            'EMAIL' => 'nullable|email',
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return parent::add($fields);
    }
}
```

### Пример 3: Мультитенантность

```php
<?php

namespace App\Services\Bitrix24;

use Leko\Bitrix24\Bitrix24Service;

class TenantBitrix24Service extends Bitrix24Service
{
    public function __construct(TokenManager $tokenManager, private int $tenantId)
    {
        parent::__construct($tokenManager);
        $this->setConnection("tenant_{$tenantId}");
    }
}
```

---

## Заключение

Пакет предоставляет несколько уровней расширения:

1. ✅ **Интерфейсы** - для Type Hinting и DI
2. ✅ **BaseClient** - общая функциональность для всех клиентов
3. ✅ **Регистрация клиентов** - через `registerClient()`
4. ✅ **Переопределение** - замена стандартных клиентов
5. ✅ **Helper методы** - `buildParams()`, `safeCall()`, `logException()`
6. ✅ **Service Provider** - интеграция с Laravel DI

Пакет полностью следует принципам **SOLID** и **Open/Closed Principle** - открыт для расширения, закрыт для модификации.

