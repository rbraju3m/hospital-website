<?php

use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\DoctorScheduleController;
use App\Http\Controllers\Admin\HealthPackageController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
| The staff panel. Everything here sits behind `auth`; guests are bounced to
| admin.login by the redirect configured in bootstrap/app.php.
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('/', DashboardController::class)->name('dashboard');

        /*
        | Front-desk work: appointments taken by phone, and the contact inbox.
        */
        Route::get('appointments/export', [AppointmentController::class, 'export'])->name('appointments.export');
        Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])
            ->name('appointments.status');
        Route::resource('appointments', AppointmentController::class)->except(['edit', 'update']);

        Route::patch('messages/{message}/read', [ContactMessageController::class, 'toggleRead'])->name('messages.read');
        Route::resource('messages', ContactMessageController::class)->only(['index', 'show', 'destroy'])
            ->parameters(['messages' => 'message']);

        /*
        | Editorial content. Every one of these is translatable — the forms post
        | base-locale columns alongside a translations[<locale>][<column>] map.
        */
        Route::resource('departments', DepartmentController::class);
        Route::resource('doctors', DoctorController::class);
        Route::resource('services', ServiceController::class);
        Route::resource('packages', HealthPackageController::class)->parameters(['packages' => 'healthPackage']);
        Route::resource('posts', PostController::class);
        Route::resource('testimonials', TestimonialController::class);

        // Chamber hours hang off a doctor rather than standing on their own.
        Route::post('doctors/{doctor}/schedules', [DoctorScheduleController::class, 'store'])
            ->name('doctors.schedules.store');
        Route::put('doctors/{doctor}/schedules/{schedule}', [DoctorScheduleController::class, 'update'])
            ->name('doctors.schedules.update');
        Route::delete('doctors/{doctor}/schedules/{schedule}', [DoctorScheduleController::class, 'destroy'])
            ->name('doctors.schedules.destroy');

        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        Route::resource('users', UserController::class)->except(['show']);
    });
});
