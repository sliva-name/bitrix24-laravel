<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Tests;

use Bitrix24\SDK\Core\Response\DTO\ResponseData;
use Bitrix24\SDK\Core\Response\Response;
use Illuminate\Support\Facades\Event;
use Leko\Bitrix24\Clients\CrmClient;
use Leko\Bitrix24\Clients\ListClient;
use Leko\Bitrix24\Events\ApiCallEvent;
use Mockery;

class CrmClientTest extends TestCase
{
    public function test_add_returns_id_when_sdk_wraps_scalar_result(): void
    {
        Event::fake([ApiCallEvent::class]);

        $crm = new CrmClient($this->builderReturning([42]));

        $this->assertSame(42, $crm->add('lead', ['TITLE' => 'New lead']));
    }

    public function test_update_and_delete_succeed_when_sdk_wraps_true(): void
    {
        Event::fake([ApiCallEvent::class]);

        $crm = new CrmClient($this->builderReturning([true]));

        $this->assertTrue($crm->update('lead', 42, ['TITLE' => 'Updated']));
        $this->assertTrue($crm->delete('lead', 42));
    }

    public function test_list_add_returns_id_when_sdk_wraps_scalar_result(): void
    {
        Event::fake([ApiCallEvent::class]);

        $lists = new ListClient($this->builderReturning([15]));

        $this->assertSame(15, $lists->add(3, ['NAME' => 'Item']));
    }

    /**
     * @param array<mixed> $result
     */
    private function builderReturning(array $result): object
    {
        $client = TestClient::fake();
        $responseData = Mockery::mock(ResponseData::class);
        $responseData->shouldReceive('getResult')->andReturn($result);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('getResponseData')->andReturn($responseData);

        $client->getServiceBuilder()->core
            ->shouldReceive('call')
            ->andReturn($response);

        return $client->getServiceBuilder();
    }
}
