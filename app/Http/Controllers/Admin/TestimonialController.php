<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableContent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestimonialRequest;
use App\Models\Testimonial;
use App\Services\MediaLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    use HandlesTranslatableContent;

    public function __construct(private readonly MediaLibrary $media) {}

    public function index(Request $request): View
    {
        $query = Testimonial::query()
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('patient_name', 'like', "%{$term}%"))
            ->ordered();

        return view('admin.testimonials.index', ['testimonials' => $this->paginateContent($query, $request)]);
    }

    public function create(): View
    {
        return view('admin.testimonials.form', ['testimonial' => new Testimonial(['rating' => 5, 'is_active' => true])]);
    }

    public function store(TestimonialRequest $request): RedirectResponse
    {
        $testimonial = $this->persist(new Testimonial, $request);

        return redirect()->route('admin.testimonials.edit', $testimonial)
            ->with('status', __('admin.testimonials.created'));
    }

    public function show(Testimonial $testimonial): RedirectResponse
    {
        return redirect()->route('admin.testimonials.edit', $testimonial);
    }

    public function edit(Testimonial $testimonial): View
    {
        return view('admin.testimonials.form', ['testimonial' => $testimonial]);
    }

    public function update(TestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $this->persist($testimonial, $request);

        return back()->with('status', __('admin.testimonials.updated'));
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $this->media->delete($testimonial->untranslated('photo'));
        $testimonial->delete();

        return redirect()->route('admin.testimonials.index')->with('status', __('admin.testimonials.deleted'));
    }

    private function persist(Testimonial $testimonial, TestimonialRequest $request): Testimonial
    {
        $data = $request->validated();
        $data['sort_order'] ??= 0;

        $photo = $this->media->replace(
            $request->file('photo'),
            'testimonials',
            $testimonial->untranslated('photo'),
            $request->boolean('photo_remove'),
        );

        $this->fillTranslatable($testimonial, Arr::except($data, ['photo', 'photo_remove']));
        $testimonial->photo = $photo;
        $testimonial->save();

        return $testimonial;
    }
}
