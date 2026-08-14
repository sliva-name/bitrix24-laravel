<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Tests;

use Leko\Bitrix24\Exceptions\UnknownConnectionException;
use Leko\Bitrix24\Support\ConnectionConfig;

class ConnectionConfigTest extends TestCase
{
    public function test_it_loads_connection_and_normalizes_values(): void
    {
        $this->oauthConnection([
            'type' => 'webhook',
            'domain' => 'https://portal.bitrix24.ru/',
            'webhook_url' => 'https://portal.bitrix24.ru/rest/1/hook/',
            'scope' => 'crm, task, ',
            'oauth_server' => 'https://oauth.bitrix.info',
        ]);

        $config = ConnectionConfig::load('main');

        $this->assertTrue($config->isWebhook());
        $this->assertSame('portal.bitrix24.ru', $config->domain());
        $this->assertSame(['crm', 'task'], $config->scopes());
        $this->assertSame('https://oauth.bitrix.info/', $config->oauthServer());
        $this->assertSame('https://oauth.bitrix.info/oauth/token/', $config->oauthTokenUrl());
    }

    public function test_it_throws_for_unknown_connection(): void
    {
        $this->expectException(UnknownConnectionException::class);

        ConnectionConfig::load('missing');
    }
}
