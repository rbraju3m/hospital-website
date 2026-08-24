<?php

use App\Http\Controllers\Web\AppointmentController;
use App\Http\Controllers\Web\ContactController;
use App\Http\Controllers\Web\DepartmentController;
use App\Http\Controllers\Web\DiagnosticController;
use App\Http\Controllers\Web\DoctorController;
use App\Http\Controllers\Web\GalleryController;
use App\Http\Controllers\Web\HealthPackageController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\PostController;
use App\Http\Controllers\Web\ServiceController;
use App\Http\Middleware\MaintenanceGate;
use Illuminate\Support\Facades\Route;

/*
| The public site.
|
| `feature:<key>` mirrors the Site controls page in the staff panel: an area
| switched off there stops answering here as well, so a hidden section cannot
| be reached from a bookmark, an old link or a search result. Defaults are all
| "on", so this file behaves exactly as it reads until somebody decides
| otherwise. Staff signed in to the panel bypass both gates and keep seeing the
| whole site.
*/

Route::middleware(MaintenanceGate::class)->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');

    Route::middleware('feature:area_departments')->group(function () {
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('/departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
    });

    Route::middleware('feature:area_doctors')->group(function () {
        Route::get('/doctors', [DoctorController::class, 'index'])->name('doctors.index');
        Route::get('/doctors/{doctor}', [DoctorController::class, 'show'])->name('doctors.show');
    });

    Route::middleware('feature:area_services')->group(function () {
        Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
        Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
    });

    Route::middleware('feature:area_packages')->group(function () {
        Route::get('/health-packages', [HealthPackageController::class, 'index'])->name('packages.index');
        Route::get('/health-packages/{healthPackage}', [HealthPackageController::class, 'show'])->name('packages.show');
    });

    Route::middleware('feature:area_diagnostics')->group(function () {
        Route::get('/diagnostics', [DiagnosticController::class, 'index'])->name('diagnostics.index');
        Route::get('/diagnostics/{diagnosticTest}', [DiagnosticController::class, 'show'])->name('diagnostics.show');
        Route::post('/diagnostics/{diagnosticTest}/request', [DiagnosticController::class, 'store'])
            ->middleware(['throttle:10,1', 'feature:behaviour_test_request'])
            ->name('diagnostics.request');
    });

    Route::middleware('feature:area_posts')->group(function () {
        Route::get('/health-hub', [PostController::class, 'index'])->name('posts.index');
        Route::get('/health-hub/{post}', [PostController::class, 'show'])->name('posts.show');
    });

    Route::middleware('feature:area_gallery')->group(function () {
        Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');
        Route::get('/gallery/{album}', [GalleryController::class, 'show'])->name('gallery.show');
    });

    Route::get('/about', [PageController::class, 'about'])
        ->middleware('feature:area_about')->name('about');
    Route::get('/emergency', [PageController::class, 'emergency'])
        ->middleware('feature:area_emergency')->name('emergency');
    Route::get('/international-patients', [PageController::class, 'international'])
        ->middleware('feature:area_international')->name('international');

    Route::middleware('feature:area_contact')->group(function () {
        Route::get('/contact', [PageController::class, 'contact'])->name('contact');
        Route::post('/contact', [ContactController::class, 'store'])
            ->middleware(['throttle:10,1', 'feature:behaviour_contact_form'])
            ->name('contact.store');
    });

    Route::prefix('appointment')->name('appointment.')->middleware('feature:area_appointment')->group(function () {
        Route::get('/', [AppointmentController::class, 'create'])->name('create');
        Route::get('/doctors', [AppointmentController::class, 'doctors'])->name('doctors');
        Route::get('/slots', [AppointmentController::class, 'slots'])->name('slots');
        Route::post('/', [AppointmentController::class, 'store'])
            // Booking can be closed on its own — the page stays up and explains
            // itself, but the form stops accepting submissions.
            ->middleware(['throttle:10,1', 'feature:behaviour_online_booking'])
            ->name('store');
        // Signed: the page carries a patient's name, phone, age and gender, and a
        // reference is short enough to enumerate. The link in the confirmation
        // email keeps working; a guessed one does not.
        Route::get('/{appointment}/confirmed', [AppointmentController::class, 'confirmed'])
            ->middleware('signed')
            ->name('confirmed');
    });
});
