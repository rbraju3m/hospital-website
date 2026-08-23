<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableContent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DepartmentRequest;
use App\Models\Department;
use App\Services\MediaLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    use HandlesTranslatableContent;

    public function __construct(private readonly MediaLibrary $media) {}

    public function index(Request $request): View
    {
        $query = Department::withCount('doctors')
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where(
                fn ($inner) => $inner->where('name', 'like', "%{$term}%")->orWhere('slug', 'like', "%{$term}%")
            ))
            ->ordered();

        return view('admin.departments.index', [
            'departments' => $this->paginateContent($query, $request),
        ]);
    }

    public function create(): View
    {
        return view('admin.departments.form', ['department' => new Department(['icon' => 'stethoscope', 'is_active' => true])]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $department = $this->persist(new Department, $request);

        return redirect()->route('admin.departments.edit', $department)
            ->with('status', __('admin.departments.created', ['name' => $department->untranslated('name')]));
    }

    public function show(Department $department): RedirectResponse
    {
        return redirect()->route('admin.departments.edit', $department);
    }

    public function edit(Department $department): View
    {
        return view('admin.departments.form', ['department' => $department]);
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->persist($department, $request);

        return back()->with('status', __('admin.departments.updated', ['name' => $department->untranslated('name')]));
    }

    public function destroy(Department $department): RedirectResponse
    {
        // Doctors cascade with their department, which would silently take
        // their appointments too — make the operator move them first.
        if ($department->doctors()->exists()) {
            return back()->with('warning', __('admin.departments.has_doctors'));
        }

        $this->media->delete($department->untranslated('image'));
        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('status', __('admin.departments.deleted'));
    }

    private function persist(Department $department, DepartmentRequest $request): Department
    {
        $data = $request->validated();

        $data['slug'] = $this->uniqueSlug('departments', $data['slug'] ?? null, $data['name'], $department->id);
        $data['sort_order'] ??= 0;

        $image = $this->media->replace(
            $request->file('image'),
            'departments',
            $department->untranslated('image'),
            $request->boolean('image_remove'),
        );

        $this->fillTranslatable($department, Arr::except($data, ['image', 'image_remove']));
        $department->image = $image;
        $department->save();

        return $department;
    }
}
