<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Http\Controllers;

use App\Http\Requests\Bitrix24\WebhookRequest;
use Leko\Bitrix24\Http\Resources\Bitrix24WebhookResource;
use Leko\Bitrix24\Http\Traits\ApiResponse;
use Leko\Bitrix24\Repositories\Bitrix24Webhook\Bitrix24WebhookRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Контроллер вебхуков Bitrix24
 *
 * Обрабатывает входящие вебхуки от Bitrix24.
 */
class Bitrix24WebhookController extends Controller
{
    use ApiResponse;

    /**
     * Создать новый экземпляр контроллера.
     *
     * @param Bitrix24WebhookRepositoryInterface $webhookRepository Репозиторий вебхуков
     */
    public function __construct(
        private readonly Bitrix24WebhookRepositoryInterface $webhookRepository
    ) {
    }

    /**
     * Обработать входящий вебхук.
     *
     * @param WebhookRequest $request Валидированный запрос
     * @return JsonResponse
     */
    public function handle(WebhookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $webhook = $this->webhookRepository->create([
                'event' => $validated['event'],
                'handler' => $this->getHandlerForEvent($validated['event']),
                'domain' => $request->get('auth.domain', 'unknown'),
                'payload' => $validated,
                'status' => 'pending',
            ]);

            Log::info('Получен вебхук Bitrix24', [
                'event' => $validated['event'],
                'webhook_id' => $webhook->id,
            ]);

            return $this->success(['webhook_id' => $webhook->id], 'Вебхук получен');
        } catch (Throwable $e) {
            Log::error('Не удалось обработать вебхук Bitrix24', [
                'event' => $validated['event'],
                'error' => $e->getMessage(),
            ]);

            return $this->error('Не удалось обработать вебхук', null, 500);
        }
    }

    /**
     * Получить ожидающие вебхуки.
     *
     * @return JsonResponse
     */
    public function pending(): JsonResponse
    {
        $webhooks = $this->webhookRepository->getPending();

        return $this->success([
            'data' => Bitrix24WebhookResource::collection($webhooks),
            'total' => $webhooks->count(),
        ]);
    }

    /**
     * Получить неудачные вебхуки.
     *
     * @return JsonResponse
     */
    public function failed(): JsonResponse
    {
        $webhooks = $this->webhookRepository->getFailed();

        return $this->success([
            'data' => Bitrix24WebhookResource::collection($webhooks),
            'total' => $webhooks->count(),
        ]);
    }

    /**
     * Получить класс обработчика для типа события.
     *
     * @param string $event Тип события
     * @return string
     */
    private function getHandlerForEvent(string $event): string
    {
        return match (true) {
            str_starts_with($event, 'ONCRMLEAD') => 'App\Services\Bitrix24\Handlers\LeadWebhookHandler',
            str_starts_with($event, 'ONCRMCONTACT') => 'App\Services\Bitrix24\Handlers\ContactWebhookHandler',
            str_starts_with($event, 'ONCRMCOMPANY') => 'App\Services\Bitrix24\Handlers\CompanyWebhookHandler',
            str_starts_with($event, 'ONCRMDEAL') => 'App\Services\Bitrix24\Handlers\DealWebhookHandler',
            str_starts_with($event, 'ONTASK') => 'App\Services\Bitrix24\Handlers\TaskWebhookHandler',
            default => 'App\Services\Bitrix24\Handlers\DefaultWebhookHandler',
        };
    }
}
