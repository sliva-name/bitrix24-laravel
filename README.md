# 🚀 Laravel Bitrix24 Integration

[![Latest Version](https://img.shields.io/packagist/v/sliva-name/bitrix24-laravel.svg)](https://packagist.org/packages/sliva-name/bitrix24-laravel)
[![License](https://img.shields.io/packagist/l/sliva-name/bitrix24-laravel.svg)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/sliva-name/bitrix24-laravel.svg)](https://packagist.org/packages/sliva-name/bitrix24-laravel)
[![Laravel Version](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x%20%7C%2012.x%20%7C%2013.x-red.svg)](https://laravel.com)
[![Downloads](https://img.shields.io/packagist/dt/sliva-name/bitrix24-laravel.svg)](https://packagist.org/packages/sliva-name/bitrix24-laravel)

**Полнофункциональный, гибкий и расширяемый пакет для интеграции Laravel с Bitrix24 CRM.**

Поддерживает OAuth и Webhook аутентификацию, предоставляет чистый API для работы с CRM, задачами, пользователями и кастомными списками. Спроектирован с учетом принципов SOLID и максимальной расширяемости.

---

## 📋 Содержание

- [Возможности](#-возможности)
- [Требования](#-требования)
- [Установка](#-установка)
- [Быстрый старт](#-быстрый-старт)
- [Конфигурация](#-конфигурация)
- [Базовое использование](#-базовое-использование)
- [Продвинутые возможности](#-продвинутые-возможности)
- [Расширение пакета](#-расширение-пакета)
- [API Reference](#-api-reference)
- [События](#-события)
- [Middleware](#-middleware)
- [Тестирование](#-тестирование)
- [Миграция](#-миграция)
- [FAQ](#-faq)
- [Поддержка](#-поддержка)

---

## ✨ Возможности

### 🔐 Аутентификация
- ✅ **OAuth 2.0** с автоматическим обновлением токенов
- ✅ **Webhook** для серверных интеграций
- ✅ Управление токенами с поддержкой мультитенантности
- ✅ Кеширование токенов для производительности

### 🎯 CRM клиенты
- ✅ **Лиды** (Leads)
- ✅ **Сделки** (Deals)
- ✅ **Контакты** (Contacts)
- ✅ **Компании** (Companies)
- ✅ **Задачи** (Tasks)
- ✅ **Пользователи** (Users)
- ✅ **Пользовательские списки** (Lists)
- ✅ Универсальный CRM клиент для любых сущностей

### 🔧 Расширяемость
- ✅ **Макросы (Macroable)** - динамическое добавление методов
- ✅ **События (Events)** - перехват API вызовов
- ✅ **Traits** - кеширование и rate limiting из коробки
- ✅ **Интерфейсы** - для всех клиентов (DI ready)
- ✅ **Регистрация клиентов** - переопределение и создание новых
- ✅ **Batch операции** - пакетные запросы к API

### 🏗️ Архитектура
- ✅ **SOLID принципы** - чистая архитектура
- ✅ **Repository Pattern** - абстракция работы с данными
- ✅ **Service Layer** - инкапсуляция бизнес-логики
- ✅ **PSR-12** - стандарты кодирования
- ✅ **Type Safety** - строгая типизация PHP 8.2+
- ✅ Полная поддержка **Dependency Injection**

### 📦 Дополнительно
- ✅ Middleware для защиты роутов
- ✅ API Resources для JSON-ответов
- ✅ Логирование API-вызовов
- ✅ Типизированные исключения (`AuthenticationException`, `OAuthException`, …)
- ✅ Подробная документация с примерами

---

## 📋 Требования

- **PHP:** 8.2 или выше
- **Laravel:** 10.x, 11.x, 12.x или 13.x
- **Bitrix24 PHP SDK:** `bitrix24/b24phpsdk` ^1.10
- **База данных:** PostgreSQL (рекомендуется), MySQL, SQLite
- **Extensions:** `ext-json`, `ext-curl`

---

## 📦 Установка

### Шаг 1: Установка через Composer

```bash
composer require sliva-name/bitrix24-laravel
```

### Шаг 2: Публикация ресурсов

```bash
# Публикация конфигурации
php artisan vendor:publish --tag=bitrix24-config

# Публикация миграций
php artisan vendor:publish --tag=bitrix24-migrations
```

### Шаг 3: Запуск миграций

```bash
php artisan migrate
```

### Шаг 4: Настройка окружения

Добавьте переменные в файл `.env`:

#### Для OAuth:
```env
BITRIX24_AUTH_TYPE=oauth
BITRIX24_DOMAIN=your-domain.bitrix24.ru
BITRIX24_CLIENT_ID=local.xxxxxxxx.yyyyyyyy
BITRIX24_CLIENT_SECRET=your_client_secret
BITRIX24_REDIRECT_URI=${APP_URL}/api/bitrix24/auth/callback
BITRIX24_SCOPE=crm,task,user,lists
BITRIX24_OAUTH_SERVER=https://oauth.bitrix.info/
```

#### Для Webhook:
```env
BITRIX24_AUTH_TYPE=webhook
BITRIX24_WEBHOOK_URL=https://your-domain.bitrix24.ru/rest/123/your_webhook_key/
```

---

## 🚀 Быстрый старт

### OAuth аутентификация

```php
use Leko\Bitrix24\Facades\Bitrix24;

// 1. Получить URL для авторизации
$authUrl = Bitrix24::getAuthorizationUrl();
return redirect($authUrl);

// 2. Обработать callback после авторизации
Route::get('/bitrix24/callback', function (Request $request) {
    $result = Bitrix24::handleCallback($request->input('code'));
    
    return response()->json([
        'success' => true,
        'token_id' => $result['token_id'],
    ]);
});

// 3. Использовать API
$leads = Bitrix24::leads()->list();

// Прямой доступ к официальному SDK при необходимости
$currentUser = Bitrix24::sdk()->getUserScope()->user()->current()->user();
```

### Работа с лидами

```php
use Leko\Bitrix24\Facades\Bitrix24;

// Получить все лиды
$leads = Bitrix24::leads()->list();

// Фильтрация и сортировка
$hotLeads = Bitrix24::leads()->list(
    filter: ['STATUS_ID' => 'NEW', 'OPPORTUNITY' => ['>=', 100000]],
    select: ['ID', 'TITLE', 'NAME', 'OPPORTUNITY'],
    order: ['OPPORTUNITY' => 'DESC']
);

// Создать лид
$leadId = Bitrix24::leads()->add([
    'TITLE' => 'Новый клиент',
    'NAME' => 'Иван',
    'LAST_NAME' => 'Иванов',
    'EMAIL' => [['VALUE' => 'ivan@example.com', 'VALUE_TYPE' => 'WORK']],
    'PHONE' => [['VALUE' => '+79001234567', 'VALUE_TYPE' => 'MOBILE']],
    'OPPORTUNITY' => 50000,
]);

// Обновить лид
Bitrix24::leads()->update($leadId, [
    'STATUS_ID' => 'IN_PROCESS',
    'ASSIGNED_BY_ID' => 1,
]);

// Удалить лид
Bitrix24::leads()->delete($leadId);
```

---

## ⚙️ Конфигурация

### Файл конфигурации

После публикации, файл `config/bitrix24.php` содержит все настройки:

```php
return [
    'default' => env('BITRIX24_DEFAULT_CONNECTION', 'main'),

    'connections' => [
        'main' => [
            'type' => env('BITRIX24_AUTH_TYPE', 'oauth'),
            'domain' => env('BITRIX24_DOMAIN'),
            'client_id' => env('BITRIX24_CLIENT_ID'),
            'client_secret' => env('BITRIX24_CLIENT_SECRET'),
            'redirect_uri' => env('BITRIX24_REDIRECT_URI'),
            'webhook_url' => env('BITRIX24_WEBHOOK_URL'),
            'scope' => env('BITRIX24_SCOPE', 'crm,task,user,lists'),
            'oauth_server' => env('BITRIX24_OAUTH_SERVER', 'https://oauth.bitrix.info/'),
        ],
    ],

    'cache' => [
        'store' => env('BITRIX24_CACHE_STORE'),
        'prefix' => 'bitrix24_tokens',
        'ttl' => 3600,
    ],

    'logging' => [
        'enabled' => env('BITRIX24_LOGGING_ENABLED', false),
        'channel' => env('BITRIX24_LOG_CHANNEL', 'daily'),
    ],

    'api' => [
        'timeout' => env('BITRIX24_API_TIMEOUT', 30),
        'retry_attempts' => env('BITRIX24_API_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('BITRIX24_API_RETRY_DELAY', 1000),
    ],
];
```

### Мультитенантность

```php
// Разные подключения для разных клиентов
$leads1 = Bitrix24::setConnection('tenant_1')->leads()->list();
$leads2 = Bitrix24::setConnection('tenant_2')->leads()->list();

// Работа с токенами конкретного пользователя
$leads = Bitrix24::setUserId(auth()->id())->leads()->list();
```

---

## 💻 Базовое использование

### Работа с CRM

#### Лиды (Leads)

```php
// Список с пагинацией
$leads = Bitrix24::leads()->list(
    filter: ['STATUS_ID' => 'NEW'],
    select: ['ID', 'TITLE', 'NAME'],
    order: ['ID' => 'DESC'],
    start: 0
);

// Получить по ID — LeadItemResult, поля через $lead->ID, $lead->TITLE
$lead = Bitrix24::leads()->get(123);

// CRUD операции
$id = Bitrix24::leads()->add([...]);
$success = Bitrix24::leads()->update($id, [...]);
$deleted = Bitrix24::leads()->delete($id);

// Получить доступные поля
$fields = Bitrix24::leads()->fields();
```

#### Сделки (Deals)

```php
$deals = Bitrix24::deals()->list([
    'STAGE_ID' => 'NEW',
    'OPPORTUNITY' => ['>=', 10000],
]);

$deal = Bitrix24::deals()->add([
    'TITLE' => 'Крупная сделка',
    'OPPORTUNITY' => 500000,
    'CURRENCY_ID' => 'RUB',
    'CONTACT_ID' => 123,
    'COMPANY_ID' => 456,
]);
```

#### Контакты (Contacts)

```php
$contacts = Bitrix24::contacts()->list();

$contact = Bitrix24::contacts()->add([
    'NAME' => 'Иван',
    'LAST_NAME' => 'Петров',
    'EMAIL' => [['VALUE' => 'ivan@example.com', 'VALUE_TYPE' => 'WORK']],
    'PHONE' => [['VALUE' => '+79001234567', 'VALUE_TYPE' => 'MOBILE']],
]);
```

#### Компании (Companies)

```php
$companies = Bitrix24::companies()->list();

$company = Bitrix24::companies()->add([
    'TITLE' => 'ООО "Рога и Копыта"',
    'COMPANY_TYPE' => 'CUSTOMER',
    'INDUSTRY' => 'IT',
]);
```

### Работа с задачами

```php
// Получить все задачи
$tasks = Bitrix24::tasks()->list();

// Создать задачу
$taskId = Bitrix24::tasks()->add([
    'TITLE' => 'Позвонить клиенту',
    'DESCRIPTION' => 'Обсудить условия сделки',
    'RESPONSIBLE_ID' => 1,
    'DEADLINE' => now()->addDays(3)->toDateTimeString(),
]);

// Обновить задачу
Bitrix24::tasks()->update($taskId, [
    'STATUS' => 5, // Завершена
]);
```

### Работа с пользователями

```php
// Получить всех пользователей
$users = Bitrix24::users()->list();

// Текущий пользователь
$currentUser = Bitrix24::users()->current();

// Поиск пользователей
$found = Bitrix24::users()->search('Иван');
```

### Работа с пользовательскими списками

```php
$lists = Bitrix24::lists();

// Получить все списки
$allLists = $lists->getAllLists();

// Получить элементы списка
$items = $lists->list($listId, [
    'PROPERTY_STATUS' => 'active'
]);

// Добавить элемент
$elementId = $lists->add($listId, [
    'NAME' => 'Новый элемент',
    'PROPERTY_VALUE' => 'test',
]);
```

### Универсальный CRM клиент

```php
$crm = Bitrix24::crm();

// Работа с любой сущностью
$items = $crm->getList('lead', ['STATUS_ID' => 'NEW']);
$fields = $crm->getFields('deal');
$id = $crm->add('contact', [...]);
```

---

## 🔥 Продвинутые возможности

### 1. Макросы (Macroable)

Добавляйте методы динамически без наследования:

```php
use Leko\Bitrix24\Clients\LeadClient;

// В AppServiceProvider::boot()
LeadClient::macro('getHotLeads', function () {
    return $this->list([
        'OPPORTUNITY' => ['>=', 100000],
        'STATUS_ID' => 'NEW',
    ]);
});

// Использование
$hotLeads = Bitrix24::leads()->getHotLeads();
```

#### Mixin - добавление класса методов

```php
class LeadAnalyticsMixin
{
    public function calculateConversion()
    {
        return function () {
            $all = count($this->list());
            $converted = count($this->list(['STATUS_ID' => 'CONVERTED']));
            return $all > 0 ? ($converted / $all * 100) : 0;
        };
    }
}

LeadClient::mixin(new LeadAnalyticsMixin());
$conversion = Bitrix24::leads()->calculateConversion();
```

### 2. События (Events)

Перехватывайте и обрабатывайте API вызовы:

```php
use Leko\Bitrix24\Events\ApiCallEvent;
use Leko\Bitrix24\Events\ApiCallFailedEvent;

// В EventServiceProvider
protected $listen = [
    ApiCallEvent::class => [
        LogBitrix24ApiCall::class,
    ],
    ApiCallFailedEvent::class => [
        NotifyOnBitrix24ApiFailure::class,
    ],
];

// Listener
class LogBitrix24ApiCall
{
    public function handle(ApiCallEvent $event): void
    {
        Log::info('Bitrix24 API Call', [
            'method' => $event->method,
            'duration' => $event->duration,
            'is_webhook' => $event->isWebhook,
        ]);
    }
}
```

### 3. Traits для расширения

#### HasCaching - кеширование запросов

```php
use Leko\Bitrix24\Clients\LeadClient;
use Leko\Bitrix24\Support\Traits\HasCaching;

class CachedLeadClient extends LeadClient
{
    use HasCaching;

    public function listCached(array $filter = []): array
    {
        $key = 'leads:' . md5(json_encode($filter));
        
        return $this->cached($key, function () use ($filter) {
            return $this->list($filter);
        }, 600); // 10 минут
    }
}
```

#### HasRateLimiting - защита от лимитов

```php
use Leko\Bitrix24\Support\Traits\HasRateLimiting;

class RateLimitedDealClient extends DealClient
{
    use HasRateLimiting;

    public function __construct($serviceBuilder)
    {
        parent::__construct($serviceBuilder);
        $this->rateLimit(50, 60); // 50 запросов в минуту
    }

    public function list(...): array
    {
        return $this->rateLimited('deals:list', function () {
            return parent::list(...);
        });
    }
}
```

### 4. Batch операции

```php
use Leko\Bitrix24\Support\BatchRequest;

$batch = new BatchRequest(Bitrix24::leads());

$batch
    ->add('lead1', 'crm.lead.add', ['fields' => ['TITLE' => 'Лид 1']])
    ->add('lead2', 'crm.lead.add', ['fields' => ['TITLE' => 'Лид 2']])
    ->add('lead3', 'crm.lead.add', ['fields' => ['TITLE' => 'Лид 3']]);

$results = $batch->execute();
```

### 5. Dependency Injection

Все клиенты имеют интерфейсы для внедрения зависимостей:

```php
use Leko\Bitrix24\Contracts\LeadClientInterface;
use Leko\Bitrix24\Contracts\DealClientInterface;

class SalesService
{
    public function __construct(
        private LeadClientInterface $leads,
        private DealClientInterface $deals
    ) {}

    public function convertLeadToDeal(int $leadId): ?int
    {
        $lead = $this->leads->get($leadId);
        
        return $this->deals->add([
            'TITLE' => $lead->TITLE,
            'CONTACT_ID' => $lead->CONTACT_ID,
        ]);
    }
}

// Laravel автоматически внедрит зависимости
$service = app(SalesService::class);
```

---

## 🎨 Расширение пакета

### Создание кастомного клиента

Класс должен наследовать `BaseClient` (для новой CRM-сущности удобнее `CrmEntityClient`):

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

### Регистрация клиента

```php
// В AppServiceProvider
use Leko\Bitrix24\Bitrix24Service;

public function boot(): void
{
    // Регистрация нового клиента
    Bitrix24Service::registerClient('analytics', AnalyticsClient::class);
    
    // Переопределение существующего
    Bitrix24Service::registerClient('leads', MyCustomLeadClient::class);
}

// Использование
$report = Bitrix24::client('analytics')->getSalesReport('month');
$leads = Bitrix24::leads(); // Вернёт MyCustomLeadClient
$same = Bitrix24::client('leads'); // Встроенный или зарегистрированный клиент
```

📖 **Подробнее:** [EXTENSIBILITY.md](EXTENSIBILITY.md), [ADVANCED_USAGE.md](ADVANCED_USAGE.md)

---

## 📚 API Reference

### Bitrix24 Facade

```php
Bitrix24::leads()      // LeadClientInterface
Bitrix24::deals()      // DealClientInterface
Bitrix24::contacts()   // ContactClientInterface
Bitrix24::companies()  // CompanyClientInterface
Bitrix24::tasks()      // TaskClientInterface
Bitrix24::users()      // UserClientInterface
Bitrix24::lists()      // ListClientInterface
Bitrix24::crm()        // CrmClientInterface
Bitrix24::client(string $name) // Встроенный или кастомный BaseClient
Bitrix24::sdk()        // Официальный ServiceBuilder

// Утилиты
Bitrix24::setConnection(string $name)
Bitrix24::setUserId(?int $id)
Bitrix24::getAuthorizationUrl(array $scopes = [])
Bitrix24::handleCallback(string $code)
Bitrix24::hasValidToken(?int $userId = null)
```

### Методы клиентов

Все CRM клиенты (Leads, Deals, Contacts, Companies) имеют одинаковый интерфейс:

```php
// LeadClient / DealClient / ContactClient / CompanyClient — DTO официального SDK
list(...): LeadItemResult[]|DealItemResult[]|ContactItemResult[]|CompanyItemResult[]
get(int $id): LeadItemResult|DealItemResult|ContactItemResult|CompanyItemResult
add(array $fields): int
update(int $id, array $fields): bool
delete(int $id): bool
fields(): array

// TaskClient
list(...): TaskItemResult[]
get(int $id): TaskItemResult
add(array $fields): int

// UserClient
list(...): UserItemResult[]
current(): UserItemResult
get(int $id): ?UserItemResult
search(string $query): UserItemResult[]
```

---

## 📡 События

Пакет генерирует следующие события:

### ApiCallEvent

Вызывается при успешном API запросе:

```php
public readonly string $method;      // Метод API
public readonly array $params;       // Параметры запроса
public readonly array|object|int|bool|string|null $result; // DTO SDK или REST-результат
public readonly float $duration;     // Длительность в секундах
public readonly bool $isWebhook;     // Webhook или OAuth
```

### ApiCallFailedEvent

Вызывается при ошибке API запроса:

```php
public readonly string $method;      // Метод API
public readonly array $params;       // Параметры запроса
public readonly Throwable $exception;// Исключение
public readonly float $duration;     // Длительность в секундах
```

---

## 🛡️ Middleware

### EnsureBitrix24Token

Проверяет наличие валидного токена:

```php
Route::middleware(['auth', 'bitrix24.token'])->group(function () {
    Route::get('/leads', [LeadController::class, 'index']);
});
```

Регистрация в `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    'bitrix24.token' => \Leko\Bitrix24\Http\Middleware\EnsureBitrix24Token::class,
];
```

---

## 🧪 Тестирование

```bash
composer test
```

### Моки для тестирования

```php
use Leko\Bitrix24\Contracts\LeadClientInterface;

public function test_lead_creation(): void
{
    $mock = Mockery::mock(LeadClientInterface::class);
    $mock->shouldReceive('add')
        ->once()
        ->with(['TITLE' => 'Test Lead'])
        ->andReturn(123);
        
    $this->app->instance(LeadClientInterface::class, $mock);
    
    // Ваш тест
}
```

---

## 🔄 Миграция

### С версии 1.1 на 1.2

См. [CHANGELOG.md](CHANGELOG.md). Публичный API расширения сохранён (`registerClient`, макросы, интерфейсы клиентов, `BaseClient` helpers).

Обратите внимание:
- `Bitrix24ServiceInterface` возвращает интерфейсы клиентов, а не конкретные классы
- OAuth и отсутствие токена бросают `Leko\Bitrix24\Exceptions\*` (наследники `RuntimeException`)
- `Bitrix24::client('leads')` находит и встроенные клиенты

### С версии 1.x на более новые

См. [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md) и [CHANGELOG.md](CHANGELOG.md).

---

## ❓ FAQ

<details>
<summary><strong>Как работать с несколькими Bitrix24 аккаунтами?</strong></summary>

```php
// В config/bitrix24.php добавьте подключения
'connections' => [
    'account1' => [...],
    'account2' => [...],
],

// Используйте
$leads1 = Bitrix24::setConnection('account1')->leads()->list();
$leads2 = Bitrix24::setConnection('account2')->leads()->list();
```
</details>

<details>
<summary><strong>Как обработать ошибки API?</strong></summary>

```php
use Leko\Bitrix24\Exceptions\AuthenticationException;
use Leko\Bitrix24\Exceptions\Bitrix24Exception;

try {
    $leads = Bitrix24::leads()->list();
} catch (AuthenticationException $e) {
    return redirect(Bitrix24::getAuthorizationUrl());
} catch (Bitrix24Exception $e) {
    Log::error('Bitrix24 Error', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
}
```
</details>

<details>
<summary><strong>Как добавить кеширование?</strong></summary>

Используйте trait `HasCaching`:

```php
use Leko\Bitrix24\Support\Traits\HasCaching;

class CachedClient extends LeadClient
{
    use HasCaching;
    
    public function listCached(): array
    {
        return $this->cached('leads', fn() => $this->list(), 600);
    }
}
```
</details>

<details>
<summary><strong>Как работать с пагинацией?</strong></summary>

```php
$start = 0;
$perPage = 50;
$allLeads = [];

do {
    $leads = Bitrix24::leads()->list(start: $start);
    $allLeads = array_merge($allLeads, $leads);
    $start += $perPage;
} while (count($leads) === $perPage);
```
</details>

---

## 📖 Документация

- **[EXTENSIBILITY.md](EXTENSIBILITY.md)** - Полное руководство по расширению пакета
- **[ADVANCED_USAGE.md](ADVANCED_USAGE.md)** - Продвинутые примеры использования
- **[FLEXIBILITY_SUMMARY.md](FLEXIBILITY_SUMMARY.md)** - Резюме гибкости архитектуры
- **[MIGRATION_GUIDE.md](MIGRATION_GUIDE.md)** - Руководство по миграции
- **[CHANGELOG.md](CHANGELOG.md)** - История изменений
- **[CONTRIBUTING.md](CONTRIBUTING.md)** - Правила контрибуции

---

## 🤝 Поддержка

- 📧 Email: [lucy@leko.team](mailto:lucy@leko.team)
- 🐛 Issues: [GitHub Issues](https://github.com/sliva-name/bitrix24-laravel/issues)
- 💬 Discussions: [GitHub Discussions](https://github.com/sliva-name/bitrix24-laravel/discussions)

---

## 🙏 Благодарности

- [Bitrix24 PHP SDK](https://github.com/bitrix24/b24phpsdk) - за основу работы с API
- [Laravel Framework](https://laravel.com) - за отличный фреймворк
- Всем контрибьюторам проекта

---

## 📄 Лицензия

Этот пакет является открытым ПО, лицензированным под [MIT license](LICENSE).

---

## 🌟 Вклад в проект

Мы приветствуем вклад! Пожалуйста:

1. Fork репозитория
2. Создайте feature branch (`git checkout -b feature/amazing-feature`)
3. Commit изменений (`git commit -m 'Add amazing feature'`)
4. Push в branch (`git push origin feature/amazing-feature`)
5. Откройте Pull Request

См. [CONTRIBUTING.md](CONTRIBUTING.md) для подробностей.

---

<div align="center">
  <p>Сделано с ❤️ командой <a href="https://leko.team">Leko Team</a></p>
  <p>
    <a href="#-содержание">⬆ Вернуться к началу</a>
  </p>
</div>
