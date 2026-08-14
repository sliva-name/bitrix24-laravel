<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Tests;

use Leko\Bitrix24\Bitrix24Service;
use Leko\Bitrix24\Contracts\Bitrix24ServiceInterface;
use Leko\Bitrix24\Contracts\LeadClientInterface;
use Leko\Bitrix24\Facades\Bitrix24;
use Leko\Bitrix24\TokenManager;

class ServiceProviderTest extends TestCase
{
    public function test_package_bindings_are_registered(): void
    {
        $this->assertTrue($this->app->bound('bitrix24'));
        $this->assertTrue($this->app->bound(Bitrix24ServiceInterface::class));
        $this->assertTrue($this->app->bound(TokenManager::class));

        $service = $this->app->make('bitrix24');

        $this->assertInstanceOf(Bitrix24Service::class, $service);
        $this->assertSame($service, $this->app->make(Bitrix24ServiceInterface::class));
        $this->assertSame($service, $this->app->make(Bitrix24Service::class));
        $this->assertInstanceOf(Bitrix24Service::class, Bitrix24::getFacadeRoot());
        $this->assertTrue($this->app->bound(LeadClientInterface::class));
    }

    public function test_authorization_url_strips_protocol_from_domain(): void
    {
        $this->oauthConnection([
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
        $this->assertStringContainsString('state=state-token', $url);
        $this->assertStringContainsString('client_id=local.app', $url);
    }

    public function test_authorization_url_uses_config_scopes_when_empty(): void
    {
        $url = Bitrix24::getAuthorizationUrl([], 'csrf');

        $this->assertStringContainsString('scope=crm%2Ctask', $url);
    }
}
