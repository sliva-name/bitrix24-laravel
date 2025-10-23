# Расширенное использование пакета Bitrix24 Laravel

## 🚀 Новые возможности для максимальной гибкости

Пакет теперь включает **5 мощных систем расширения**:

1. **Макросы (Macroable)** - динамическое добавление методов
2. **События (Events)** - перехват API вызовов
3. **Traits** - кеширование и rate limiting
4. **Dependency Injection** - интерфейсы забинжены в контейнер
5. **Batch операции** - пакетные запросы

---

## 1. Макросы (Macroable)

### Добавление методов во время выполнения

```php
use Leko\Bitrix24\Clients\LeadClient;
use Leko\Bitrix24\Facades\Bitrix24;

// В AppServiceProvider::boot()
LeadClient::macro('getHotLeads', function () {
    return $this->list([
        'OPPORTUNITY' => ['>=', 100000],
        'STATUS_ID' => 'NEW'
    ]);
});

// Использование
$hotLeads = Bitrix24::leads()->getHotLeads();
```

### Mixin - добавление целого класса методов

```php
class LeadAnalyticsMixin
{
    public function calculateConversion()
    {
        return function () {
            $all = $this->list();
            $converted = $this->list(['STATUS_ID' => 'CONVERTED']);
            
            return count($converted) / count($all) * 100;
        };
    }
    
    public function getTopLeads()
    {
        return function (int $limit = 10) {
            return $this->list([
                'order' => ['OPPORTUNITY' => 'DESC'],
                'limit' => $limit
            ]);
        };
    }
}

// Регистрация
LeadClient::mixin(new LeadAnalyticsMixin());

// Использование
$conversion = Bitrix24::leads()->calculateConversion();
$topLeads = Bitrix24::leads()->getTopLeads(5);
```

---

## 2. События (Events)

### Слушатель успешного вызова API

```php
<?php

namespace App\Listeners;

use Leko\Bitrix24\Events\ApiCallEvent;
use Illuminate\Support\Facades\Log;

class LogBitrix24ApiCall
{
    public function handle(ApiCallEvent $event): void
    {
        Log::info('Bitrix24 API Call', [
            'method' => $event->method,
            'duration' => round($event->duration * 1000, 2) . 'ms',
            'is_webhook' => $event->isWebhook,
            'params_count' => count($event->params),
        ]);
    }
}
```

### Слушатель ошибок API

```php
<?php

namespace App\Listeners;

use Leko\Bitrix24\Events\ApiCallFailedEvent;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Bitrix24ApiFailedNotification;

class NotifyOnBitrix24ApiFailure
{
    public function handle(ApiCallFailedEvent $event): void
    {
        // Отправить уведомление администратору
        Notification::route('mail', config('app.admin_email'))
            ->notify(new Bitrix24ApiFailedNotification(
                $event->method,
                $event->exception->getMessage()
            ));
    }
}
```

### Регистрация слушателей

```php
// В EventServiceProvider
protected $listen = [
    \Leko\Bitrix24\Events\ApiCallEvent::class => [
        \App\Listeners\LogBitrix24ApiCall::class,
    ],
    \Leko\Bitrix24\Events\ApiCallFailedEvent::class => [
        \App\Listeners\NotifyOnBitrix24ApiFailure::class,
    ],
];
```

---

## 3. Traits - Кеширование и Rate Limiting

### HasCaching Trait

```php
<?php

namespace App\Services\Bitrix24;

use Leko\Bitrix24\Clients\LeadClient;
use Leko\Bitrix24\Support\Traits\HasCaching;

class CachedLeadClient extends LeadClient
{
    use HasCaching;

    /**
     * Получить список с кешированием.
     */
    public function listCached(array $filter = []): array
    {
        $cacheKey = 'leads:' . md5(json_encode($filter));
        
        return $this->cached($cacheKey, function () use ($filter) {
            return $this->list($filter);
        }, 600); // 10 минут
    }
    
    /**
     * Добавить лид и очистить кеш.
     */
    public function add(array $fields): ?int
    {
        $result = parent::add($fields);
        
        // Очистить кеш после добавления
        $this->flushCache();
        
        return $result;
    }
}

// Использование
$client = new CachedLeadClient($serviceBuilder);
$client->cacheTtl(1800)->cachePrefix('my-leads');
$leads = $client->listCached(['STATUS_ID' => 'NEW']);
```

