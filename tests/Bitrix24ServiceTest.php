<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Tests;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Leko\Bitrix24\Bitrix24Service;
use Leko\Bitrix24\Clients\LeadClient;
use Leko\Bitrix24\Exceptions\UnknownClientException;
use Leko\Bitrix24\Facades\Bitrix24;
use Leko\Bitrix24\TokenManager;

class Bitrix24ServiceTest extends TestCase
{
    public function test_handle_callback_stores_token(): void
    {
        Http::fake([
            'https://oauth.bitrix.info/oauth/token/' => Http::response([
                'access_token' => 'access',
                'refresh_token' => 'refresh',
                'expires_in' => 3600,
                'domain' => 'portal.bitrix24.ru',
                'scope' => 'crm',
            ]),
        ]);

        $result = Bitrix24::setUserId(12)->handleCallback('oauth-code');

        $this->assertArrayHasKey('token_id', $result);
        $this->assertSame('portal.bitrix24.ru', $result['domain']);
        $this->assertTrue(Bitrix24::hasValidToken(12));
    }

    public function test_client_resolves_builtin_and_custom_clients(): void
    {
        $this->oauthConnection([
            'type' => 'webhook',
            'webhook_url' => 'https://portal.bitrix24.ru/rest/1/hook/',
        ]);

        $this->assertInstanceOf(LeadClient::class, Bitrix24::client('leads'));

        Bitrix24Service::registerClient('dummy', TestClient::class);

        $this->assertInstanceOf(TestClient::class, Bitrix24::client('dummy'));
    }

    public function test_unknown_client_throws(): void
    {
        $this->expectException(UnknownClientException::class);

        Bitrix24::client('analytics');
    }

    public function test_register_client_rejects_invalid_class(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Bitrix24Service::registerClient('bad', \stdClass::class);
    }

    public function test_set_connection_is_fluent(): void
    {
        $service = $this->app->make(Bitrix24Service::class);

        $this->assertSame($service, $service->setConnection('main')->setUserId(1));
        $this->assertInstanceOf(TokenManager::class, $this->app->make(TokenManager::class));
    }
}
