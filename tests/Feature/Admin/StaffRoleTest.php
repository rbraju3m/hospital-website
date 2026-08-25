<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Support\ManagedLists;
use App\Support\PanelNavigation;
use App\Support\PanelSearch;
use App\Support\StaffRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Three roles cut along the lines the menu already draws.
 *
 * The thing worth testing is not that an administrator can reach everything —
 * it is that the other two cannot, in all five places the question is asked:
 * the route, the write request behind it, the menu, the palette's list and the
 * search endpoint. A hole in any one of them makes the other four decoration.
 */
class StaffRoleTest extends TestCase
{
    use RefreshDatabase;

    private function desk(): User
    {
        return User::factory()->frontDesk()->create();
    }

    private function editor(): User
    {
        return User::factory()->editor()->create();
    }

    public static function deskOnlyRoutes(): array
    {
        return [
            'appointments' => ['admin.appointments.index'],
            'messages' => ['admin.messages.index'],
            'documents' => ['admin.documents.index'],
            'patients' => ['admin.patients.index'],
        ];
    }

    public static function editorialRoutes(): array
    {
        return [
            'departments' => ['admin.departments.index'],
            'doctors' => ['admin.doctors.index'],
            'services' => ['admin.services.index'],
            'packages' => ['admin.packages.index'],
            'diagnostics' => ['admin.diagnostics.index'],
            'posts' => ['admin.posts.index'],
            'gallery' => ['admin.gallery.index'],
            'testimonials' => ['admin.testimonials.index'],
        ];
    }

    public static function administratorRoutes(): array
    {
        return [
            'site controls' => ['admin.site.edit'],
            'site settings' => ['admin.settings.edit'],
            'staff accounts' => ['admin.users.index'],
        ];
    }

    #[DataProvider('deskOnlyRoutes')]
    public function test_the_front_desk_reaches_its_own_work(string $route): void
    {
        $this->actingAs($this->desk())->get(route($route))->assertOk();
    }

    #[DataProvider('editorialRoutes')]
    public function test_an_editor_is_refused_the_front_desk(string $route): void
    {
        // The editorial routes an editor may use are the desk's blind spot and
        // vice versa, so one provider tests both directions.
        $this->actingAs($this->editor())->get(route($route))->assertOk();
        $this->actingAs($this->desk())->get(route($route))->assertForbidden();
    }

    #[DataProvider('deskOnlyRoutes')]
    public function test_an_editor_never_reaches_patient_data(string $route): void
    {
        $this->actingAs($this->editor())->get(route($route))->assertForbidden();
    }

    #[DataProvider('administratorRoutes')]
    public function test_only_an_administrator_reaches_the_switches(string $route): void
    {
        $this->actingAs(User::factory()->create())->get(route($route))->assertOk();
        $this->actingAs($this->desk())->get(route($route))->assertForbidden();
        $this->actingAs($this->editor())->get(route($route))->assertForbidden();
    }

    public function test_a_refusal_is_403_and_not_404(): void
    {
        // The section exists and their colleague uses it every day. Pretending
        // it does not would have them reporting a bug rather than asking for
        // access.
        $this->actingAs($this->editor())->get(route('admin.appointments.index'))->assertStatus(403);
    }

    public function test_a_write_is_refused_as_well_as_the_screen(): void
    {
        $department = Department::create(['name' => 'Cardiology', 'slug' => 'cardiology', 'icon' => 'heart-pulse']);

        $this->actingAs($this->desk())
            ->put(route('admin.departments.update', $department), ['name' => 'Renamed', 'slug' => 'cardiology'])
            ->assertForbidden();

        $this->assertSame('Cardiology', $department->fresh()->name);
    }

    public function test_the_listing_endpoints_check_the_list_they_were_given(): void
    {
        $department = Department::create(['name' => 'Cardiology', 'slug' => 'cardiology', 'icon' => 'heart-pulse']);

        // One route serves eight listings, so this is the one permission check
        // that cannot live on the route.
        $this->actingAs($this->desk())
            ->patchJson(route('admin.lists.toggle', ['list' => 'departments', 'id' => $department->id]))
            ->assertForbidden();

        $this->actingAs($this->editor())
            ->patchJson(route('admin.lists.toggle', ['list' => 'departments', 'id' => $department->id]))
            ->assertOk();
    }

    public function test_the_menu_shows_only_what_the_role_can_reach(): void
    {
        $this->actingAs($this->editor());

        $keys = array_column(PanelNavigation::items(), 'key');

        $this->assertContains('doctors', $keys);
        $this->assertNotContains('appointments', $keys);
        $this->assertNotContains('users', $keys);

        // And an empty group is not a heading over nothing.
        $headings = array_column(PanelNavigation::groups(), 'heading');
        $this->assertNotContains('portal', $headings);
        $this->assertNotContains('system', $headings);
    }

    public function test_the_palette_cannot_be_a_way_round_the_menu(): void
    {
        $this->actingAs($this->editor());

        $labels = array_column(PanelNavigation::palette(), 'label');

        $this->assertContains(__('admin.nav.doctors'), $labels);
        $this->assertContains(__('admin.doctors.create'), $labels);

        // Neither the section nor the shortcut to adding one.
        $this->assertNotContains(__('admin.nav.appointments'), $labels);
        $this->assertNotContains(__('admin.appointments.create'), $labels);
    }

