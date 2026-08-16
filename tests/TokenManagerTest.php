<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Tests;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Leko\Bitrix24\Exceptions\OAuthException;
use Leko\Bitrix24\Models\Bitrix24Token;
use Leko\Bitrix24\TokenManager;

class TokenManagerTest extends TestCase
{
    private TokenManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->app->make(TokenManager::class);
    }

    public function test_it_stores_and_returns_a_cached_token(): void
    {
        $token = $this->manager->storeToken([
            'domain' => 'https://portal.bitrix24.ru/',
            'access_token' => 'access',
            'refresh_token' => 'refresh',
            'expires_in' => 3600,
            'scope' => ['crm'],
        ], 7);

        $this->assertSame('portal.bitrix24.ru', $token->domain);
        $this->assertTrue($token->is_active);
        $this->assertSame($token->id, $this->manager->getToken(7)?->id);
    }

    public function test_it_exchanges_authorization_code(): void
    {
        Http::fake([
            'https://oauth.bitrix.info/oauth/token/' => Http::response([
                'access_token' => 'new-access',
                'refresh_token' => 'new-refresh',
                'expires_in' => 1800,
                'domain' => 'https://portal.bitrix24.ru/',
                'scope' => 'crm,user',
                'member_id' => 'member-1',
            ]),
        ]);

        $payload = $this->manager->exchangeAuthorizationCode('auth-code');

        $this->assertSame('portal.bitrix24.ru', $payload['domain']);
        $this->assertSame('new-access', $payload['access_token']);
        $this->assertSame(['crm', 'user'], $payload['scope']);
        $this->assertSame('member-1', $payload['metadata']['member_id']);
    }

    public function test_it_refreshes_an_expired_token(): void
    {
        $token = $this->manager->storeToken([
            'domain' => 'portal.bitrix24.ru',
            'access_token' => 'old-access',
            'refresh_token' => 'old-refresh',
            'expires' => Carbon::now()->subHour()->getTimestamp(),
        ], 3);

        Http::fake([
            'https://oauth.bitrix.info/oauth/token/' => Http::response([
                'access_token' => 'fresh-access',
                'refresh_token' => 'fresh-refresh',
                'expires_in' => 3600,
                'domain' => 'https://portal.bitrix24.ru/',
            ]),
        ]);

        $refreshed = $this->manager->getToken(3);

        $this->assertNotNull($refreshed);
        $this->assertSame('fresh-access', $refreshed->access_token);
        $this->assertSame('portal.bitrix24.ru', $refreshed->domain);
        $this->assertSame($token->id, $refreshed->id);
    }

    public function test_it_deactivates_token_when_refresh_fails(): void
    {
        $token = $this->manager->storeToken([
            'domain' => 'portal.bitrix24.ru',
            'access_token' => 'old-access',
            'refresh_token' => 'old-refresh',
            'expires' => Carbon::now()->subHour()->getTimestamp(),
        ], 4);

        Http::fake([
            'https://oauth.bitrix.info/oauth/token/' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $this->assertNull($this->manager->getToken(4));
        $this->assertFalse($token->fresh()->is_active);
    }

    public function test_it_keeps_token_active_when_refresh_hits_server_error(): void
    {
        $token = $this->manager->storeToken([
            'domain' => 'portal.bitrix24.ru',
            'access_token' => 'old-access',
            'refresh_token' => 'old-refresh',
            'expires' => Carbon::now()->subHour()->getTimestamp(),
        ], 6);

        config()->set('bitrix24.api.retry_attempts', 1);
        config()->set('bitrix24.api.retry_delay', 0);

        Http::fake([
            'https://oauth.bitrix.info/oauth/token/' => Http::response(['error' => 'temporarily_unavailable'], 503),
        ]);

        $this->assertNull($this->manager->getToken(6));
        $this->assertTrue($token->fresh()->is_active);
        $this->assertSame('old-refresh', $token->fresh()->refresh_token);
    }

    public function test_it_returns_still_valid_token_when_proactive_refresh_fails_transiently(): void
    {
        $token = $this->manager->storeToken([
            'domain' => 'portal.bitrix24.ru',
            'access_token' => 'old-access',
            'refresh_token' => 'old-refresh',
            'expires' => Carbon::now()->addMinutes(2)->getTimestamp(),
        ], 8);

        config()->set('bitrix24.api.retry_attempts', 1);
        config()->set('bitrix24.api.retry_delay', 0);

        Http::fake([
            'https://oauth.bitrix.info/oauth/token/' => Http::response(['error' => 'temporarily_unavailable'], 503),
        ]);

        $returned = $this->manager->getToken(8);

        $this->assertNotNull($returned);
        $this->assertSame($token->id, $returned->id);
        $this->assertSame('old-access', $returned->access_token);
        $this->assertTrue($returned->fresh()->is_active);
    }

    public function test_it_does_not_deactivate_when_concurrent_refresh_already_rotated_token(): void
    {
        $token = $this->manager->storeToken([
            'domain' => 'portal.bitrix24.ru',
            'access_token' => 'old-access',
            'refresh_token' => 'old-refresh',
            'expires' => Carbon::now()->subHour()->getTimestamp(),
        ], 11);

        $stale = Bitrix24Token::query()->findOrFail($token->id);

        Bitrix24Token::query()->whereKey($token->id)->update([
            'access_token' => 'rotated-access',
            'refresh_token' => 'rotated-refresh',
            'expires_at' => Carbon::now()->addHour(),
            'is_active' => true,
        ]);

        Http::fake([
            'https://oauth.bitrix.info/oauth/token/' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        try {
            $this->manager->refreshToken($stale);
            $this->fail('Expected OAuthException was not thrown');
        } catch (OAuthException $exception) {
            $this->assertTrue($exception->permanent);
        }

        $fresh = $token->fresh();
        $this->assertTrue($fresh->is_active);
        $this->assertSame('rotated-access', $fresh->access_token);
        $this->assertSame('rotated-refresh', $fresh->refresh_token);
    }

    public function test_it_returns_token_rotated_by_a_concurrent_refresh(): void
    {
        $token = $this->manager->storeToken([
            'domain' => 'portal.bitrix24.ru',
            'access_token' => 'old-access',
            'refresh_token' => 'old-refresh',
            'expires' => Carbon::now()->subHour()->getTimestamp(),
        ], 12);

        Http::fake(function () use ($token) {
            Bitrix24Token::query()->whereKey($token->id)->update([
                'access_token' => 'rotated-access',
                'refresh_token' => 'rotated-refresh',
                'expires_at' => Carbon::now()->addHour(),
                'is_active' => true,
            ]);

            return Http::response(['error' => 'invalid_grant'], 400);
        });

        $returned = $this->manager->getToken(12);

        $this->assertNotNull($returned);
        $this->assertSame($token->id, $returned->id);
        $this->assertSame('rotated-access', $returned->access_token);
        $this->assertTrue($returned->fresh()->is_active);
    }

    public function test_it_retries_oauth_on_server_errors(): void
    {
        Http::fake([
            'https://oauth.bitrix.info/oauth/token/' => Http::sequence()
                ->push(['error' => 'temporarily_unavailable'], 503)
                ->push([
                    'access_token' => 'access',
                    'refresh_token' => 'refresh',
                    'expires_in' => 3600,
                    'domain' => 'portal.bitrix24.ru',
                    'scope' => 'crm',
                ]),
        ]);

        config()->set('bitrix24.api.retry_attempts', 2);
        config()->set('bitrix24.api.retry_delay', 0);

        $payload = $this->manager->exchangeAuthorizationCode('auth-code');

        $this->assertSame('access', $payload['access_token']);
        Http::assertSentCount(2);
    }

    public function test_it_throws_when_oauth_response_has_no_tokens(): void
    {
        Http::fake([
            'https://oauth.bitrix.info/oauth/token/' => Http::response(['ok' => true]),
        ]);

        $this->expectException(OAuthException::class);

        $this->manager->exchangeAuthorizationCode('code');
    }

    public function test_it_revokes_a_token(): void
    {
        $token = $this->manager->storeToken([
            'domain' => 'portal.bitrix24.ru',
            'access_token' => 'access',
            'refresh_token' => 'refresh',
            'expires_in' => 3600,
        ], 9);

        $this->assertTrue($this->manager->revokeToken($token->id));
        $this->assertFalse(Bitrix24Token::query()->find($token->id)->is_active);
        $this->assertFalse($this->manager->revokeToken(999));
    }
}
