<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Leko\Bitrix24\Http\Controllers\IncomingWebhookController;

Route::post(config('bitrix24.webhook.path', 'bitrix24/webhook'), IncomingWebhookController::class)
    ->name('bitrix24.webhook');
