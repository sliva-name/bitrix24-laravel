<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Http\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait для стандартизации API ответов
 *
 * Предоставляет единообразные методы для формирования JSON ответов.
 */
trait ApiResponse
{
    /**
     * Успешный ответ с данными.
     *
     * @param array|object|string|int|float|bool|null $data Данные ответа
     * @param string $message Сообщение
     * @param int $code HTTP код ответа
     * @return JsonResponse
     */
    protected function success(array|object|string|int|float|bool|null $data = null, string $message = 'Успешно', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Ответ с ошибкой.
     *
     * @param string $message Сообщение об ошибке
     * @param array|object|string|null $errors Детали ошибки
     * @param int $code HTTP код ответа
     * @return JsonResponse
     */
    protected function error(string $message = 'Ошибка', array|object|string|null $errors = null, int $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['error'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Ответ с данными без обертки success.
     *
     * @param array|object|string|int|float|bool|null $data Данные ответа
     * @param int $code HTTP код ответа
     * @return JsonResponse
     */
    protected function data(array|object|string|int|float|bool|null $data, int $code = 200): JsonResponse
    {
        return response()->json($data, $code);
    }

    /**
     * Ответ "Не найдено".
     *
     * @param string $message Сообщение
     * @return JsonResponse
     */
    protected function notFound(string $message = 'Не найдено'): JsonResponse
    {
        return $this->error($message, null, 404);
    }

    /**
     * Ответ "Запрещено".
     *
     * @param string $message Сообщение
     * @return JsonResponse
     */
    protected function forbidden(string $message = 'Доступ запрещен'): JsonResponse
    {
        return $this->error($message, null, 403);
    }

    /**
     * Ответ "Неавторизован".
     *
     * @param string $message Сообщение
     * @return JsonResponse
     */
    protected function unauthorized(string $message = 'Не авторизован'): JsonResponse
    {
        return $this->error($message, null, 401);
    }

    /**
     * Ответ "Создано".
     *
     * @param array|object|string|int|float|bool|null $data Данные созданного ресурса
     * @param string $message Сообщение
     * @return JsonResponse
     */
    protected function created(array|object|string|int|float|bool|null $data = null, string $message = 'Создано'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Ответ "Без контента".
     *
     * @return JsonResponse
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}

