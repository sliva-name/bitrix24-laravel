<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс вебхука Bitrix24
 *
 * Преобразует модель Bitrix24Webhook в JSON ответ.
 */
class Bitrix24WebhookResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив.
     *
     * @param Request $request HTTP запрос
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'handler' => $this->handler,
            'domain' => $this->domain,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'error_message' => $this->error_message,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'payload' => $this->when($request->boolean('include_payload'), $this->payload),
        ];
    }
}
