<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\GalleryPhoto;
use App\Models\Service;

class PageController extends Controller
{
    public function about()
    {
        return view('pages.about', [
            'departmentCount' => Department::active()->count(),
            'doctorCount' => Doctor::active()->count(),
            'galleryPhotos' => GalleryPhoto::recent(6),
        ]);
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function emergency()
    {
        return view('pages.emergency', [
            'services' => Service::active()->where('is_247', true)->ordered()->get(),
        ]);
    }

    public function international()
    {
        return view('pages.international');
    }
}
