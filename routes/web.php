<?php

use App\Http\Controllers\Web\AppointmentController;
use App\Http\Controllers\Web\ContactController;
use App\Http\Controllers\Web\DepartmentController;
use App\Http\Controllers\Web\DiagnosticController;
use App\Http\Controllers\Web\DoctorController;
use App\Http\Controllers\Web\HealthPackageController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\PostController;
use App\Http\Controllers\Web\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');

Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
Route::get('/departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');

Route::get('/doctors', [DoctorController::class, 'index'])->name('doctors.index');
Route::get('/doctors/{doctor}', [DoctorController::class, 'show'])->name('doctors.show');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');

Route::get('/health-packages', [HealthPackageController::class, 'index'])->name('packages.index');
Route::get('/health-packages/{healthPackage}', [HealthPackageController::class, 'show'])->name('packages.show');

Route::get('/diagnostics', [DiagnosticController::class, 'index'])->name('diagnostics.index');
Route::get('/diagnostics/{diagnosticTest}', [DiagnosticController::class, 'show'])->name('diagnostics.show');
Route::post('/diagnostics/{diagnosticTest}/request', [DiagnosticController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('diagnostics.request');

Route::get('/health-hub', [PostController::class, 'index'])->name('posts.index');
Route::get('/health-hub/{post}', [PostController::class, 'show'])->name('posts.show');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/emergency', [PageController::class, 'emergency'])->name('emergency');
Route::get('/international-patients', [PageController::class, 'international'])->name('international');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('contact.store');

Route::prefix('appointment')->name('appointment.')->group(function () {
    Route::get('/', [AppointmentController::class, 'create'])->name('create');
    Route::get('/doctors', [AppointmentController::class, 'doctors'])->name('doctors');
    Route::get('/slots', [AppointmentController::class, 'slots'])->name('slots');
    Route::post('/', [AppointmentController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('store');
    // Signed: the page carries a patient's name, phone, age and gender, and a
    // reference is short enough to enumerate. The link in the confirmation
    // email keeps working; a guessed one does not.
    Route::get('/{appointment}/confirmed', [AppointmentController::class, 'confirmed'])
        ->middleware('signed')
        ->name('confirmed');
});
