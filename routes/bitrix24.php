<?php

declare(strict_types=1);

use Leko\Bitrix24\Facades\Bitrix24;
use Leko\Bitrix24\Http\Controllers\Bitrix24AuthController;
use Leko\Bitrix24\Http\Controllers\Bitrix24WebhookController;
use Leko\Bitrix24\Http\Middleware\EnsureBitrix24Token;
use Illuminate\Support\Facades\Route;

Route::prefix('bitrix24')->group(function () {
    Route::prefix('auth')->name('bitrix24.auth.')->group(function () {
        Route::get('/authorize', [Bitrix24AuthController::class, 'authorize'])
            ->name('authorize');

        Route::post('/callback', [Bitrix24AuthController::class, 'callback'])
            ->name('callback');

        Route::get('/status', [Bitrix24AuthController::class, 'status'])
            ->middleware('auth:sanctum')
            ->name('status');

        Route::post('/revoke', [Bitrix24AuthController::class, 'revoke'])
            ->middleware('auth:sanctum')
            ->name('revoke');
    });

    Route::prefix('webhook')->name('bitrix24.webhook.')->group(function () {
        Route::post('/handle', [Bitrix24WebhookController::class, 'handle'])
            ->name('handle');

        Route::get('/pending', [Bitrix24WebhookController::class, 'pending'])
            ->middleware('auth:sanctum')
            ->name('pending');

        Route::get('/failed', [Bitrix24WebhookController::class, 'failed'])
            ->middleware('auth:sanctum')
            ->name('failed');
    });
});

Route::prefix('bitrix24')->middleware(['auth:sanctum', EnsureBitrix24Token::class])->group(function () {
    Route::get('/leads', function () {
        $leads = Bitrix24::leads()->list();
        return response()->json($leads);
    });

    Route::get('/contacts', function () {
        $contacts = Bitrix24::contacts()->list();
        return response()->json($contacts);
    });

    Route::get('/companies', function () {
        $companies = Bitrix24::companies()->list();
        return response()->json($companies);
    });

    Route::get('/deals', function () {
        $deals = Bitrix24::deals()->list();
        return response()->json($deals);
    });

    Route::get('/tasks', function () {
        $tasks = Bitrix24::tasks()->list();
        return response()->json($tasks);
    });

    Route::get('/users', function () {
        $users = Bitrix24::users()->list();
        return response()->json($users);
    });
});
