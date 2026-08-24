<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\GalleryAlbum;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dragging a row into place and switching a record on or off, from the listing.
 */
class AdminListControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function departments(int $count = 3): array
    {
        return collect(range(1, $count))->map(fn ($i) => Department::create([
            'name' => "Department {$i}", 'slug' => "department-{$i}", 'icon' => 'stethoscope', 'sort_order' => $i,
        ]))->all();
    }

    public function test_a_guest_cannot_reorder_or_toggle_anything(): void
    {
        auth()->logout();
        [$first] = $this->departments(1);

        $this->postJson(route('admin.lists.order', 'departments'), ['ids' => [$first->id]])->assertUnauthorized();
        $this->patchJson(route('admin.lists.toggle', ['departments', $first->id]))->assertUnauthorized();
    }

    public function test_dragging_a_row_rewrites_the_order(): void
    {
        [$a, $b, $c] = $this->departments();

        $this->postJson(route('admin.lists.order', 'departments'), ['ids' => [$c->id, $a->id, $b->id]])
            ->assertOk()
            ->assertJson(['ordered' => 3]);

        $this->assertSame(
            [$c->id, $a->id, $b->id],
            Department::ordered()->pluck('id')->all(),
        );
    }

    public function test_the_order_starts_from_where_the_block_already_sat(): void
    {
        // Page two of a listing must not renumber itself into page one.
        $a = Department::create(['name' => 'A', 'slug' => 'a', 'icon' => 'stethoscope', 'sort_order' => 40]);
        $b = Department::create(['name' => 'B', 'slug' => 'b', 'icon' => 'stethoscope', 'sort_order' => 41]);

        $this->postJson(route('admin.lists.order', 'departments'), ['ids' => [$b->id, $a->id]])->assertOk();

        $this->assertSame(40, $b->fresh()->sort_order);
        $this->assertSame(41, $a->fresh()->sort_order);
    }

    public function test_the_switch_flips_the_record_and_reports_the_new_state(): void
    {
        [$department] = $this->departments(1);
        // The column defaults in the database, so read it back rather than
        // trusting the instance that was just built.
        $this->assertTrue($department->fresh()->is_active);

        $this->patchJson(route('admin.lists.toggle', ['departments', $department->id]))
            ->assertOk()
            ->assertJson(['active' => false, 'label' => __('admin.states.hidden')]);

        $this->assertFalse($department->fresh()->is_active);

        $this->patchJson(route('admin.lists.toggle', ['departments', $department->id]))
            ->assertJson(['active' => true]);
    }

    public function test_every_declared_list_answers(): void
    {
        $album = GalleryAlbum::create(['title' => 'Theatres', 'slug' => 'theatres']);
        $post = Post::create(['title' => 'A post', 'slug' => 'a-post', 'category' => 'news']);

        $this->patchJson(route('admin.lists.toggle', ['gallery', $album->id]))->assertOk();
        $this->patchJson(route('admin.lists.toggle', ['posts', $post->id]))->assertOk();

        // Articles are ordered by publication date; dragging them would be a
        // second, contradictory answer to "what comes first".
        $this->postJson(route('admin.lists.order', 'posts'), ['ids' => [$post->id]])->assertNotFound();
        $this->postJson(route('admin.lists.order', 'gallery'), ['ids' => [$album->id]])->assertOk();
    }

    public function test_an_unlisted_model_cannot_be_reached(): void
    {
        // The list name comes from the browser: "any model, any column" is how
        // this endpoint would become a way to flip a row on the users table.
        $this->postJson(route('admin.lists.order', 'users'), ['ids' => [1]])->assertNotFound();
        $this->patchJson(route('admin.lists.toggle', ['users', auth()->id()]))->assertNotFound();
        $this->patchJson(route('admin.lists.toggle', ['patients', 1]))->assertNotFound();
    }

    public function test_ids_from_another_list_are_ignored_rather_than_written(): void
    {
        [$a, $b] = $this->departments(2);
        $album = GalleryAlbum::create(['title' => 'Theatres', 'slug' => 'theatres', 'sort_order' => 9]);

        $this->postJson(route('admin.lists.order', 'departments'), ['ids' => [$b->id, $album->id, $a->id]])
            ->assertOk()
            ->assertJson(['ordered' => 2]);

        $this->assertSame(9, $album->fresh()->sort_order);
    }

    public function test_the_listings_render_their_handles_and_switches(): void
    {
        $this->departments(1);

        $this->get(route('admin.departments.index'))
            ->assertOk()
            ->assertSee('adminList({ list: \'departments\'', escape: false)
            ->assertSee('@dragstart="dragStart($event)"', escape: false)
            ->assertSee('arm($event)', escape: false)
            ->assertSee('switch-track', escape: false);
    }
}
