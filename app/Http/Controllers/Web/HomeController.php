<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\GalleryPhoto;
use App\Models\HealthPackage;
use App\Models\Post;
use App\Models\Service;
use App\Models\Slide;
use App\Models\Testimonial;
use App\Support\HomeLayouts;

class HomeController extends Controller
{
    public function __invoke()
    {
        /* Which layout, decided in the panel. HomeLayouts falls back to
           `classic` for anything it does not recognise: this is the one page
           on the site that must always render. */
        return view(HomeLayouts::view(), [
            // Only the slider layout draws these, but the query is one row per
            // slide and passing them unconditionally keeps the layouts
            // interchangeable — a template is a template, not a controller.
            'slides' => Slide::active()->ordered()->get(),
            'centres' => Department::active()->centresOfExcellence()->ordered()->take(8)->get(),
            'doctors' => Doctor::active()->featured()->with('department')->ordered()->take(8)->get(),
            'services' => Service::active()->where('is_featured', true)->ordered()->take(6)->get(),
            'packages' => HealthPackage::active()->where('is_featured', true)->ordered()->take(3)->get(),
            'testimonials' => Testimonial::active()->ordered()->take(6)->get(),
            'posts' => Post::published()->latestFirst()->take(3)->get(),
            'galleryPhotos' => GalleryPhoto::recent(8),
            'departmentOptions' => Department::active()->ordered()->get(),
            // Drives the "Health checks from ৳x" tile, so the figure tracks the data.
            'cheapestPackage' => (int) HealthPackage::active()
                ->selectRaw('MIN(COALESCE(discount_price, price)) as low')
                ->value('low'),
        ]);
    }
}