### HasRateLimiting Trait

```php
<?php

namespace App\Services\Bitrix24;

use Leko\Bitrix24\Clients\DealClient;
use Leko\Bitrix24\Support\Traits\HasRateLimiting;

class RateLimitedDealClient extends DealClient
{
    use HasRateLimiting;

    public function __construct($serviceBuilder)
    {
        parent::__construct($serviceBuilder);
        
        // 50 запросов в минуту
        $this->rateLimit(50, 60);
    }

    /**
     * Получить список с rate limiting.
     */
    public function list(array $filter = [], array $select = ['*'], array $order = ['ID' => 'DESC'], int $start = 0): array
    {
        return $this->rateLimited('deals:list', function () use ($filter, $select, $order, $start) {
            return parent::list($filter, $select, $order, $start);
        });
    }
}
```

### Комбинирование Traits

```php
<?php

namespace App\Services\Bitrix24;

use Leko\Bitrix24\Clients\ContactClient;
use Leko\Bitrix24\Support\Traits\HasCaching;
use Leko\Bitrix24\Support\Traits\HasRateLimiting;

class OptimizedContactClient extends ContactClient
{
    use HasCaching, HasRateLimiting;

    public function __construct($serviceBuilder)
    {
        parent::__construct($serviceBuilder);
        $this->rateLimit(100, 60);
        $this->cacheTtl(900);
    }

    public function list(array $filter = [], array $select = ['*'], array $order = ['ID' => 'DESC'], int $start = 0): array
    {
        $cacheKey = 'contacts:' . md5(json_encode([$filter, $select, $order, $start]));
        
        return $this->cached($cacheKey, function () use ($filter, $select, $order, $start) {
            return $this->rateLimited('contacts:list', function () use ($filter, $select, $order, $start) {
                return parent::list($filter, $select, $order, $start);
            });
        });
    }
}
```

---

## 4. Dependency Injection через интерфейсы

### Внедрение зависимостей в конструктор

```php
<?php

namespace App\Services;

use Leko\Bitrix24\Contracts\LeadClientInterface;
use Leko\Bitrix24\Contracts\DealClientInterface;

class SalesReportService
{
    public function __construct(
        private LeadClientInterface $leadClient,
        private DealClientInterface $dealClient
    ) {}

    public function generateMonthlyReport(): array
    {
        $leads = $this->leadClient->list([
            'DATE_CREATE' => ['>=', now()->startOfMonth()]
        ]);
        
        $deals = $this->dealClient->list([
            'DATE_CREATE' => ['>=', now()->startOfMonth()],
            'STAGE_ID' => 'WON'
        ]);
        
        return [
            'total_leads' => count($leads),
            'total_deals' => count($deals),
            'conversion' => count($deals) / count($leads) * 100,
        ];
    }
}

// Laravel автоматически внедрит зависимости
$report = app(SalesReportService::class)->generateMonthlyReport();
```

### Внедрение через Route Model Binding

```php
Route::get('/leads/{id}/convert', function (
    int $id,
    LeadClientInterface $leadClient,
    DealClientInterface $dealClient
) {
    $lead = $leadClient->get($id);
    
    $dealId = $dealClient->add([
        'TITLE' => $lead['TITLE'],
        'CONTACT_ID' => $lead['CONTACT_ID'],
    ]);
    
    return response()->json(['deal_id' => $dealId]);
});
```

---

## 5. Batch операции (Пакетные запросы)

### Создание batch запроса

```php
use Leko\Bitrix24\Support\BatchRequest;
use Leko\Bitrix24\Facades\Bitrix24;

$batch = new BatchRequest(Bitrix24::leads());

$batch
    ->add('lead1', 'crm.lead.add', ['fields' => ['TITLE' => 'Лид 1']])
    ->add('lead2', 'crm.lead.add', ['fields' => ['TITLE' => 'Лид 2']])
    ->add('lead3', 'crm.lead.add', ['fields' => ['TITLE' => 'Лид 3']]);

$results = $batch->execute();
```

