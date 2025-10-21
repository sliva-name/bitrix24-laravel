# Laravel Bitrix24 Integration

[![Latest Version](https://img.shields.io/packagist/v/leko/laravel-bitrix24.svg)](https://packagist.org/packages/leko/laravel-bitrix24)
[![License](https://img.shields.io/packagist/l/leko/laravel-bitrix24.svg)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/leko/laravel-bitrix24.svg)](https://packagist.org/packages/leko/laravel-bitrix24)
[![Laravel Version](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x%20%7C%2012.x-red.svg)](https://laravel.com)

Пакет для интеграции Laravel с Bitrix24 CRM. Поддерживает OAuth и Webhook аутентификацию.

## Возможности

- ✅ Двойной режим аутентификации (OAuth / Webhook)
- ✅ Управление токенами с автоматическим обновлением
- ✅ Кеширование токенов
- ✅ CRM клиенты (Лиды, Сделки, Контакты, Компании)
- ✅ Работа с пользователями и задачами
- ✅ Готовые контроллеры и маршруты
- ✅ Middleware для защиты роутов
- ✅ PSR-12 стандарты кода
- ✅ SOLID принципы

## Требования

- PHP 8.2+
- Laravel 10.x, 11.x или 12.x
- PostgreSQL (рекомендуется)

## Установка

### 1. Установка через Composer

```bash
composer require leko/laravel-bitrix24
```

### 2. Публикация конфигурации и миграций

```bash
php artisan vendor:publish --tag=bitrix24-config
php artisan vendor:publish --tag=bitrix24-migrations
```

### 3. Запуск миграций

```bash
php artisan migrate
```

### 4. Настройка .env

Добавьте следующие переменные в ваш `.env` файл:

#### Для OAuth:
```env
BITRIX24_AUTH_TYPE=oauth
BITRIX24_DOMAIN=your-domain.bitrix24.ru
BITRIX24_CLIENT_ID=local.xxxxxxxx.yyyyyyyy
BITRIX24_CLIENT_SECRET=your_client_secret
BITRIX24_REDIRECT_URI=${APP_URL}/api/bitrix24/auth/callback
```

#### Для Webhook:
```env
BITRIX24_AUTH_TYPE=webhook
BITRIX24_WEBHOOK_URL=https://your-domain.bitrix24.ru/rest/123/your_webhook_key/
```

#### Дополнительные настройки:
```env
BITRIX24_DEFAULT_CONNECTION=main
BITRIX24_TOKEN_STORAGE=database
BITRIX24_CACHE_STORE=database
BITRIX24_LOGGING_ENABLED=false
BITRIX24_API_TIMEOUT=30
```

## Использование

### OAuth аутентификация

```php
use Leko\Bitrix24\Facades\Bitrix24;

// Получить URL авторизации
$authUrl = Bitrix24::getAuthorizationUrl();

// Обработать callback
$result = Bitrix24::handleCallback($code);

// Проверить наличие токена
if (Bitrix24::hasValidToken()) {
    // Токен валиден
}
```

### Работа с CRM

#### Лиды

```php
use Leko\Bitrix24\Facades\Bitrix24;

// Получить список лидов
$leads = Bitrix24::leads()->list(
    filter: ['STATUS_ID' => 'NEW'],
    select: ['ID', 'TITLE', 'NAME'],
    order: ['ID' => 'DESC'],
    start: 0
);

// Получить лид по ID
$lead = Bitrix24::leads()->get(123);

// Создать лид
$newLead = Bitrix24::leads()->add([
    'TITLE' => 'Новый лид',
    'NAME' => 'Иван',
    'LAST_NAME' => 'Иванов',
    'EMAIL' => [['VALUE' => 'ivan@example.com', 'VALUE_TYPE' => 'WORK']],
    'PHONE' => [['VALUE' => '+79001234567', 'VALUE_TYPE' => 'MOBILE']],
]);

// Обновить лид
$updated = Bitrix24::leads()->update(123, [
    'TITLE' => 'Обновленный лид',
    'STATUS_ID' => 'IN_PROCESS',
]);

// Удалить лид
$deleted = Bitrix24::leads()->delete(123);

// Получить поля лида
$fields = Bitrix24::leads()->fields();
```

#### Сделки

```php
// Получить список сделок
$deals = Bitrix24::deals()->list();

// Получить сделку по ID
$deal = Bitrix24::deals()->get(456);

// Создать сделку
$newDeal = Bitrix24::deals()->add([
    'TITLE' => 'Новая сделка',
    'OPPORTUNITY' => 10000,
    'CURRENCY_ID' => 'RUB',
]);
```

#### Контакты

```php
// Получить список контактов
$contacts = Bitrix24::contacts()->list();

// Создать контакт
$newContact = Bitrix24::contacts()->add([
    'NAME' => 'Иван',
    'LAST_NAME' => 'Петров',
    'EMAIL' => [['VALUE' => 'ivan@example.com', 'VALUE_TYPE' => 'WORK']],
]);
```

#### Компании

```php
// Получить список компаний
$companies = Bitrix24::companies()->list();

// Создать компанию
$newCompany = Bitrix24::companies()->add([
    'TITLE' => 'ООО "Рога и Копыта"',
    'COMPANY_TYPE' => 'CUSTOMER',
]);
```

### Работа с пользователями

```php
// Получить список пользователей
$users = Bitrix24::users()->list();

// Получить текущего пользователя
$currentUser = Bitrix24::users()->current();
```

### Работа с задачами

```php
// Получить список задач
$tasks = Bitrix24::tasks()->list();
```

### Использование с конкретным пользователем

```php
// Установить пользователя для работы с токенами
$leads = Bitrix24::setUserId(1)->leads()->list();

// Использовать другое подключение
$leads = Bitrix24::setConnection('secondary')->leads()->list();
```

## API Роуты

Пакет автоматически регистрирует следующие роуты:

### Аутентификация (OAuth)
- `GET /api/bitrix24/auth/authorize` - Начать OAuth авторизацию
- `POST /api/bitrix24/auth/callback` - Обработать OAuth callback
- `GET /api/bitrix24/auth/status` - Проверить статус токена (требует аутентификации)
- `POST /api/bitrix24/auth/revoke` - Отозвать токен (требует аутентификации)

### Webhook
- `POST /api/bitrix24/webhook/handle` - Обработать входящий webhook
- `GET /api/bitrix24/webhook/pending` - Получить ожидающие webhook (требует аутентификации)
- `GET /api/bitrix24/webhook/failed` - Получить неудавшиеся webhook (требует аутентификации)

### Примеры API (защищены middleware)
- `GET /api/bitrix24/leads` - Получить список лидов
- `GET /api/bitrix24/contacts` - Получить список контактов
- `GET /api/bitrix24/companies` - Получить список компаний
- `GET /api/bitrix24/deals` - Получить список сделок
- `GET /api/bitrix24/tasks` - Получить список задач
- `GET /api/bitrix24/users` - Получить список пользователей

## Контроллеры

Вы можете использовать готовые контроллеры или создать свои:

```php
use Leko\Bitrix24\Http\Controllers\Bitrix24LeadController;

class MyLeadController extends Bitrix24LeadController
{
    // Переопределите методы при необходимости
}
```

## Middleware

Защитите свои роуты с помощью middleware:

```php
use Leko\Bitrix24\Http\Middleware\EnsureBitrix24Token;

Route::middleware([EnsureBitrix24Token::class])->group(function () {
    Route::get('/protected', function () {
        return Bitrix24::leads()->list();
    });
});
```

## Обработка ошибок

Пакет выбрасывает исключения при ошибках API:

```php
use Leko\Bitrix24\Facades\Bitrix24;

try {
    $leads = Bitrix24::leads()->list();
} catch (\RuntimeException $e) {
    // Обработка ошибки API
    logger()->error('Bitrix24 API Error: ' . $e->getMessage());
}
```

## Логирование

Включите логирование в `.env`:

```env
BITRIX24_LOGGING_ENABLED=true
BITRIX24_LOG_CHANNEL=daily
```

## Тестирование

```bash
composer test
```

## Changelog

Смотрите [CHANGELOG](CHANGELOG.md) для получения информации об изменениях.

## Лицензия

MIT License. Смотрите [LICENSE](LICENSE) для получения дополнительной информации.

## Авторы

- **Leko Team** - [lucy@leko.team](mailto:lucy@leko.team)

## Поддержка

Если вы нашли баг или хотите предложить улучшение, пожалуйста, создайте issue в репозитории.

## Вклад в проект

Мы приветствуем вклад в развитие проекта! Пожалуйста, ознакомьтесь с нашими [правилами контрибуции](CONTRIBUTING.md).

## Благодарности

- [Bitrix24 PHP SDK](https://github.com/bitrix24/b24phpsdk) - за основу работы с API
- [Laravel Framework](https://laravel.com) - за отличный фреймворк
- Всем контрибьюторам проекта