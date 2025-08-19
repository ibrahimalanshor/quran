<?php

use App\Http\Controllers\Api\V1\WhatsappWebhookController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/v1/webhooks'], function () {
    Route::get('/wa', [WhatsappWebhookController::class, 'verify']);
    Route::post('/wa', [WhatsappWebhookController::class, 'handle']);
});