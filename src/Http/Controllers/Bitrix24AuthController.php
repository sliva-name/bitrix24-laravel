<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Http\Controllers;

use App\Facades\Bitrix24;
use App\Http\Requests\Bitrix24\AuthCallbackRequest;
use Leko\Bitrix24\Http\Traits\ApiResponse;
use Leko\Bitrix24\TokenManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Контроллер аутентификации Bitrix24
 *
 * Обрабатывает OAuth flow аутентификации с Bitrix24.
 */
class Bitrix24AuthController extends Controller
{
    use ApiResponse;

    /**
     * Получить URL авторизации для OAuth flow.
     *
     * @param Request $request HTTP запрос
     * @return JsonResponse
     */
    public function authorize(Request $request): JsonResponse
    {
        $scopes = $request->get('scopes', []);
        $state = $request->get('state');
        $connection = $request->get('connection', 'main');

        $authUrl = Bitrix24::setConnection($connection)
            ->getAuthorizationUrl($scopes, $state);

        return $this->success([
            'authorization_url' => $authUrl,
            'state' => $state,
        ]);
    }

    /**
     * Обработать OAuth callback от Bitrix24.
     *
     * @param AuthCallbackRequest $request Валидированный запрос
     * @return JsonResponse
     */
    public function callback(AuthCallbackRequest $request): JsonResponse
    {
        $code = $request->validated('code');
        $connection = $request->get('connection', 'main');
        $userId = $request->user()?->id;

        try {
            $result = Bitrix24::setConnection($connection)
                ->setUserId($userId)
                ->handleCallback($code);

            return $this->success($result, 'Успешная аутентификация с Bitrix24');
        } catch (Throwable $e) {
            return $this->error('Не удалось аутентифицироваться с Bitrix24', $e->getMessage());
        }
    }

    /**
     * Получить текущий статус токена.
     *
     * @param Request $request HTTP запрос
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        $connection = $request->get('connection', 'main');

        $hasToken = Bitrix24::setConnection($connection)
            ->hasValidToken($userId);

        return $this->success([
            'has_valid_token' => $hasToken,
            'connection' => $connection,
        ]);
    }

    /**
     * Отозвать текущий токен.
     *
     * @param Request $request HTTP запрос
     * @return JsonResponse
     */
    public function revoke(Request $request): JsonResponse
    {
        $tokenId = $request->get('token_id');

        if (!$tokenId) {
            return $this->error('Требуется ID токена');
        }

        try {
            $tokenManager = app(TokenManager::class);
            $result = $tokenManager->revokeToken((int) $tokenId);

            return $result
                ? $this->success(null, 'Токен успешно отозван')
                : $this->error('Не удалось отозвать токен');
        } catch (Throwable $e) {
            return $this->error('Не удалось отозвать токен', $e->getMessage());
        }
    }
}
