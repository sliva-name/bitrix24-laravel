<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Exceptions;

use Throwable;

/**
 * Ошибка обмена или обновления OAuth-токена.
 */
class OAuthException extends Bitrix24Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly bool $permanent = false,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
