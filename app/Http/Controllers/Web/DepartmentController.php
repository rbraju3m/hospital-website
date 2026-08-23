<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function index()
    {
        return view('pages.departments.index', [
            'centres' => Department::active()->withCount(['doctors' => fn ($q) => $q->where('is_active', true)])
                ->centresOfExcellence()->ordered()->get(),
            'departments' => Department::active()->withCount(['doctors' => fn ($q) => $q->where('is_active', true)])
                ->where('is_centre_of_excellence', false)->ordered()->get(),
        ]);
    }

    public function show(Department $department)
    {
        abort_unless($department->is_active, 404);

        $department->load(['doctors' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('name')]);

        return view('pages.departments.show', [
            'department' => $department,
            'related' => Department::active()
                ->whereKeyNot($department->id)
                ->ordered()
                ->take(6)
                ->get(),
        ]);
    }
}
