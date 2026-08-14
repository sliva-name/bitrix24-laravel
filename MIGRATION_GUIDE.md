# Миграция на пакет sliva-name/bitrix24-laravel

## Установка

```bash
composer require sliva-name/bitrix24-laravel
php artisan vendor:publish --tag=bitrix24-config
php artisan vendor:publish --tag=bitrix24-migrations
php artisan migrate
```

Если раньше интеграция жила в `App\...`, замените namespace:

```php
use Leko\Bitrix24\Bitrix24Service;
use Leko\Bitrix24\Facades\Bitrix24;
use Leko\Bitrix24\Models\Bitrix24Token;
use Leko\Bitrix24\Contracts\LeadClientInterface;
use Leko\Bitrix24\Http\Middleware\EnsureBitrix24Token;
```

Провайдер подключается через package auto-discovery. Алиас middleware `bitrix24.token` регистрируется пакетом.

Не удаляйте уже применённые миграции токенов и вебхуков.

## С версии 1.1 на 1.2+

См. [CHANGELOG.md](CHANGELOG.md).

- `Bitrix24ServiceInterface` возвращает интерфейсы клиентов
- OAuth и отсутствие токена бросают `Leko\Bitrix24\Exceptions\*`
- `Bitrix24::client('leads')` находит и встроенные клиенты
- Входящие вебхуки включаются явно: `BITRIX24_WEBHOOK_ENABLED=true`
