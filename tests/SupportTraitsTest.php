<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Tests;

use Illuminate\Support\Facades\RateLimiter;
use Leko\Bitrix24\Support\Traits\HasCaching;
use Leko\Bitrix24\Support\Traits\HasRateLimiting;
use RuntimeException;

class SupportTraitsTest extends TestCase
{
    public function test_caching_trait_remembers_and_forgets(): void
    {
        $stub = new class {
            use HasCaching;

            public function remember(): string
            {
                return $this->cached('answer', fn (): string => '42');
            }

            public function forget(): bool
            {
                return $this->forgetCache('answer');
            }

            public function flush(): void
            {
                $this->flushCache();
            }
        };

        $this->assertSame('42', $stub->remember());
        $this->assertTrue($stub->forget());
        $stub->flush();
    }

    public function test_rate_limiting_trait_blocks_excess_attempts(): void
    {
        RateLimiter::clear('bitrix24-rate-limit-demo');

        $stub = new class {
            use HasRateLimiting;

            public function ping(): string
            {
                return $this->rateLimited('demo', fn (): string => 'ok');
            }
        };

        $stub->rateLimit(1, 60);

        $this->assertSame('ok', $stub->ping());

        $this->expectException(RuntimeException::class);
        $stub->ping();
    }
}
