<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Leko\Bitrix24\Bitrix24Service;
use Leko\Bitrix24\Providers\Bitrix24ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Bitrix24Service::flushCustomClients();
        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        Bitrix24Service::flushCustomClients();

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            Bitrix24ServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('bitrix24.cache.store', 'array');
        $app['config']->set('bitrix24.logging.enabled', false);
        $app['config']->set('bitrix24.default', 'main');
        $app['config']->set('bitrix24.connections.main', [
            'type' => 'oauth',
            'domain' => 'portal.bitrix24.ru',
            'client_id' => 'local.app',
            'client_secret' => 'secret',
            'redirect_uri' => 'https://app.test/callback',
            'scope' => 'crm,task',
            'oauth_server' => 'https://oauth.bitrix.info/',
        ]);
    }

    protected function setUpDatabase(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
            });
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * @param array<string, mixed> $overrides
     */
    protected function oauthConnection(array $overrides = []): void
    {
        config()->set('bitrix24.connections.main', array_merge(
            config('bitrix24.connections.main', []),
            $overrides
        ));
    }
}
