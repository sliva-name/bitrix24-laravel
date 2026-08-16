<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Tests;

use Leko\Bitrix24\Support\Domain;

class DomainTest extends TestCase
{
    public function test_it_strips_scheme_and_trailing_slash(): void
    {
        $this->assertSame('portal.bitrix24.ru', Domain::normalize('https://portal.bitrix24.ru/'));
        $this->assertSame('portal.bitrix24.ru', Domain::normalize('http://portal.bitrix24.ru'));
        $this->assertSame('portal.bitrix24.ru', Domain::normalize(' portal.bitrix24.ru/ '));
        $this->assertSame('portal.bitrix24.ru', Domain::normalize('portal.bitrix24.ru'));
    }

    public function test_it_extracts_portal_host_from_client_endpoint(): void
    {
        $this->assertSame(
            'portal.bitrix24.ru',
            Domain::fromClientEndpoint('https://portal.bitrix24.ru/rest/')
        );
        $this->assertSame(
            'account.bitrix24.com',
            Domain::fromClientEndpoint('https://account.bitrix24.com/rest/')
        );
        $this->assertNull(Domain::fromClientEndpoint(null));
        $this->assertNull(Domain::fromClientEndpoint(''));
        $this->assertNull(Domain::fromClientEndpoint('not-a-url'));
    }

    public function test_it_detects_authorization_server_hosts(): void
    {
        $this->assertTrue(Domain::isAuthorizationServer('oauth.bitrix.info'));
        $this->assertTrue(Domain::isAuthorizationServer('https://oauth.bitrix.info/'));
        $this->assertTrue(Domain::isAuthorizationServer('oauth.bitrix24.tech'));
        $this->assertFalse(Domain::isAuthorizationServer('portal.bitrix24.ru'));
        $this->assertFalse(Domain::isAuthorizationServer('oauth.example.com'));
    }
}
