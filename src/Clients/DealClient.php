<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Bitrix24\SDK\Services\CRM\Deal\Result\DealItemResult;
use Leko\Bitrix24\Contracts\DealClientInterface;

/**
 * Клиент сделок Bitrix24.
 */
class DealClient extends CrmEntityClient implements DealClientInterface
{
    protected function entity(): string
    {
        return 'deal';
    }

    public function get(int $id): DealItemResult
    {
        return parent::get($id);
    }
}
