<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use App\Support\PanelNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The panel's menu comes from one registry, so this is where a link added to it
 * is checked: that the route exists, that both locales name it, that its icon
 * is one the icon component actually draws, and that the page it points at
 * answers. Nothing else in the suite renders the sidebar deliberately.
 */
class PanelNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    /** @return array<int, array<string, mixed>> */
    private function declaredItems(): array
    {
        return array_merge(...array_column(PanelNavigation::registry(), 'items'));
    }

    public function test_every_menu_item_names_a_route_that_exists(): void
    {
        foreach ($this->declaredItems() as $item) {
            $this->assertTrue(
                Route::has($item['route']),
                "The menu points at {$item['route']}, which is not a route.",
            );
        }
    }

    public function test_every_menu_item_is_labelled_in_every_locale(): void
    {
        foreach (config('app.available_locales') as $locale => $name) {
            foreach ($this->declaredItems() as $item) {
                $this->assertTrue(
                    Lang::has("admin.nav.{$item['key']}", $locale),
                    "admin.nav.{$item['key']} is missing from {$locale}.",
                );
            }

            foreach (array_filter(array_column(PanelNavigation::registry(), 'heading')) as $heading) {
                $this->assertTrue(
                    Lang::has("admin.nav.group_{$heading}", $locale),
                    "admin.nav.group_{$heading} is missing from {$locale}.",
                );
            }
        }
    }

    /**
     * The icon component falls back to `activity` for a name it does not know,
     * so a typo is a plausible-looking icon rather than an error. Read the
     * component's own list instead of rendering it — `services` is genuinely
     * `activity`, and the fallback cannot be told apart from the real thing.
     */
    public function test_every_menu_item_uses_an_icon_that_exists(): void
    {
        preg_match_all(
            "/^\s*'([a-z0-9-]+)' => '</m",
            file_get_contents(resource_path('views/components/icon.blade.php')),
            $matches,
        );

        $this->assertContains('stethoscope', $matches[1], 'The icon list did not parse.');

        foreach ($this->declaredItems() as $item) {
            $this->assertContains(
                $item['icon'],
                $matches[1],
                "The menu asks for the icon {$item['icon']}, which the icon component does not have.",
            );
        }
    }

    public function test_every_menu_item_reaches_a_page_that_answers(): void
    {
        foreach (PanelNavigation::items() as $item) {
            $this->get($item['url'])->assertOk();
        }
    }

    public function test_the_sidebar_marks_the_section_being_looked_at(): void
    {
        $html = $this->get(route('admin.doctors.index'))->getContent();

        $this->assertMatchesRegularExpression(
            '/admin-nav-item-active[^>]*>\s*<x?[^>]*>?.*?'.preg_quote(__('admin.nav.doctors'), '/').'/s',
            $html,
        );

        // And only the section being looked at.
        $this->assertSame(1, substr_count($html, 'admin-nav-item-active'));
        $this->assertStringContainsString('aria-current="page"', $html);
    }

    public function test_the_badges_count_work_that_is_waiting(): void
    {
        $department = Department::create(['name' => 'Cardiology', 'slug' => 'cardiology', 'icon' => 'heart-pulse']);
        $doctor = Doctor::create([
            'department_id' => $department->id,
            'name' => 'Dr. Farhana Islam',
            'slug' => 'dr-farhana-islam',
        ]);

        $appointment = fn (string $reference, string $status, string $date) => Appointment::create([
            'reference' => $reference,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'patient_name' => 'Rahim Uddin',
            'phone' => '01712345678',
            'appointment_date' => $date,
            'appointment_time' => '17:'.substr($reference, -2),
            'status' => $status,
        ]);

        $appointment('RBRPEND01', 'pending', today()->toDateString());
        $appointment('RBRPEND02', 'pending', today()->addDay()->toDateString());
        // Neither of these is waiting on anybody: one is agreed, and the visit
        // the other was for has already been missed.
        $appointment('RBRCONF03', 'confirmed', today()->addDay()->toDateString());
        $appointment('RBRPAST04', 'pending', today()->subDay()->toDateString());

        ContactMessage::create(['name' => 'Shirin Akter', 'phone' => '01812345678', 'message' => 'Question']);
        ContactMessage::create(['name' => 'Kamal Hossain', 'phone' => '01812345679', 'message' => 'Read', 'is_read' => true]);

        $items = collect(PanelNavigation::items())->keyBy('key');

        $this->assertSame(2, $items['appointments']['badge']);
        $this->assertSame(1, $items['messages']['badge']);
        $this->assertNull($items['doctors']['badge']);
    }

    public function test_a_count_of_nothing_is_not_rendered_as_a_zero(): void
    {
        $html = $this->get(route('admin.dashboard'))->getContent();

        $this->assertStringNotContainsString('rail-badge', $html);
    }

    public function test_every_add_screen_the_palette_offers_exists_and_is_named(): void
    {
        $creates = array_filter(array_column($this->declaredItems(), 'create'));

        // Every section that can be added to, and nothing that cannot.
        $this->assertCount(11, $creates);

        foreach ($creates as $route) {
            $this->assertTrue(Route::has($route), "The palette points at {$route}, which is not a route.");

            // The route name is also the label key. Asserted rather than
            // trusted: the palette would otherwise offer a row reading
            // "admin.doctors.create" and nobody would notice until it shipped.
            foreach (config('app.available_locales') as $locale => $name) {
                $this->assertTrue(Lang::has($route, $locale), "{$route} is missing from {$locale}.");
            }
        }
    }

    public function test_the_palette_lists_every_section_and_every_add_screen(): void
    {
        $entries = PanelNavigation::palette();
        $labels = array_column($entries, 'label');

        $this->assertCount(count($this->declaredItems()) + 11, $entries);

        $this->assertContains(__('admin.nav.doctors'), $labels);
        $this->assertContains(__('admin.doctors.create'), $labels);
        // Nothing to add to the dashboard, the inbox or the switches.
        $this->assertNotContains(__('admin.nav.dashboard'), array_column(
            array_filter($entries, fn ($entry) => $entry['kind'] === 'create'),
            'group',
        ));

        foreach ($entries as $entry) {
            $this->assertNotEmpty($entry['label']);
            $this->assertStringStartsWith('http', $entry['url']);
        }
    }

    public function test_the_palette_counts_the_badges_once_with_the_sidebar(): void
    {
        // palette() takes the resolved groups, so the counts behind the two
        // are the same two queries rather than four.
        $groups = PanelNavigation::groups();
        $entries = collect(PanelNavigation::palette($groups))->keyBy('label');

        $this->assertArrayHasKey('badge', $entries[__('admin.nav.appointments')]);
        $this->assertSame(
            collect($groups)->flatMap->items->firstWhere('key', 'appointments')['badge'],
            $entries[__('admin.nav.appointments')]['badge'],
        );
    }

    public function test_the_palette_is_on_the_page_with_a_handle_and_a_shortcut(): void
    {
        $html = $this->get(route('admin.dashboard'))->getContent();

        $this->assertStringContainsString('panelPalette(', $html);
        $this->assertStringContainsString(__('admin.palette.placeholder'), $html);
        $this->assertStringContainsString('$dispatch(\'panel-palette\')', $html);
        $this->assertStringContainsString('data-shortcut-mod', $html);

        // The list is rendered into the page, not fetched.
        $this->assertStringContainsString(e(__('admin.doctors.create'), false), $html);
    }

    public function test_the_menu_collapses_and_remembers_the_choice(): void
    {
        $html = $this->get(route('admin.dashboard'))->getContent();

        $this->assertStringContainsString('data-panel-rail-toggle', $html);
        $this->assertStringContainsString(__('admin.rail.collapse'), $html);

        // Settled before the first paint, or the sidebar renders wide and snaps.
        $this->assertStringContainsString("localStorage.getItem('panel-rail')", $html);
        $this->assertStringContainsString("classList.add('panel-rail')", $html);

        // And the label a collapsed item is named by.
        $this->assertStringContainsString('data-panel-tip="'.__('admin.nav.doctors').'"', $html);
        $this->assertStringContainsString('data-panel-tip-box', $html);
    }
}
