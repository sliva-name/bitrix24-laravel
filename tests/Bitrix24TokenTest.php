<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Tests;

use Carbon\Carbon;
use Leko\Bitrix24\Models\Bitrix24Token;

class Bitrix24TokenTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_is_expiring_soon_does_not_mutate_expires_at(): void
    {
        $now = Carbon::parse('2026-08-13 12:00:00');
        Carbon::setTestNow($now);

        $token = new Bitrix24Token();
        $token->expires_at = $now->copy()->addMinutes(3);
        $originalTimestamp = $token->expires_at->getTimestamp();

        $this->assertTrue($token->isExpiringSoon());
        $this->assertSame($originalTimestamp, $token->expires_at->getTimestamp());
    }

    public function test_token_is_not_expiring_soon_when_far_from_expiry(): void
    {
        $now = Carbon::parse('2026-08-13 12:00:00');
        Carbon::setTestNow($now);

        $token = new Bitrix24Token();
        $token->expires_at = $now->copy()->addHour();

        $this->assertFalse($token->isExpiringSoon());
        $this->assertFalse($token->isExpired());
    }
}
