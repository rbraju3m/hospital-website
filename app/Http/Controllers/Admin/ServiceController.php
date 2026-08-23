<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableContent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use App\Services\MediaLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class ServiceController extends Controller
{
    use HandlesTranslatableContent;

    public function __construct(private readonly MediaLibrary $media) {}

    public function index(Request $request): View
    {
        $query = Service::query()
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->ordered();

        return view('admin.services.index', ['services' => $this->paginateContent($query, $request)]);
    }

    public function create(): View
    {
        return view('admin.services.form', [
            'service' => new Service(['icon' => 'activity', 'category' => 'clinical', 'is_active' => true]),
        ]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $service = $this->persist(new Service, $request);

        return redirect()->route('admin.services.edit', $service)
            ->with('status', __('admin.services.created', ['name' => $service->untranslated('name')]));
    }

    public function show(Service $service): RedirectResponse
    {
        return redirect()->route('admin.services.edit', $service);
    }

    public function edit(Service $service): View
    {
        return view('admin.services.form', ['service' => $service]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $this->persist($service, $request);

        return back()->with('status', __('admin.services.updated', ['name' => $service->untranslated('name')]));
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->media->delete($service->untranslated('image'));
        $service->delete();

        return redirect()->route('admin.services.index')->with('status', __('admin.services.deleted'));
    }

    private function persist(Service $service, ServiceRequest $request): Service
    {
        $data = $request->validated();

        $data['slug'] = $this->uniqueSlug('services', $data['slug'] ?? null, $data['name'], $service->id);
        $data['sort_order'] ??= 0;

        $image = $this->media->replace(
            $request->file('image'),
            'services',
            $service->untranslated('image'),
            $request->boolean('image_remove'),
        );

        $this->fillTranslatable($service, Arr::except($data, ['image', 'image_remove']));
        $service->image = $image;
        $service->save();

        return $service;
    }
}
