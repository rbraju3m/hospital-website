<?php

use App\Http\Controllers\Portal\AppointmentController;
use App\Http\Controllers\Portal\Auth\PasswordResetController;
use App\Http\Controllers\Portal\Auth\RegisterController;
use App\Http\Controllers\Portal\Auth\SessionController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\DocumentController;
use App\Http\Controllers\Portal\PaymentController;
use App\Http\Controllers\Portal\ProfileController;
use Illuminate\Support\Facades\Route;

/*
| The patient portal. Its own guard, its own table, its own sign-in — nothing
| here shares an authentication surface with /admin.
*/

Route::prefix('portal')->name('portal.')->group(function () {
    Route::middleware('guest:patient')->group(function () {
        Route::get('login', [SessionController::class, 'create'])->name('login');
        Route::post('login', [SessionController::class, 'store'])
            ->middleware('throttle:10,1')->name('login.store');

        Route::get('register', [RegisterController::class, 'create'])->name('register');
        Route::post('register', [RegisterController::class, 'store'])
            ->middleware('throttle:5,1')->name('register.store');

        Route::get('forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
        // Tighter than login: each attempt sends a real SMS, which costs money
        // and lands on somebody's phone whether they asked for it or not.
        Route::post('forgot-password', [PasswordResetController::class, 'send'])
            ->middleware('throttle:3,10')->name('password.send');

        Route::get('reset-password', [PasswordResetController::class, 'reset'])->name('password.reset');
        Route::post('reset-password', [PasswordResetController::class, 'update'])
            ->middleware('throttle:10,10')->name('password.update');
    });

    Route::middleware('auth:patient')->group(function () {
        Route::post('logout', [SessionController::class, 'destroy'])->name('logout');

        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments');
        Route::get('documents', [DocumentController::class, 'index'])->name('documents');
        Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::post('documents/{document}/pay', [PaymentController::class, 'initiate'])
            ->middleware('throttle:10,1')->name('payments.initiate');

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    // Payment gateway redirects back to these URLs after the user completes payment.
    // Not gated behind auth:patient: the session may have expired by the time the user
    // returns from the gateway, and the page still needs to display. Ownership is proved
    // by the tran_id lookup, not by who is logged in.
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::any('{transaction}/success', [PaymentController::class, 'success'])->name('success');
        Route::any('{transaction}/fail', [PaymentController::class, 'fail'])->name('fail');
        Route::any('{transaction}/cancel', [PaymentController::class, 'cancel'])->name('cancel');
    });
});
