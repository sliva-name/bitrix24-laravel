<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Tests;

use Illuminate\Support\Facades\Event;
use Leko\Bitrix24\Events\ApiCallEvent;
use Leko\Bitrix24\Events\ApiCallFailedEvent;
use RuntimeException;

class BaseClientTest extends TestCase
{
    public function test_call_method_dispatches_success_event(): void
    {
        Event::fake([ApiCallEvent::class, ApiCallFailedEvent::class]);

        $client = TestClient::fake();
        $result = $client->invoke('crm.lead.list', ['start' => 0], fn () => ['ID' => 1]);

        $this->assertSame(['ID' => 1], $result);
        Event::assertDispatched(ApiCallEvent::class, function (ApiCallEvent $event): bool {
            return $event->method === 'crm.lead.list' && $event->params === ['start' => 0];
        });
    }

    public function test_call_crm_method_prefixes_entity(): void
    {
        Event::fake([ApiCallEvent::class]);

        $client = TestClient::fake();
        $client->invokeCrm('deal', 'get', ['id' => 5], fn () => ['ID' => 5]);

        Event::assertDispatched(ApiCallEvent::class, fn (ApiCallEvent $event): bool => $event->method === 'crm.deal.get');
    }

    public function test_call_method_dispatches_failure_event(): void
    {
        Event::fake([ApiCallEvent::class, ApiCallFailedEvent::class]);

        $client = TestClient::fake();

        try {
            $client->invoke('crm.lead.get', [], function (): void {
                throw new RuntimeException('boom');
            });
            $this->fail('Exception was not thrown');
        } catch (RuntimeException $exception) {
            $this->assertSame('boom', $exception->getMessage());
        }

        Event::assertDispatched(ApiCallFailedEvent::class, fn (ApiCallFailedEvent $event): bool => $event->method === 'crm.lead.get');
        Event::assertNotDispatched(ApiCallEvent::class);
    }

    public function test_result_helpers(): void
    {
        $client = TestClient::fake();

        $this->assertTrue($client->success(true));
        $this->assertTrue($client->success(1));
        $this->assertTrue($client->success('1'));
        $this->assertFalse($client->success(false));
        $this->assertSame(15, $client->integer('15'));
        $this->assertNull($client->integer('nope'));
        $this->assertSame(['a' => 1], $client->arrayValue(['a' => 1]));
        $this->assertSame([], $client->arrayValue('x'));

        // Official SDK wraps scalar REST results as a single-element list.
        $this->assertSame(42, $client->integer([42]));
        $this->assertSame(15, $client->integer(['15']));
        $this->assertTrue($client->success([true]));
        $this->assertTrue($client->success([1]));
        $this->assertTrue($client->success(['1']));
        $this->assertFalse($client->success([false]));
        $this->assertNull($client->integer([['ID' => 42]]));
        $this->assertNull($client->integer(['ID' => 42]));
        $this->assertFalse($client->success(['ID' => 1]));
    }

    public function test_build_params_skips_empty_and_conditional_values(): void
    {
        $client = TestClient::fake();

        $params = $client->params(
            ['IBLOCK_TYPE_ID' => 'lists'],
            [
                'filter' => ['STATUS' => 'NEW'],
                'select' => [],
                'start' => [
                    'value' => 0,
                    'condition' => static fn ($value): bool => $value > 0,
                ],
            ]
        );

        $this->assertSame([
            'IBLOCK_TYPE_ID' => 'lists',
            'filter' => ['STATUS' => 'NEW'],
        ], $params);
    }
}
