<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Service;

class ServiceController extends Controller
{
    public function index()
    {
        return view('pages.services.index', [
            'grouped' => Service::active()->ordered()->get()->groupBy('category'),
        ]);
    }

    public function show(Service $service)
    {
        abort_unless($service->is_active, 404);

        return view('pages.services.show', [
            'service' => $service,
            'related' => Service::active()
                ->where('category', $service->category)
                ->whereKeyNot($service->id)
                ->ordered()
                ->take(4)
                ->get(),
        ]);
    }
}
