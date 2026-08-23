<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function guardedRoutes(): array
    {
        return [
            'dashboard' => ['admin.dashboard'],
            'appointments' => ['admin.appointments.index'],
            'messages' => ['admin.messages.index'],
            'departments' => ['admin.departments.index'],
            'doctors' => ['admin.doctors.index'],
            'services' => ['admin.services.index'],
            'packages' => ['admin.packages.index'],
            'posts' => ['admin.posts.index'],
            'testimonials' => ['admin.testimonials.index'],
            'settings' => ['admin.settings.edit'],
            'users' => ['admin.users.index'],
        ];
    }

    #[DataProvider('guardedRoutes')]
    public function test_a_guest_is_sent_to_the_login_screen(string $route): void
    {
        $this->get(route($route))->assertRedirect(route('admin.login'));
    }

    #[DataProvider('guardedRoutes')]
    public function test_a_signed_in_user_reaches_every_section(string $route): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route($route))
            ->assertOk();
    }

    public function test_a_write_route_is_guarded_too(): void
    {
        // The redirect above only proves the GETs are covered; a POST that fell
        // outside the middleware would be a far worse hole.
        $this->post(route('admin.departments.store'), ['name' => 'Injected'])
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseMissing('departments', ['name' => 'Injected']);
    }

    public function test_valid_credentials_sign_a_user_in(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-horse')]);

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'correct-horse',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_credentials_are_rejected(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-horse')]);

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'wrong',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_repeated_failures_are_throttled(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-horse')]);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'wrong']);
        }

        // The sixth attempt is refused even with the right password.
        $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'correct-horse'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_signing_out_ends_the_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_the_panel_is_not_indexable(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('noindex', escape: false);
    }
}
