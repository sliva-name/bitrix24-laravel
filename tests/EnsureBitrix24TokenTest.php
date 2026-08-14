<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Tests;

use Illuminate\Http\Request;
use Leko\Bitrix24\Http\Middleware\EnsureBitrix24Token;
use Leko\Bitrix24\TokenManager;
use Symfony\Component\HttpFoundation\Response;

class EnsureBitrix24TokenTest extends TestCase
{
    public function test_it_rejects_guest(): void
    {
        $response = $this->handle(Request::create('/leads'));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_it_rejects_user_without_token(): void
    {
        $request = Request::create('/leads');
        $request->setUserResolver(fn () => (object) ['id' => 5]);

        $response = $this->handle($request);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_it_allows_user_with_valid_token(): void
    {
        $this->app->make(TokenManager::class)->storeToken([
            'domain' => 'portal.bitrix24.ru',
            'access_token' => 'access',
            'refresh_token' => 'refresh',
            'expires_in' => 3600,
        ], 5);

        $request = Request::create('/leads');
        $request->setUserResolver(fn () => (object) ['id' => 5]);
        $called = false;

        $response = $this->handle($request, function () use (&$called) {
            $called = true;

            return response('ok');
        });

        $this->assertTrue($called);
        $this->assertSame(200, $response->getStatusCode());
    }

    private function handle(Request $request, ?callable $next = null): Response
    {
        $middleware = new EnsureBitrix24Token();

        return $middleware->handle(
            $request,
            $next ?? fn () => response('ok')
        );
    }
}
