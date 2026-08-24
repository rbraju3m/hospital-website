<?php

use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\MaintenanceGate;
use App\Http\Middleware\SetLocale;
use App\Services\MediaLibrary;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // The staff panel shares the web group: session, CSRF and SetLocale
            // all apply, so /admin is bilingual for exactly the same reasons.
            Route::middleware('web')->group(base_path('routes/admin.php'));
            Route::middleware('web')->group(base_path('routes/portal.php'));
            // Payment gateway IPN callbacks: no session, no CSRF, no SetLocale needed.
            Route::group([], base_path('routes/payments.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Runs after the session is started, so a stored locale choice is visible.
        $middleware->appendToGroup('web', SetLocale::class);

        // `feature:<key>` closes a route whose Site-controls switch is off, so
        // hiding an area from the navigation also stops its URL working.
        $middleware->alias(['feature' => EnsureFeatureEnabled::class]);

        // SSLCommerz redirect back from the payment gateway: the browser returns
        // after payment, and SSLCommerz posts the result without a CSRF token.
        $middleware->validateCsrfTokens(except: [
            'portal/payments/*/success',
            'portal/payments/*/fail',
            'portal/payments/*/cancel',
        ]);

        // Two audiences, two sign-in screens. A patient sent to the staff
        // login would be told to ask IT for an account that does not exist.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('portal', 'portal/*')
            ? route('portal.login')
            : route('admin.login'));

        $middleware->redirectUsersTo(fn (Request $request) => $request->is('portal', 'portal/*')
            ? route('portal.dashboard')
            : route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A POST bigger than `post_max_size` reaches PHP with its body thrown
        // away — no fields, no files, no CSRF token. Left alone that surfaces
        // as a bare 413, or as "page expired", neither of which mentions the
        // upload. Send it back to the form saying what the limit is.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return back()->withErrors([
                'photos' => __('admin.form.upload_too_large', [
                    'size' => round(MediaLibrary::maxKilobytes() / 1024, 1),
                ]),
            ]);
        });
    })->create();
