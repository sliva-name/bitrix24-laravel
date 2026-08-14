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
}
