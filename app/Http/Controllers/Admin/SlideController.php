<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableContent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SlideRequest;
use App\Models\Slide;
use App\Services\MediaLibrary;
use App\Support\HomeLayouts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

/**
 * The home page's slider, one panel at a time.
 *
 * Ordinary content in every respect — translated per locale, dragged into
 * order, switched on and off from the row — with one thing worth knowing: it
 * only appears on the site while the slider layout is the one on air, so the
 * listing says so rather than leaving somebody to wonder why their slide is
 * not on the home page.
 */
class SlideController extends Controller
{
    use HandlesTranslatableContent;

    public function __construct(private readonly MediaLibrary $media) {}

    public function index(Request $request): View
    {
        $query = Slide::query()
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('title', 'like', "%{$term}%"))
            ->ordered();

        return view('admin.slides.index', [
            'slides' => $this->paginateContent($query, $request),
            'layoutShowsSlider' => HomeLayouts::current() === 'slider',
        ]);
    }

    public function create(): View
    {
        return view('admin.slides.form', ['slide' => new Slide(['is_active' => true])]);
    }

    public function store(SlideRequest $request): RedirectResponse
    {
        $slide = $this->persist(new Slide, $request);

        return redirect()->route('admin.slides.edit', $slide)->with('status', __('admin.slides.created'));
    }

    public function show(Slide $slide): RedirectResponse
    {
        return redirect()->route('admin.slides.edit', $slide);
    }

    public function edit(Slide $slide): View
    {
        return view('admin.slides.form', ['slide' => $slide]);
    }

    public function update(SlideRequest $request, Slide $slide): RedirectResponse
    {
        $this->persist($slide, $request);

        return back()->with('status', __('admin.slides.updated'));
    }

    public function destroy(Slide $slide): RedirectResponse
    {
        $this->media->delete($slide->untranslated('image'));
        $slide->delete();

        return redirect()->route('admin.slides.index')->with('status', __('admin.slides.deleted'));
    }

    private function persist(Slide $slide, SlideRequest $request): Slide
    {
        $data = $request->validated();
        $data['sort_order'] ??= 0;

        $image = $this->media->replace(
            $request->file('image'),
            'slides',
            $slide->untranslated('image'),
            $request->boolean('image_remove'),
        );

        $this->fillTranslatable($slide, Arr::except($data, ['image', 'image_remove']));
        $slide->image = $image;
        $slide->save();

        return $slide;
    }
}
