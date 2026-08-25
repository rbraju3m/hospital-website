<?php

namespace App\Providers;

use App\Models\Department;
use App\Payments\SslcommerzGateway;
use App\Sms\Contracts\SmsGateway;
use App\Sms\SmsManager;
use App\Support\PanelNavigation;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One gateway for the whole app; which driver it is comes from
        // config/sms.php, so tests and staging can swap in `null`.
        $this->app->singleton(SmsGateway::class, fn () => new SmsManager);

        $this->app->singleton(SslcommerzGateway::class, fn () => new SslcommerzGateway(config('sslcommerz')));
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

        // The panel's menu, resolved once per request. Bound to the layout
        // rather than to the sidebar because the partials it includes inherit
        // its data — so the sidebar and anything else that renders the menu
        // share one copy, and the badge counts are queried once.
        View::composer('admin.layouts.app', function ($view) {
            $groups = PanelNavigation::groups();

            $view->with([
                'panelGroups' => $groups,
                'panelPalette' => PanelNavigation::palette($groups),
            ]);
        });
    }
}
