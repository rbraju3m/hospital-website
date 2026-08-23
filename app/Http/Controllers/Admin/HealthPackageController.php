<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableContent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HealthPackageRequest;
use App\Models\HealthPackage;
use App\Services\MediaLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class HealthPackageController extends Controller
{
    use HandlesTranslatableContent;

    public function __construct(private readonly MediaLibrary $media) {}

    public function index(Request $request): View
    {
        $query = HealthPackage::query()
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->ordered();

        return view('admin.packages.index', ['packages' => $this->paginateContent($query, $request)]);
    }

    public function create(): View
    {
        return view('admin.packages.form', [
            'package' => new HealthPackage(['category' => 'basic', 'is_active' => true, 'price' => 0]),
        ]);
    }

    public function store(HealthPackageRequest $request): RedirectResponse
    {
        $package = $this->persist(new HealthPackage, $request);

        return redirect()->route('admin.packages.edit', $package)
            ->with('status', __('admin.packages.created', ['name' => $package->untranslated('name')]));
    }

    public function show(HealthPackage $healthPackage): RedirectResponse
    {
        return redirect()->route('admin.packages.edit', $healthPackage);
    }

    public function edit(HealthPackage $healthPackage): View
    {
        return view('admin.packages.form', ['package' => $healthPackage]);
    }

    public function update(HealthPackageRequest $request, HealthPackage $healthPackage): RedirectResponse
    {
        $this->persist($healthPackage, $request);

        return back()->with('status', __('admin.packages.updated', ['name' => $healthPackage->untranslated('name')]));
    }

    public function destroy(HealthPackage $healthPackage): RedirectResponse
    {
        $this->media->delete($healthPackage->untranslated('image'));
        $healthPackage->delete();

        return redirect()->route('admin.packages.index')->with('status', __('admin.packages.deleted'));
    }

    private function persist(HealthPackage $package, HealthPackageRequest $request): HealthPackage
    {
        $data = $request->validated();

        $data['slug'] = $this->uniqueSlug('health_packages', $data['slug'] ?? null, $data['name'], $package->id);
        $data['sort_order'] ??= 0;

        $image = $this->media->replace(
            $request->file('image'),
            'packages',
            $package->untranslated('image'),
            $request->boolean('image_remove'),
        );

        $this->fillTranslatable($package, Arr::except($data, ['image', 'image_remove']));
        $package->image = $image;
        $package->save();

        return $package;
    }
}
