<?php

namespace App\Support;

use App\Models\Department;
use App\Models\DiagnosticTest;
use App\Models\Doctor;
use App\Models\GalleryAlbum;
use App\Models\HealthPackage;
use App\Models\Post;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;

/**
 * The panel's listings that can be reordered and switched on or off in place.
 *
 * A whitelist rather than a route parameter resolved to a class name: the two
 * endpoints behind this take an arbitrary string from the browser, and "any
 * model, any column" is how a listing endpoint turns into a way to flip a
 * column on the users table.
 */
class ManagedLists
{
    /**
     * key => [model, sortable]
     *
     * `sortable` is false where the listing has an order of its own that
     * dragging would contradict — articles are ordered by publication date,
     * and a hand-sorted news list is a different feature.
     *
     * @return array<string, array{model: class-string<Model>, sortable: bool}>
     */
    public static function all(): array
    {
        return [
            'departments' => ['model' => Department::class, 'sortable' => true],
            'doctors' => ['model' => Doctor::class, 'sortable' => true],
            'services' => ['model' => Service::class, 'sortable' => true],
            'packages' => ['model' => HealthPackage::class, 'sortable' => true],
            'diagnostics' => ['model' => DiagnosticTest::class, 'sortable' => true],
            'testimonials' => ['model' => Testimonial::class, 'sortable' => true],
            'gallery' => ['model' => GalleryAlbum::class, 'sortable' => true],
            'posts' => ['model' => Post::class, 'sortable' => false],
        ];
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, static::all());
    }

    public static function sortable(string $key): bool
    {
        return static::all()[$key]['sortable'] ?? false;
    }

    /** @return class-string<Model> */
    public static function model(string $key): string
    {
        return static::all()[$key]['model'];
    }

    public static function query(string $key)
    {
        return static::model($key)::query();
    }
}
