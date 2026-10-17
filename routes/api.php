<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Event\EventController;
use App\Http\Controllers\SubscriptionPlan\SubscriptionPlanController;
use App\Http\Controllers\Subscription\SubscriptionController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Invoice\InvoiceController;
use App\Http\Controllers\AccessLink\AccessLinkController;
use App\Http\Controllers\Media\MediaController;
use App\Http\Controllers\Internal\PhotoProcessingCallbackController;
use App\Http\Controllers\PublicGallery\PublicGalleryController;
use App\Http\Controllers\Media\MediaUploadController;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login/google', [AuthController::class, 'loginByGoogle']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:3,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::get('/public/access-links/{token}', [AccessLinkController::class, 'resolve'])
    ->where('token', '[A-Fa-f0-9]{64}')
    ->middleware('throttle:30,1');

Route::get('/public/access-links/{token}/media', [PublicGalleryController::class, 'index'])
    ->where('token', '[A-Fa-f0-9]{64}')
    ->middleware('throttle:60,1');
Route::post('/public/access-links/{token}/downloads', [PublicGalleryController::class, 'downloads'])
    ->where('token', '[A-Fa-f0-9]{64}')
    ->middleware('throttle:30,1');
Route::post('/public/access-links/{token}/deliveries', [PublicGalleryController::class, 'delivery'])
    ->where('token', '[A-Fa-f0-9]{64}')
    ->middleware('throttle:10,1');

Route::post('/public/access-links/{token}/guest-scans', [\App\Http\Controllers\GuestScan\GuestScanController::class, 'store'])
    ->where('token', '[A-Fa-f0-9]{64}')
    ->middleware('throttle:5,1');

Route::post('/internal/photo-processing/callback', PhotoProcessingCallbackController::class)
    ->middleware('throttle:120,1');

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/users/me', [\App\Http\Controllers\User\UserController::class, 'updateProfile']);
    Route::get('/users/search', [\App\Http\Controllers\User\UserController::class, 'index']);
    Route::get('/users', [\App\Http\Controllers\User\UserController::class, 'index']);
    Route::get('/users/{user}', [\App\Http\Controllers\User\UserController::class, 'show'])->whereNumber('user');
    Route::delete('/users/{user}', [\App\Http\Controllers\User\UserController::class, 'destroy'])->whereNumber('user');
    Route::get('/events/search', [EventController::class, 'search']);
    Route::get('/events/options', [EventController::class, 'options']);
    Route::get('/events/status/{status}', [EventController::class, 'byStatus']);
    Route::apiResource('events', EventController::class);
    Route::apiResource('subscription-plans', SubscriptionPlanController::class);
    Route::get('/subscriptions/current', [SubscriptionController::class, 'current']);
    Route::apiResource('subscriptions', SubscriptionController::class);
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('invoices', InvoiceController::class)->only(['index', 'show']);
    Route::get('/events/{event}/access-links', [AccessLinkController::class, 'index']);
    Route::post('/events/{event}/access-links', [AccessLinkController::class, 'store']);
    Route::get('/access-links/{accessLink}', [AccessLinkController::class, 'show']);
    Route::patch('/access-links/{accessLink}', [AccessLinkController::class, 'update']);
    Route::delete('/access-links/{accessLink}', [AccessLinkController::class, 'destroy']);
    Route::post('/events/{event}/media', MediaUploadController::class);
    Route::get('/events/{event}/media', [MediaController::class, 'index']);

    
});