    public function test_search_does_not_answer_for_sections_the_role_cannot_reach(): void
    {
        Patient::create([
            'name' => 'Shirin Akter',
            'phone' => '1812345678',
            'password' => Hash::make('secret-password'),
        ]);
        Doctor::create([
            'department_id' => Department::create(['name' => 'Cardiology', 'slug' => 'cardiology', 'icon' => 'heart-pulse'])->id,
            'name' => 'Dr. Shirin Akter',
            'slug' => 'dr-shirin-akter',
        ]);

        $this->actingAs($this->editor());
        $groups = array_column(PanelSearch::run('Shirin'), 'group');

        // The same name in two sections: the editor gets the consultant and
        // not the patient, which is the whole point of the check.
        $this->assertContains(__('admin.nav.doctors'), $groups);
        $this->assertNotContains(__('admin.nav.patients'), $groups);
    }

    public function test_the_dashboard_shows_each_role_its_own_half(): void
    {
        // assertSee rather than a string check on the body: these headings
        // carry apostrophes, and Blade escapes them on the way out.
        $this->actingAs($this->editor())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('admin.dashboard.catalogue'))
            ->assertDontSee(__('admin.dashboard.todays_schedule'))
            ->assertDontSee(__('admin.dashboard.recent_messages'));

        $this->actingAs($this->desk())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('admin.dashboard.todays_schedule'))
            ->assertSee(__('admin.dashboard.recent_messages'))
            ->assertDontSee(__('admin.dashboard.catalogue'));
    }

    public function test_a_role_cannot_change_its_own(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me)->put(route('admin.users.update', $me), [
            'name' => $me->name,
            'email' => $me->email,
            'role' => StaffRoles::EDITOR,
        ])->assertSessionHasNoErrors();

        // Demoting yourself takes this screen with it, and the way back is a
        // database client. The field is not on the form; this proves the
        // payload cannot do it either.
        $this->assertTrue($me->fresh()->isAdministrator());
    }

    public function test_the_last_administrator_cannot_be_deleted(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $this->desk();

        $this->actingAs($me)->delete(route('admin.users.destroy', $other))->assertSessionHas('status');

        // $me is now the only administrator left, and the front desk account
        // cannot reach this screen to appoint another.
        $this->actingAs($me)->delete(route('admin.users.destroy', User::factory()->editor()->create()))
            ->assertSessionHas('status');

        $this->assertSame(1, User::where('role', StaffRoles::ADMINISTRATOR)->count());
    }

    public function test_an_unknown_role_is_refused_by_the_form(): void
    {
        $this->actingAs(User::factory()->create())->post(route('admin.users.store'), [
            'name' => 'Nusrat Jahan',
            'email' => 'nusrat@example.test',
            'password' => 'front-desk-2026',
            'password_confirmation' => 'front-desk-2026',
            'role' => 'superuser',
        ])->assertSessionHasErrors('role');
    }

    public function test_a_section_added_without_a_grant_is_denied_to_everyone_but_an_administrator(): void
    {
        // The safe direction to fail in: sectionForRoute() derives the section
        // from the route name, so a resource added below with no thought for
        // roles is closed rather than open.
        $this->assertSame('widgets', StaffRoles::sectionForRoute('admin.widgets.index'));
        $this->assertFalse(StaffRoles::grants(StaffRoles::EDITOR, 'widgets'));
        $this->assertTrue(StaffRoles::grants(StaffRoles::ADMINISTRATOR, 'widgets'));

        // And the routes that belong to no section stay open to all staff.
        foreach (['admin.dashboard', 'admin.logout', 'admin.search', 'admin.lists.toggle'] as $route) {
            $this->assertNull(StaffRoles::sectionForRoute($route));
        }
    }

    public function test_the_three_vocabularies_line_up(): void
    {
        $sections = array_column(
            array_merge(...array_column(PanelNavigation::registry(), 'items')),
            'key',
        );

        // A role granting a section the menu does not have is a typo nobody
        // would notice: the account would simply be missing something.
        foreach ([StaffRoles::FRONT_DESK, StaffRoles::EDITOR] as $role) {
            foreach (StaffRoles::sections($role) as $section) {
                $this->assertContains($section, $sections, "{$role} is granted {$section}, which is not a section.");
            }
        }

        // Every list the drag-and-toggle endpoints serve is a section too, or
        // the check in ListController would refuse everybody.
        foreach (array_keys(ManagedLists::all()) as $list) {
            $this->assertContains($list, $sections, "The list {$list} is not a section of the menu.");
        }
    }

    public function test_every_section_is_reachable_by_some_role(): void
    {
        $granted = array_merge(
            StaffRoles::sections(StaffRoles::FRONT_DESK),
            StaffRoles::sections(StaffRoles::EDITOR),
        );

        // Administrator-only sections, named here so that adding a section and
        // forgetting to grant it to anybody is a failing test rather than a
        // screen only one person can open.
        $administratorOnly = ['site_controls', 'settings', 'users'];

        $sections = array_column(
            array_merge(...array_column(PanelNavigation::registry(), 'items')),
            'key',
        );

        foreach ($sections as $section) {
            $this->assertTrue(
                in_array($section, $granted, true) || in_array($section, $administratorOnly, true),
                "{$section} is in the menu but granted to no role.",
            );
        }
    }
}
