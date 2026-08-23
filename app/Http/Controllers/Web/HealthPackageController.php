<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\HealthPackage;
use Illuminate\Http\Request;

class HealthPackageController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category');

        return view('pages.packages.index', [
            'packages' => HealthPackage::active()
                ->when($category, fn ($q) => $q->where('category', $category))
                ->ordered()
                ->get(),
            'categories' => HealthPackage::active()->distinct()->orderBy('category')->pluck('category'),
            'category' => $category,
        ]);
    }

    public function show(HealthPackage $healthPackage)
    {
        abort_unless($healthPackage->is_active, 404);

        return view('pages.packages.show', [
            'package' => $healthPackage,
            'related' => HealthPackage::active()
                ->whereKeyNot($healthPackage->id)
                ->ordered()
                ->take(3)
                ->get(),
        ]);
    }
}
