<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\Department;
use App\Models\DiagnosticTest;
use App\Models\Doctor;
use App\Models\HealthPackage;
use App\Models\Post;
use App\Models\Service;
use App\Models\Testimonial;
use App\Support\SiteFeatures;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Content types checked for translation gaps, mapped to the route that
     * edits them so the dashboard can link straight at the work.
     */
    private const TRANSLATABLE = [
        'departments' => Department::class,
        'doctors' => Doctor::class,
        'services' => Service::class,
        'packages' => HealthPackage::class,
        'diagnostics' => DiagnosticTest::class,
        'posts' => Post::class,
        'testimonials' => Testimonial::class,
    ];

    /**
     * One page, assembled from whichever of its three halves the signed-in role
     * can see: the desk's day, the inbox, and the state of the content.
     *
     * Gated in the controller rather than only in the template, so an editor's
     * home page does not run a query over every patient booked in today before
     * deciding not to show them.
     */
    public function __invoke(Request $request): View
    {
        $today = Carbon::today();
        $user = $request->user();

        $desk = $user->canReach('appointments');
        $inbox = $user->canReach('messages');
        $content = $user->canReach('departments');
        $system = $user->canReach('site_controls');

        return view('admin.dashboard.index', [
            'stats' => array_filter([
                'today' => $desk ? Appointment::whereDate('appointment_date', $today)
                    ->whereNot('status', 'cancelled')->count() : null,
                'pending' => $desk ? Appointment::where('status', 'pending')
                    ->whereDate('appointment_date', '>=', $today)->count() : null,
                'week' => $desk ? Appointment::whereBetween('appointment_date', [$today, $today->copy()->addDays(6)])
                    ->whereNot('status', 'cancelled')->count() : null,
                'unread' => $inbox ? ContactMessage::where('is_read', false)->count() : null,
            ], fn ($value) => $value !== null),
            'todaysAppointments' => $desk ? Appointment::with('doctor')
                ->whereDate('appointment_date', $today)
                ->whereNot('status', 'cancelled')
                ->orderBy('appointment_time')
                ->get() : null,
            'recentMessages' => $inbox ? ContactMessage::latest()->take(5)->get() : null,
            'catalogue' => $content ? [
                'departments' => Department::count(),
                'doctors' => Doctor::where('is_active', true)->count(),
                'services' => Service::count(),
                'packages' => HealthPackage::count(),
                'diagnostics' => DiagnosticTest::active()->count(),
                'posts' => Post::published()->count(),
            ] : null,
            'translationGaps' => $content ? $this->translationGaps() : [],
            'weekTrend' => $desk ? $this->weekTrend($today) : [],
            'statusBreakdown' => $desk ? $this->statusBreakdown($today) : [],
            // What the site is currently hiding, so nobody spends an afternoon
            // wondering why a page 404s that somebody switched off last week.
            // Only an administrator can act on it, and only they can see it.
            'featuresOff' => $system
                ? collect(SiteFeatures::all())->except('behaviour_maintenance')->reject()->keys()
                : collect(),
            'maintenance' => $system && SiteFeatures::enabled('behaviour_maintenance'),
        ]);
    }

    /**
     * Booked appointments for each of the next seven days.
     *
     * Forward-looking rather than historical: this is a front desk's workload,
     * not a report. Cancelled bookings are excluded because nobody is coming.
     *
     * @return list<array{date: Carbon, count: int}>
     */
    private function weekTrend(Carbon $today): array
    {
        $counts = Appointment::query()
            ->selectRaw('appointment_date, COUNT(*) as total')
            ->whereBetween('appointment_date', [$today, $today->copy()->addDays(6)])
            ->whereNot('status', 'cancelled')
            ->groupBy('appointment_date')
            ->pluck('total', 'appointment_date');

        return collect(range(0, 6))
            ->map(function (int $offset) use ($today, $counts) {
                $date = $today->copy()->addDays($offset);

                return [
                    'date' => $date,
                    'count' => (int) ($counts[$date->toDateString()] ?? 0),
                ];
            })
            ->all();
    }

    /**
     * Appointments by status over the coming fortnight.
     *
     * @return array<string, int>
     */
    private function statusBreakdown(Carbon $today): array
    {
        return Appointment::query()
            ->selectRaw('status, COUNT(*) as total')
            ->whereBetween('appointment_date', [$today, $today->copy()->addDays(13)])
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    /**
     * Rows of content that have no translation for a given locale.
     *
     * Counted in PHP rather than SQL: missingTranslations() skips fields that
     * are blank in the source too, which a JSON_EXTRACT query could not tell
     * apart from a genuine gap.
     *
     * @return array<string, array<string, int>>
     */
    private function translationGaps(): array
    {
        $gaps = [];

        foreach (translation_locales() as $locale) {
            foreach (self::TRANSLATABLE as $key => $class) {
                /** @var class-string<Model> $class */
                $incomplete = $class::query()->get()
                    ->filter(fn (Model $model) => ! $model->isFullyTranslated($locale))
                    ->count();

                if ($incomplete > 0) {
                    $gaps[$locale][$key] = $incomplete;
                }
            }
        }

        return $gaps;
    }
}