### Массовые операции

```php
$leadsData = [
    ['TITLE' => 'Компания А', 'PHONE' => '+7 (999) 123-45-67'],
    ['TITLE' => 'Компания Б', 'PHONE' => '+7 (999) 765-43-21'],
    ['TITLE' => 'Компания В', 'PHONE' => '+7 (999) 111-22-33'],
];

$batch = new BatchRequest(Bitrix24::leads());

foreach ($leadsData as $index => $data) {
    $batch->add("lead_{$index}", 'crm.lead.add', ['fields' => $data]);
}

$results = $batch->execute();
```

---

## Полный пример: Комплексная интеграция

```php
<?php

namespace App\Services;

use Leko\Bitrix24\Clients\LeadClient;
use Leko\Bitrix24\Support\Traits\HasCaching;
use Leko\Bitrix24\Support\Traits\HasRateLimiting;
use Illuminate\Support\Facades\Event;
use Leko\Bitrix24\Events\ApiCallEvent;

class EnhancedLeadClient extends LeadClient
{
    use HasCaching, HasRateLimiting;

    public function __construct($serviceBuilder)
    {
        parent::__construct($serviceBuilder);
        
        // Настройка rate limiting: 60 запросов в минуту
        $this->rateLimit(60, 60);
        
        // Настройка кеширования: 15 минут
        $this->cacheTtl(900)->cachePrefix('enhanced-leads');
        
        // Добавление макросов
        static::macro('getQualifiedLeads', function () {
            return $this->list([
                'OPPORTUNITY' => ['>=', 50000],
                'STATUS_ID' => ['NEW', 'IN_PROCESS']
            ]);
        });
    }

    /**
     * Умный список с кешированием и rate limiting.
     */
    public function smartList(array $filter = []): array
    {
        $cacheKey = 'leads:smart:' . md5(json_encode($filter));
        
        return $this->cached($cacheKey, function () use ($filter) {
            return $this->rateLimited('smart-list', function () use ($filter) {
                return parent::list($filter);
            });
        });
    }
}

// Регистрация в ServiceProvider
Bitrix24Service::registerClient('enhanced-leads', EnhancedLeadClient::class);

// Регистрация слушателя событий
Event::listen(ApiCallEvent::class, function ($event) {
    if ($event->duration > 1) {
        logger()->warning('Slow Bitrix24 API call', [
            'method' => $event->method,
            'duration' => $event->duration
        ]);
    }
});

// Использование
$client = Bitrix24::client('enhanced-leads');
$qualified = $client->getQualifiedLeads(); // Макрос
$all = $client->smartList(); // Кеширование + rate limiting
```

---

## Резюме возможностей

| Фича | Применение | Преимущества |
|------|-----------|-------------|
| **Macroable** | Динамическое расширение | Добавление методов без наследования |
| **Events** | Мониторинг и логирование | Централизованная обработка событий |
| **HasCaching** | Ускорение повторных запросов | Снижение нагрузки на API |
| **HasRateLimiting** | Защита от лимитов | Автоматическое ограничение частоты |
| **DI Interfaces** | Чистая архитектура | Легкое тестирование и замена |
| **BatchRequest** | Массовые операции | Экономия времени и запросов |
| **registerClient()** | Кастомные клиенты | Полная кастомизация |

---

## 🎉 Итог

Теперь пакет предоставляет **невероятную гибкость** на всех уровнях:

✅ **Макросы** - расширение во время выполнения  
✅ **События** - полный контроль над API вызовами  
✅ **Traits** - готовые решения для кеширования и rate limiting  
✅ **DI** - чистая архитектура и тестируемость  
✅ **Batch** - эффективные массовые операции  
✅ **Интерфейсы** - контракты для всех клиентов  
✅ **BaseClient helpers** - buildParams, safeCall, logException  
✅ **Service Provider** - автоматическая регистрация  

Пакет полностью следует принципам **SOLID**, **DRY**, и **Open/Closed Principle**! 🚀

