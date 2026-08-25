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

Route::prefix('portal')->name('portal.')->middleware('feature:area_portal')->group(function () {
    Route::middleware('guest:patient')->group(function () {
        Route::get('login', [SessionController::class, 'create'])->name('login');
        Route::post('login', [SessionController::class, 'store'])
            ->middleware('throttle:10,1')->name('login.store');

        // Registration closes on its own switch: an existing patient can still
        // sign in and read their records while new sign-ups are paused.
        Route::middleware('feature:behaviour_portal_registration')->group(function () {
            Route::get('register', [RegisterController::class, 'create'])->name('register');
            Route::post('register', [RegisterController::class, 'store'])
                ->middleware('throttle:5,1')->name('register.store');
        });

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

        /* Moving and cancelling a booking, behind their own switch: a hospital
           that would rather patients rang the desk turns this off and the
           portal goes back to showing records. The routes close with it, so a
           bookmarked reschedule page is not a way round the decision. */
        Route::middleware('feature:behaviour_portal_changes')->group(function () {
            Route::get('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])
                ->name('appointments.reschedule');
            Route::get('appointments/{appointment}/slots', [AppointmentController::class, 'slots'])
                ->name('appointments.slots');
            Route::patch('appointments/{appointment}/reschedule', [AppointmentController::class, 'move'])
                ->middleware('throttle:10,1')->name('appointments.move');
            Route::patch('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])
                ->middleware('throttle:10,1')->name('appointments.cancel');
        });
        Route::get('documents', [DocumentController::class, 'index'])->name('documents');
        Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::post('documents/{document}/pay', [PaymentController::class, 'initiate'])
            ->middleware(['throttle:10,1', 'feature:behaviour_online_payment'])->name('payments.initiate');

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
