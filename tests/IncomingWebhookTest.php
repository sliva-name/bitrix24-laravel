<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Tests;

use Leko\Bitrix24\Models\Bitrix24Webhook;

class IncomingWebhookTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('bitrix24.webhook.enabled', true);
        $app['config']->set('bitrix24.webhook.secret', 'app-token');
        $app['config']->set('bitrix24.webhook.path', 'bitrix24/webhook');
    }

    public function test_it_rejects_invalid_secret(): void
    {
        $this->postJson('/bitrix24/webhook', [
            'event' => 'ONCRMLEADADD',
            'auth' => ['application_token' => 'wrong'],
        ])->assertUnauthorized();

        $this->assertSame(0, Bitrix24Webhook::query()->count());
    }

    public function test_it_stores_a_valid_webhook(): void
    {
        $response = $this->postJson('/bitrix24/webhook', [
            'event' => 'ONCRMLEADADD',
            'handler' => 'lead',
            'auth' => [
                'application_token' => 'app-token',
                'domain' => 'https://portal.bitrix24.ru/',
            ],
            'data' => ['FIELDS' => ['ID' => 15]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.event', 'ONCRMLEADADD')
            ->assertJsonPath('data.domain', 'portal.bitrix24.ru')
            ->assertJsonPath('data.status', Bitrix24Webhook::STATUS_PENDING);

        $this->assertSame(1, Bitrix24Webhook::query()->count());
    }
}
