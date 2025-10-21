# Руководство по миграции на пакет Laravel Bitrix24

## Шаг 1: Установка пакета

### 1.1. Обновите `composer.json`

Добавьте пакет в зависимости и локальный репозиторий:

```json
{
    "require": {
        "leko/laravel-bitrix24": "*"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../packages/leko/laravel-bitrix24"
        }
    ]
}
```

### 1.2. Установите зависимости

```bash
composer update
```

## Шаг 2: Обновите Service Provider

### 2.1. Удалите старый провайдер

В файле `bootstrap/providers.php` **удалите**:

```php
App\Providers\Bitrix24ServiceProvider::class,
```

**Добавьте** новый провайдер:

```php
Leko\Bitrix24\Providers\Bitrix24ServiceProvider::class,
```

### 2.2. Удалите старые файлы (опционально)

После успешной миграции вы можете удалить следующие директории из `app/`:

- `app/Services/Bitrix24/`
- `app/Http/Controllers/Bitrix24*`
- `app/Http/Controllers/Examples/Bitrix24*`
- `app/Http/Middleware/EnsureBitrix24Token.php`
- `app/Http/Resources/Bitrix24*`
- `app/Models/Bitrix24*`
- `app/Repositories/Bitrix24Token/`
- `app/Repositories/Bitrix24Webhook/`
- `app/Facades/Bitrix24.php`
- `config/bitrix24.php`
- `routes/bitrix24.php`

**Примечание:** Не удаляйте миграции из `database/migrations/`, они нужны для базы данных!

## Шаг 3: Обновите Use Statements

### 3.1. Во всех файлах замените namespace:

**Было:**
```php
use App\Services\Bitrix24\Bitrix24Service;
use App\Facades\Bitrix24;
use App\Models\Bitrix24Token;
use App\Repositories\Bitrix24Token\Bitrix24TokenRepositoryInterface;
```

**Стало:**
```php
use Leko\Bitrix24\Bitrix24Service;
use Leko\Bitrix24\Facades\Bitrix24;
use Leko\Bitrix24\Models\Bitrix24Token;
use Leko\Bitrix24\Repositories\Bitrix24Token\Bitrix24TokenRepositoryInterface;
```

### 3.2. Найдите все файлы с импортами:

```bash
# Найти файлы с старыми импортами
grep -r "use App\\\\Services\\\\Bitrix24" app/
grep -r "use App\\\\Facades\\\\Bitrix24" app/
grep -r "use App\\\\Models\\\\Bitrix24" app/
grep -r "use App\\\\Repositories\\\\Bitrix24" app/
grep -r "use App\\\\Http\\\\Controllers\\\\Bitrix24" app/
grep -r "use App\\\\Http\\\\Middleware\\\\EnsureBitrix24" app/
```

### 3.3. Замените импорты автоматически:

```bash
# Замена в файлах (Linux/Mac)
find app/ -type f -name "*.php" -exec sed -i 's/use App\\Services\\Bitrix24/use Leko\\Bitrix24/g' {} +
find app/ -type f -name "*.php" -exec sed -i 's/use App\\Facades\\Bitrix24/use Leko\\Bitrix24\\Facades\\Bitrix24/g' {} +
find app/ -type f -name "*.php" -exec sed -i 's/use App\\Models\\Bitrix24/use Leko\\Bitrix24\\Models\\Bitrix24/g' {} +
find app/ -type f -name "*.php" -exec sed -i 's/use App\\Repositories\\Bitrix24/use Leko\\Bitrix24\\Repositories\\Bitrix24/g' {} +
find app/ -type f -name "*.php" -exec sed -i 's/use App\\Http\\Controllers\\Bitrix24/use Leko\\Bitrix24\\Http\\Controllers\\Bitrix24/g' {} +
find app/ -type f -name "*.php" -exec sed -i 's/use App\\Http\\Middleware\\EnsureBitrix24/use Leko\\Bitrix24\\Http\\Middleware\\EnsureBitrix24/g' {} +
find app/ -type f -name "*.php" -exec sed -i 's/use App\\Http\\Resources\\Bitrix24/use Leko\\Bitrix24\\Http\\Resources\\Bitrix24/g' {} +
find app/ -type f -name "*.php" -exec sed -i 's/use App\\Http\\Traits\\ApiResponse/use Leko\\Bitrix24\\Http\\Traits\\ApiResponse/g' {} +
```

## Шаг 4: Обновите Routes (если используете кастомные)

### 4.1. Удалите старую регистрацию роутов

Если вы регистрировали роуты вручную в `routes/api.php` или `routes/web.php`, удалите:

```php
require __DIR__.'/bitrix24.php';
```

Пакет автоматически регистрирует роуты через Service Provider!

### 4.2. Если используете кастомные роуты

Обновите импорты контроллеров:

```php
use Leko\Bitrix24\Http\Controllers\Bitrix24AuthController;
use Leko\Bitrix24\Http\Controllers\Bitrix24WebhookController;
use Leko\Bitrix24\Http\Middleware\EnsureBitrix24Token;
```

## Шаг 5: Проверка

### 5.1. Очистите кеш

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 5.2. Запустите Tinker для проверки

```bash
php artisan tinker
```

```php
// Проверьте, что сервис работает
$service = app('bitrix24');
echo get_class($service); // Leko\Bitrix24\Bitrix24Service

// Проверьте фасад
use Leko\Bitrix24\Facades\Bitrix24;
$users = Bitrix24::users()->current();
print_r($users);
```

### 5.3. Проверьте роуты

```bash
php artisan route:list --path=bitrix24
```

Вы должны увидеть все роуты пакета.

## Шаг 6: Тестирование

Протестируйте основные функции:

- ✅ OAuth авторизация
- ✅ Получение данных из CRM
- ✅ Webhook обработка
- ✅ Работа с токенами

## Типичные проблемы

### Проблема: Class 'Leko\Bitrix24\...' not found

**Решение:**
```bash
composer dump-autoload
php artisan config:clear
```

### Проблема: Route not found

**Решение:**
```bash
php artisan route:clear
php artisan cache:clear
```

### Проблема: Provider not registered

**Решение:** Убедитесь, что в `bootstrap/providers.php` добавлен:
```php
Leko\Bitrix24\Providers\Bitrix24ServiceProvider::class,
```

## Rollback (откат)

Если что-то пошло не так:

1. Удалите пакет из `composer.json`
2. Восстановите старый `App\Providers\Bitrix24ServiceProvider::class` в `bootstrap/providers.php`
3. Верните все старые файлы из backup
4. Запустите:
```bash
composer update
php artisan config:clear
```

## Преимущества пакета

✅ Переиспользуемый код между проектами
✅ Простое обновление через composer
✅ Изолированная кодовая база
✅ Легко тестировать
✅ Можно публиковать на Packagist

---

**Готово!** Теперь ваше приложение использует пакет Laravel Bitrix24! 🎉

