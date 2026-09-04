<?php

use App\Http\Controllers\Api\ChatApiController;
use App\Http\Controllers\Api\SocialMediaPostApiController;
use App\Http\Controllers\FeishuCallbackController;
use App\Http\Controllers\ShippingQuoteController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\WeComAppCallbackController;
use App\Http\Controllers\WeComKfCallbackController;
use App\Http\Middleware\AuthenticateChatApi;
use Illuminate\Support\Facades\Route;

Route::post('shipping/quote', [ShippingQuoteController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('shipping.quote');

// Telegram calls this endpoint directly. Authentication is performed with
// Telegram's X-Telegram-Bot-Api-Secret-Token header in the controller.
Route::post('telegram/webhook', TelegramWebhookController::class)
    ->name('telegram.webhook');

// Feishu authenticates this callback in the controller because the request
// carries the provider-specific signature and optional encrypted payload.
Route::post('feishu/callback', FeishuCallbackController::class)
    ->name('feishu.callback');

// WeCom verifies and authenticates this callback in the controller because
// the request carries the provider-specific signature and encrypted payload.
Route::match(['get', 'post'], 'wecom/kf/callback', WeComKfCallbackController::class)
    ->name('wecom.kf.callback');

Route::match(['get', 'post'], 'wecom/app/callback', WeComAppCallbackController::class)
    ->name('wecom.app.callback');

// REST API for managing scheduled social media posts.
Route::apiResource('v1/social-media-posts', SocialMediaPostApiController::class);

// Stateless API for a separate admin/support client.
Route::prefix('v1/support/chat')
    ->middleware([AuthenticateChatApi::class, 'throttle:60,1'])
    ->group(function () {
        Route::get('conversations', [ChatApiController::class, 'conversations'])
            ->name('support.chat.conversations');
        Route::get('conversations/{conversation}/messages', [ChatApiController::class, 'messages'])
            ->name('support.chat.messages');
        Route::post('conversations/{conversation}/reply', [ChatApiController::class, 'reply'])
            ->name('support.chat.reply');
        Route::post('conversations/{conversation}/telegram-link', [ChatApiController::class, 'telegramLink'])
            ->name('support.chat.telegram-link');
        Route::post('conversations/{conversation}/takeover', [ChatApiController::class, 'takeover'])
            ->name('support.chat.takeover');
        Route::post('conversations/{conversation}/resolve', [ChatApiController::class, 'resolve'])
            ->name('support.chat.resolve');
    });
