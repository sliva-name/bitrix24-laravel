<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Leko\Bitrix24\Http\Resources\Bitrix24WebhookResource;
use Leko\Bitrix24\Http\Traits\ApiResponse;
use Leko\Bitrix24\Models\Bitrix24Webhook;
use Leko\Bitrix24\Repositories\Bitrix24Webhook\Bitrix24WebhookRepositoryInterface;
use Leko\Bitrix24\Support\Domain;

/**
 * Приём входящих вебхуков Bitrix24.
 */
class IncomingWebhookController
{
    use ApiResponse;

    public function __invoke(
        Request $request,
        Bitrix24WebhookRepositoryInterface $webhooks
    ): JsonResponse {
        $secret = (string) config('bitrix24.webhook.secret', '');

        if ($secret !== '' && !hash_equals($secret, $this->requestToken($request))) {
            return $this->unauthorized('Неверный токен вебхука');
        }

        $webhook = $webhooks->create([
            'event' => (string) $request->input('event', 'unknown'),
            'handler' => (string) $request->input('handler', ''),
            'domain' => Domain::normalize((string) (
                $request->input('auth.domain')
                ?? $request->input('domain', '')
            )),
            'payload' => $request->all(),
            'status' => Bitrix24Webhook::STATUS_PENDING,
        ]);

        return $this->created(
            (new Bitrix24WebhookResource($webhook))->toArray($request),
            'Вебхук принят'
        );
    }

    private function requestToken(Request $request): string
    {
        return (string) (
            $request->input('auth.application_token')
            ?? $request->input('application_token')
            ?? $request->header('X-Bitrix24-Token', '')
        );
    }
}
