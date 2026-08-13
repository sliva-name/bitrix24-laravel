<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Tests;

use Leko\Bitrix24\Providers\Bitrix24ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            Bitrix24ServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('bitrix24.cache.store', 'array');
        $app['config']->set('bitrix24.logging.enabled', false);
    }
}
