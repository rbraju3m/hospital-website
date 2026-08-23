<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\HealthPackage;
use App\Models\Post;
use App\Models\Service;
use App\Models\Testimonial;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('pages.home', [
            'centres' => Department::active()->centresOfExcellence()->ordered()->take(8)->get(),
            'doctors' => Doctor::active()->featured()->with('department')->ordered()->take(8)->get(),
            'services' => Service::active()->where('is_featured', true)->ordered()->take(6)->get(),
            'packages' => HealthPackage::active()->where('is_featured', true)->ordered()->take(3)->get(),
            'testimonials' => Testimonial::active()->ordered()->take(6)->get(),
            'posts' => Post::published()->latestFirst()->take(3)->get(),
            'departmentOptions' => Department::active()->ordered()->get(['id', 'name', 'slug']),
        ]);
    }
}
