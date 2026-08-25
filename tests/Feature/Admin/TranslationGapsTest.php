<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Post;
use App\Models\User;
use App\Support\PanelNavigation;
use App\Support\TranslationGaps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The count of content nobody has translated yet, which the panel's menu
 * carries as a badge. The expensive half is the cache: it is counted in PHP
 * over whole tables, so what matters is that it is never recounted when it
 * did not have to be, and always recounted when it did.
 */
class TranslationGapsTest extends TestCase
{
    use RefreshDatabase;

    private function department(array $attributes = []): Department
    {
        return Department::create(array_merge([
            'name' => 'Cardiology',
            'slug' => 'cardiology-'.Str::random(6),
            'icon' => 'heart-pulse',
        ], $attributes));
    }

    public function test_content_with_no_translation_is_counted(): void
    {
        $this->assertSame([], TranslationGaps::counts());

        $this->department();

        $this->assertSame(['departments' => 1], TranslationGaps::counts());
    }

    public function test_translated_content_is_not_counted(): void
    {
        $this->department([
            'translations' => ['bn' => ['name' => 'কার্ডিওলজি']],
        ]);

        $this->assertArrayNotHasKey('departments', TranslationGaps::counts());
    }

    public function test_a_field_blank_in_the_source_is_not_a_gap(): void
    {
        // `summary` is translatable and empty here. There is nothing to
        // translate, so its absence in Bangla is not a gap — the same rule the
        // ?untranslated= filter follows, and the reason this cannot be SQL.
        $this->department([
            'summary' => null,
            'translations' => ['bn' => ['name' => 'কার্ডিওলজি']],
        ]);

        $this->assertArrayNotHasKey('departments', TranslationGaps::counts());
    }

    public function test_the_count_is_recounted_after_content_changes(): void
    {
        $department = $this->department();

        $this->assertSame(['departments' => 1], TranslationGaps::counts());

        // No manual flush anywhere: saving is what drops the count.
        $department->setTranslations('bn', ['name' => 'কার্ডিওলজি'])->save();

        $this->assertSame([], TranslationGaps::counts());

        $department->delete();

        $this->assertSame([], TranslationGaps::counts());
    }

    public function test_saving_one_section_leaves_the_others_counted(): void
    {
        $this->department();
        Post::create(['title' => 'Five signs', 'slug' => 'five-signs', 'body' => 'Body']);

        $this->assertSame(['departments' => 1, 'posts' => 1], TranslationGaps::counts());

        $this->department(['name' => 'Neurology']);

        // Departments has to be recounted; the articles have not moved, and a
        // single cache key would have charged the next page load for both.
        $this->assertNull(Cache::get('translation.gaps.departments'));
        $this->assertSame(1, Cache::get('translation.gaps.posts'));
    }

    public function test_every_counted_section_is_a_section_of_the_menu(): void
    {
        $keys = array_column(array_merge(...array_column(PanelNavigation::registry(), 'items')), 'key');

        foreach (TranslationGaps::sections() as $section) {
            $this->assertContains($section, $keys, "{$section} is counted but is not in the menu.");
        }
    }

    public function test_a_row_never_carries_both_kinds_of_badge(): void
    {
        // Work waiting on the desk and a sentence nobody has written yet are
        // different news in different colours, and collapsed to the rail there
        // is only room for one dot — so the sidebar renders the second with an
        // `elseif`. That is only safe while no item declares both.
        $items = array_merge(...array_column(PanelNavigation::registry(), 'items'));

        foreach ($items as $item) {
            if (! isset($item['badge'])) {
                continue;
            }

            $this->assertNotContains($item['key'], TranslationGaps::sections());
        }
    }

    public function test_the_menu_badges_what_is_waiting_on_a_translator(): void
    {
        $this->department();

        $html = $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->getContent();

        $this->assertStringContainsString(trans_choice('admin.translation.gap', 1), $html);
        $this->assertStringContainsString('bg-amber-400', $html);
    }
}
