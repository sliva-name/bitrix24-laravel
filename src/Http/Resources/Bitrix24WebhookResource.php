<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JSON-представление входящего вебхука Bitrix24.
 */
class Bitrix24WebhookResource extends JsonResource
{
    /**
     * @return array<string, mixed>
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
            'payload' => $this->payload,
        ];
    }
}
