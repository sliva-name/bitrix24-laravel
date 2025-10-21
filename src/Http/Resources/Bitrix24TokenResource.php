<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс токена Bitrix24
 *
 * Преобразует модель Bitrix24Token в JSON ответ.
 */
class Bitrix24TokenResource extends JsonResource
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
            'connection' => $this->connection,
            'domain' => $this->domain,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_expired' => $this->isExpired(),
            'is_expiring_soon' => $this->isExpiringSoon(),
            'is_active' => $this->is_active,
            'scope' => $this->scope,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
