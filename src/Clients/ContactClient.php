<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Clients;

use Bitrix24\SDK\Services\CRM\Contact\Result\ContactItemResult;
use Leko\Bitrix24\Contracts\ContactClientInterface;

/**
 * Клиент контактов Bitrix24.
 */
class ContactClient extends CrmEntityClient implements ContactClientInterface
{
    protected function entity(): string
    {
        return 'contact';
    }

    public function get(int $id): ContactItemResult
    {
        return parent::get($id);
    }
}
