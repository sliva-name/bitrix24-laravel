<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Leko\Bitrix24\Facades\Bitrix24;
use Symfony\Component\HttpFoundation\Response;

/**
 * Проверяет наличие валидного токена Bitrix24 у текущего пользователя.
 */
class EnsureBitrix24Token
{
    public function handle(Request $request, Closure $next, string $connection = 'main'): Response
    {
        $userId = $request->user()?->id;

        if ($userId === null) {
            return response()->json([
                'error' => 'Не авторизован',
                'message' => 'Требуется аутентификация пользователя',
            ], 401);
        }

        $bitrix = Bitrix24::setConnection($connection)->setUserId((int) $userId);

        if (!$bitrix->hasValidToken((int) $userId)) {
            return response()->json([
                'error' => 'Требуется интеграция с Bitrix24',
                'message' => 'Пожалуйста, сначала аутентифицируйтесь в Bitrix24',
                'connection' => $connection,
            ], 403);
        }

        return $next($request);
    }
}
