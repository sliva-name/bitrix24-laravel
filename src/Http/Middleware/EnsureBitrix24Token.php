<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Http\Middleware;

use App\Facades\Bitrix24;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware проверки токена Bitrix24
 *
 * Проверяет наличие валидного токена Bitrix24 у пользователя перед продолжением.
 */
class EnsureBitrix24Token
{
    /**
     * Обработать входящий запрос.
     *
     * @param Request $request HTTP запрос
     * @param Closure $next Следующий обработчик
     * @param string $connection Название подключения
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $connection = 'main'): Response
    {
        $userId = $request->user()?->id;

        if (!$userId) {
            return response()->json([
                'error' => 'Не авторизован',
                'message' => 'Требуется аутентификация пользователя',
            ], 401);
        }

        $hasValidToken = Bitrix24::setConnection($connection)
            ->hasValidToken($userId);

        if (!$hasValidToken) {
            return response()->json([
                'error' => 'Требуется интеграция с Bitrix24',
                'message' => 'Пожалуйста, сначала аутентифицируйтесь в Bitrix24',
                'connection' => $connection,
            ], 403);
        }

        Bitrix24::setUserId($userId)->setConnection($connection);

        return $next($request);
    }
}
