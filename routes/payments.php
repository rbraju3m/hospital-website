<?php

use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('payments/ipn', [PaymentWebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('payments.ipn');
