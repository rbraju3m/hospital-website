<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\StaffRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::factory()->create();
        $this->actingAs($this->me);
    }

    public function test_a_staff_account_is_created(): void
    {
        $this->post(route('admin.users.store'), [
            'name' => 'Nusrat Jahan',
            'email' => 'nusrat@example.test',
            'password' => 'front-desk-2026',
            'password_confirmation' => 'front-desk-2026',
            'role' => StaffRoles::FRONT_DESK,
        ])->assertSessionHasNoErrors();

        $user = User::firstWhere('email', 'nusrat@example.test');

        $this->assertNotNull($user);
        // Hashed by the model's cast, never stored in the clear.
        $this->assertTrue(Hash::check('front-desk-2026', $user->password));
        $this->assertSame(StaffRoles::FRONT_DESK, $user->role);
    }

    public function test_a_mismatched_confirmation_is_rejected(): void
    {
        $this->post(route('admin.users.store'), [
            'name' => 'Nusrat Jahan',
            'email' => 'nusrat@example.test',
            'password' => 'front-desk-2026',
            'password_confirmation' => 'something-else',
        ])->assertSessionHasErrors('password');

        $this->assertSame(1, User::count());
    }

    public function test_an_empty_password_leaves_the_existing_one_alone(): void
    {
        $user = User::factory()->create(['password' => Hash::make('original-secret')]);

        $this->put(route('admin.users.update', $user), [
            'name' => 'Renamed',
            'email' => $user->email,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Renamed', $user->fresh()->name);
        $this->assertTrue(Hash::check('original-secret', $user->fresh()->password));
    }

    public function test_a_user_cannot_delete_their_own_account(): void
    {
        $this->delete(route('admin.users.destroy', $this->me))->assertSessionHas('warning');

        $this->assertModelExists($this->me);
    }

    public function test_the_last_account_cannot_be_deleted(): void
    {
        // Deleting it would need database access to undo.
        $other = User::factory()->create();
        $this->actingAs($other);
        $this->me->delete();

        $this->delete(route('admin.users.destroy', $other))->assertSessionHas('warning');

        $this->assertSame(1, User::count());
    }

    public function test_another_account_is_deleted(): void
    {
        $other = User::factory()->create();

        $this->delete(route('admin.users.destroy', $other))
            ->assertRedirect(route('admin.users.index'));

        $this->assertModelMissing($other);
    }

    public function test_an_email_address_cannot_be_reused(): void
    {
        $other = User::factory()->create();

        $this->post(route('admin.users.store'), [
            'name' => 'Duplicate',
            'email' => $other->email,
            'password' => 'front-desk-2026',
            'password_confirmation' => 'front-desk-2026',
        ])->assertSessionHasErrors('email');
    }
}
