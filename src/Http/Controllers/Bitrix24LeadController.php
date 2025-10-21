<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Http\Controllers;

use App\Facades\Bitrix24;
use App\Http\Controllers\Controller;
use Leko\Bitrix24\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Пример контроллера Bitrix24 лидов
 *
 * Демонстрирует работу с лидами Bitrix24.
 */
class Bitrix24LeadController extends Controller
{
    use ApiResponse;

    /**
     * Получить список лидов.
     *
     * @param Request $request HTTP запрос
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filter = [];
            
            if ($request->has('status')) {
                $filter['STATUS_ID'] = $request->get('status');
            }

            if ($request->has('min_amount')) {
                $filter['>OPPORTUNITY'] = $request->get('min_amount');
            }

            $leads = Bitrix24::setUserId($request->user()->id)
                ->leads()
                ->list(
                    filter: $filter,
                    select: ['ID', 'TITLE', 'NAME', 'LAST_NAME', 'STATUS_ID', 'OPPORTUNITY', 'DATE_CREATE'],
                    order: ['DATE_CREATE' => 'DESC']
                );

            return $this->success($leads);
        } catch (Throwable $e) {
            return $this->error('Не удалось получить список лидов', $e->getMessage(), 500);
        }
    }

    /**
     * Получить лид по ID.
     *
     * @param Request $request HTTP запрос
     * @param int $id Идентификатор лида
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $lead = Bitrix24::setUserId($request->user()->id)
                ->leads()
                ->get($id);

            if (!$lead) {
                return $this->notFound('Лид не найден');
            }

            return $this->success($lead);
        } catch (Throwable $e) {
            return $this->error('Не удалось получить лид', $e->getMessage(), 500);
        }
    }

    /**
     * Создать новый лид.
     *
     * @param Request $request HTTP запрос
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'name' => 'nullable|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'source_id' => 'nullable|string',
            'status_id' => 'nullable|string',
            'opportunity' => 'nullable|numeric|min:0',
            'currency_id' => 'nullable|string|max:3',
            'comments' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Ошибка валидации', $validator->errors());
        }

        try {
            $fields = [
                'TITLE' => $request->get('title'),
                'NAME' => $request->get('name'),
                'LAST_NAME' => $request->get('last_name'),
                'PHONE' => $request->get('phone') ? [['VALUE' => $request->get('phone'), 'VALUE_TYPE' => 'WORK']] : null,
                'EMAIL' => $request->get('email') ? [['VALUE' => $request->get('email'), 'VALUE_TYPE' => 'WORK']] : null,
                'SOURCE_ID' => $request->get('source_id'),
                'STATUS_ID' => $request->get('status_id', 'NEW'),
                'OPPORTUNITY' => $request->get('opportunity', 0),
                'CURRENCY_ID' => $request->get('currency_id', 'RUB'),
                'COMMENTS' => $request->get('comments'),
            ];

            $fields = array_filter($fields, fn($value) => $value !== null);

            $leadId = Bitrix24::setUserId($request->user()->id)
                ->leads()
                ->add($fields);

            return $this->created(['lead_id' => $leadId], 'Лид успешно создан');
        } catch (Throwable $e) {
            return $this->error('Не удалось создать лид', $e->getMessage(), 500);
        }
    }

    /**
     * Обновить лид.
     *
     * @param Request $request HTTP запрос
     * @param int $id Идентификатор лида
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:50',
            'last_name' => 'sometimes|string|max:50',
            'phone' => 'sometimes|string|max:50',
            'email' => 'sometimes|email|max:255',
            'source_id' => 'sometimes|string',
            'status_id' => 'sometimes|string',
            'opportunity' => 'sometimes|numeric|min:0',
            'currency_id' => 'sometimes|string|max:3',
            'comments' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Ошибка валидации', $validator->errors());
        }

        try {
            $fields = [];

            if ($request->has('title')) {
                $fields['TITLE'] = $request->get('title');
            }
            if ($request->has('name')) {
                $fields['NAME'] = $request->get('name');
            }
            if ($request->has('last_name')) {
                $fields['LAST_NAME'] = $request->get('last_name');
            }
            if ($request->has('phone')) {
                $fields['PHONE'] = [['VALUE' => $request->get('phone'), 'VALUE_TYPE' => 'WORK']];
            }
            if ($request->has('email')) {
                $fields['EMAIL'] = [['VALUE' => $request->get('email'), 'VALUE_TYPE' => 'WORK']];
            }
            if ($request->has('source_id')) {
                $fields['SOURCE_ID'] = $request->get('source_id');
            }
            if ($request->has('status_id')) {
                $fields['STATUS_ID'] = $request->get('status_id');
            }
            if ($request->has('opportunity')) {
                $fields['OPPORTUNITY'] = $request->get('opportunity');
            }
            if ($request->has('currency_id')) {
                $fields['CURRENCY_ID'] = $request->get('currency_id');
            }
            if ($request->has('comments')) {
                $fields['COMMENTS'] = $request->get('comments');
            }

            $result = Bitrix24::setUserId($request->user()->id)
                ->leads()
                ->update($id, $fields);

            return $result
                ? $this->success(null, 'Лид успешно обновлен')
                : $this->error('Не удалось обновить лид');
        } catch (Throwable $e) {
            return $this->error('Не удалось обновить лид', $e->getMessage(), 500);
        }
    }

    /**
     * Удалить лид.
     *
     * @param Request $request HTTP запрос
     * @param int $id Идентификатор лида
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $result = Bitrix24::setUserId($request->user()->id)
                ->leads()
                ->delete($id);

            return $result
                ? $this->success(null, 'Лид успешно удален')
                : $this->error('Не удалось удалить лид');
        } catch (Throwable $e) {
            return $this->error('Не удалось удалить лид', $e->getMessage(), 500);
        }
    }

    /**
     * Получить поля лида.
     *
     * @param Request $request HTTP запрос
     * @return JsonResponse
     */
    public function fields(Request $request): JsonResponse
    {
        try {
            $fields = Bitrix24::setUserId($request->user()->id)
                ->leads()
                ->fields();

            return $this->success($fields);
        } catch (Throwable $e) {
            return $this->error('Не удалось получить поля лида', $e->getMessage(), 500);
        }
    }
}
