<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Tests;

use Leko\Bitrix24\Clients\CompanyClient;
use Leko\Bitrix24\Clients\ContactClient;
use Leko\Bitrix24\Clients\DealClient;
use Leko\Bitrix24\Clients\LeadClient;
use Leko\Bitrix24\Support\BatchRequest;
use ReflectionMethod;

class ClientStructureTest extends TestCase
{
    public function test_crm_entity_clients_share_the_same_entity_contract(): void
    {
        foreach ([LeadClient::class, DealClient::class, ContactClient::class, CompanyClient::class] as $class) {
            $this->assertTrue(is_subclass_of($class, \Leko\Bitrix24\Clients\CrmEntityClient::class));
            $this->assertTrue(method_exists($class, 'list'));
            $this->assertTrue(method_exists($class, 'get'));
            $this->assertTrue(method_exists($class, 'add'));
            $this->assertTrue(method_exists($class, 'update'));
            $this->assertTrue(method_exists($class, 'delete'));
            $this->assertTrue(method_exists($class, 'fields'));
        }
    }

    public function test_lead_client_entity_name(): void
    {
        $method = new ReflectionMethod(LeadClient::class, 'entity');
        $lead = new LeadClient(TestClient::fake()->getServiceBuilder());

        $this->assertSame('lead', $method->invoke($lead));
    }

    public function test_batch_request_collects_and_clears_commands(): void
    {
        $batch = new BatchRequest(TestClient::fake());

        $this->assertTrue($batch->isEmpty());

        $batch->add('lead1', 'crm.lead.add', ['fields' => ['TITLE' => 'A']])
            ->addMany([
                'lead2' => ['method' => 'crm.lead.add', 'params' => ['fields' => ['TITLE' => 'B']]],
            ]);

        $this->assertSame(2, $batch->count());
        $this->assertSame([], $batch->clear()->execute());
        $this->assertTrue($batch->isEmpty());
    }
}
