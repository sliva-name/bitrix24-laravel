<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Leko\Bitrix24\Bitrix24Service;
use Leko\Bitrix24\Contracts\Bitrix24ServiceInterface;
use Leko\Bitrix24\Repositories\Bitrix24Token\Bitrix24TokenRepository;
use Leko\Bitrix24\Repositories\Bitrix24Token\Bitrix24TokenRepositoryInterface;
use Leko\Bitrix24\Repositories\Bitrix24Webhook\Bitrix24WebhookRepository;
use Leko\Bitrix24\Repositories\Bitrix24Webhook\Bitrix24WebhookRepositoryInterface;
use Leko\Bitrix24\TokenManager;

/**
 * Service Provider для пакета Laravel Bitrix24
 */
class Bitrix24ServiceProvider extends ServiceProvider
{
    /**
     * Регистрация сервисов в контейнере.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/bitrix24.php', 'bitrix24');

        $this->app->bind(Bitrix24TokenRepositoryInterface::class, Bitrix24TokenRepository::class);
        $this->app->bind(Bitrix24WebhookRepositoryInterface::class, Bitrix24WebhookRepository::class);

        $this->app->singleton(TokenManager::class, function ($app) {
            return new TokenManager(
                $app->make(Bitrix24TokenRepositoryInterface::class),
                $app['cache']->store(config('bitrix24.cache.store'))
            );
        });

        $this->app->singleton(Bitrix24ServiceInterface::class, function ($app) {
            return new Bitrix24Service($app->make(TokenManager::class));
        });

        $this->app->singleton('bitrix24', function ($app) {
            return $app->make(Bitrix24ServiceInterface::class);
        });
    }

    /**
     * Загрузка сервисов приложения.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/bitrix24.php' => config_path('bitrix24.php'),
            ], 'bitrix24-config');

            $this->publishes([
                __DIR__ . '/../../database/migrations/' => database_path('migrations'),
            ], 'bitrix24-migrations');
        }

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->registerRoutes();
    }

    /**
     * Регистрация роутов пакета.
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        // Routes are now handled in the main application
        // No need to load routes from package
    }

    /**
     * Получить список сервисов, предоставляемых провайдером.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            'bitrix24',
            Bitrix24ServiceInterface::class,
            TokenManager::class,
            Bitrix24TokenRepositoryInterface::class,
            Bitrix24WebhookRepositoryInterface::class,
        ];
    }
}

