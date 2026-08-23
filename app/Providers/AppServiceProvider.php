<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\Department;
use App\Sms\Contracts\SmsGateway;
use App\Sms\SmsManager;
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

        // The staff sidebar carries counts for the two things that go stale if
        // nobody looks at them: unanswered bookings and an unread inbox.
        View::composer('admin.partials.sidebar', function ($view) {
            $view->with([
                'pendingAppointments' => Appointment::where('status', 'pending')
                    ->whereDate('appointment_date', '>=', today())
                    ->count(),
                'unreadMessages' => ContactMessage::where('is_read', false)->count(),
            ]);
        });
    }
}
