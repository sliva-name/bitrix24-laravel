<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Bitrix24\SDK\Services\CRM\Lead\Result\LeadItemResult;
use Leko\Bitrix24\Contracts\LeadClientInterface;

/**
 * Клиент лидов Bitrix24.
 */
class LeadClient extends CrmEntityClient implements LeadClientInterface
{
    protected function entity(): string
    {
        return 'lead';
    }

    public function get(int $id): LeadItemResult
    {
        return parent::get($id);
    }
}
