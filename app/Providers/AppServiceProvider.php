<?php

namespace App\Providers;

use App\Models\Department;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // The header mega-menu and footer both need the department list on
        // every page, so bind it once rather than in each controller.
        //
        // Whole rows, not a column list: a partial select omits `translations`
        // and the locale-aware attributes would silently fall back to English.
        View::composer(['partials.header', 'partials.footer'], function ($view) {
            $view->with('navDepartments', Department::active()->ordered()->get());
        });
    }
}
