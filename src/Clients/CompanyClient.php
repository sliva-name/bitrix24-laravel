<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Bitrix24\SDK\Services\CRM\Company\Result\CompanyItemResult;
use Leko\Bitrix24\Contracts\CompanyClientInterface;

/**
 * Клиент компаний Bitrix24.
 */
class CompanyClient extends CrmEntityClient implements CompanyClientInterface
{
    protected function entity(): string
    {
        return 'company';
    }

    public function get(int $id): CompanyItemResult
    {
        return parent::get($id);
    }
}
