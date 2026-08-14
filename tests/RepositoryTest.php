<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Tests;

use Leko\Bitrix24\Models\Bitrix24Token;
use Leko\Bitrix24\Models\Bitrix24Webhook;
use Leko\Bitrix24\Repositories\Bitrix24Token\Bitrix24TokenRepository;
use Leko\Bitrix24\Repositories\Bitrix24Webhook\Bitrix24WebhookRepository;

class RepositoryTest extends TestCase
{
    public function test_token_repository_upserts_and_deactivates(): void
    {
        $repository = new Bitrix24TokenRepository();

        $token = $repository->upsert([
            'connection' => 'main',
            'user_id' => 1,
            'domain' => 'portal.bitrix24.ru',
            'access_token' => 'a',
            'refresh_token' => 'r',
            'expires_in' => 3600,
            'expires_at' => now()->addHour(),
        ]);

        $this->assertSame($token->id, $repository->findActiveToken(1)?->id);
        $this->assertTrue($repository->deactivate($token->id));
        $this->assertNull($repository->findValidToken(1));
        $this->assertTrue($repository->activate($token->id));
        $this->assertNotNull($repository->findValidToken(1));
        $this->assertTrue($repository->delete($token->id));
        $this->assertNull(Bitrix24Token::query()->find($token->id));
    }

    public function test_webhook_repository_filters_by_status_and_event(): void
    {
        $repository = new Bitrix24WebhookRepository();

        $pending = $repository->create([
            'event' => 'ONCRMLEADADD',
            'handler' => 'lead',
            'domain' => 'portal.bitrix24.ru',
            'payload' => ['ID' => 1],
            'status' => Bitrix24Webhook::STATUS_PENDING,
        ]);

        $failed = $repository->create([
            'event' => 'ONCRMDEALADD',
            'handler' => 'deal',
            'domain' => 'portal.bitrix24.ru',
            'payload' => ['ID' => 2],
            'status' => Bitrix24Webhook::STATUS_FAILED,
        ]);

        $pending->markAsProcessing();
        $pending->markAsCompleted();
        $failed->markAsFailed('timeout');

        $this->assertSame(Bitrix24Webhook::STATUS_COMPLETED, $pending->fresh()->status);
        $this->assertSame(0, $repository->getPending()->count());
        $this->assertSame(1, $repository->getFailed()->count());
        $this->assertSame(1, $repository->getByEvent('ONCRMLEADADD')->count());
        $this->assertTrue($repository->delete($failed->id));
    }
}
