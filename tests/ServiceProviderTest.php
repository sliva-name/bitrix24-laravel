<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Tests;

use Leko\Bitrix24\Bitrix24Service;
use Leko\Bitrix24\Contracts\Bitrix24ServiceInterface;
use Leko\Bitrix24\Facades\Bitrix24;
use Leko\Bitrix24\TokenManager;

class ServiceProviderTest extends TestCase
{
    public function test_package_bindings_are_registered(): void
    {
        $this->assertTrue($this->app->bound('bitrix24'));
        $this->assertTrue($this->app->bound(Bitrix24ServiceInterface::class));
        $this->assertTrue($this->app->bound(TokenManager::class));

        $this->assertInstanceOf(Bitrix24Service::class, $this->app->make('bitrix24'));
        $this->assertInstanceOf(Bitrix24Service::class, Bitrix24::getFacadeRoot());
    }

    public function test_authorization_url_strips_protocol_from_domain(): void
    {
        config()->set('bitrix24.connections.main', [
            'type' => 'oauth',
            'domain' => 'https://portal.bitrix24.ru/',
            'client_id' => 'local.app',
            'client_secret' => 'secret',
            'redirect_uri' => 'https://app.test/callback',
            'scope' => 'crm',
        ]);

        $url = Bitrix24::getAuthorizationUrl(['crm'], 'state-token');

        $this->assertStringStartsWith('https://portal.bitrix24.ru/oauth/authorize/?', $url);
        $this->assertStringNotContainsString('https://https://', $url);
    }
}
