<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
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
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Runs after the session is started, so a stored locale choice is visible.
        $middleware->appendToGroup('web', SetLocale::class);

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
    })->create();
