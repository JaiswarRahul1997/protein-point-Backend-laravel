<?php

use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => 'Protein Point',
    ]);
});

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{idOrSlug}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/banners', [BannerController::class, 'index']);
Route::get('/menus', [MenuController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);

Route::post('/auth/register', [\App\Http\Controllers\Api\CustomerAuthController::class, 'register']);
Route::post('/auth/login', [\App\Http\Controllers\Api\CustomerAuthController::class, 'login']);
Route::post('/auth/logout', [\App\Http\Controllers\Api\CustomerAuthController::class, 'logout']);
Route::get('/auth/me', [\App\Http\Controllers\Api\CustomerAuthController::class, 'me']);
Route::put('/auth/profile', [\App\Http\Controllers\Api\CustomerAuthController::class, 'updateProfile']);
Route::put('/auth/password', [\App\Http\Controllers\Api\CustomerAuthController::class, 'updatePassword']);
Route::get('/auth/orders/{order}', [\App\Http\Controllers\Api\CustomerAuthController::class, 'order']);
Route::post('/auth/impersonate', [\App\Http\Controllers\Api\CustomerAuthController::class, 'impersonate']);

Route::post('/newsletter/subscribe', [\App\Http\Controllers\Api\NewsletterController::class, 'subscribe']);
Route::post('/newsletter/confirm/email', [\App\Http\Controllers\Api\NewsletterController::class, 'confirmEmail']);
Route::post('/newsletter/unsubscribe/email', [\App\Http\Controllers\Api\NewsletterController::class, 'unsubscribeEmail']);
Route::post('/newsletter/unsubscribe/whatsapp', [\App\Http\Controllers\Api\NewsletterController::class, 'unsubscribeWhatsApp']);

Route::get('/webhooks/whatsapp', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'verify']);
Route::post('/webhooks/whatsapp', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'handle']);
