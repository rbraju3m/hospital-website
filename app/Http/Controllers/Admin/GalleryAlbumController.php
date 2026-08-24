<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableContent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GalleryAlbumRequest;
use App\Models\GalleryAlbum;
use App\Services\MediaLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class GalleryAlbumController extends Controller
{
    use HandlesTranslatableContent;

    public function __construct(private readonly MediaLibrary $media) {}

    public function index(Request $request): View
    {
        $query = GalleryAlbum::withCount('photos')
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where(
                fn ($inner) => $inner->where('title', 'like', "%{$term}%")->orWhere('slug', 'like', "%{$term}%")
            ))
            ->ordered();

        return view('admin.gallery.index', [
            'albums' => $this->paginateContent($query, $request),
        ]);
    }

    public function create(): View
    {
        return view('admin.gallery.form', ['album' => new GalleryAlbum(['is_active' => true])]);
    }

    public function store(GalleryAlbumRequest $request): RedirectResponse
    {
        $album = $this->persist(new GalleryAlbum, $request);

        // Straight to the edit screen: an album with no photographs in it is
        // half a job, and that is where the upload box lives.
        return redirect()->route('admin.gallery.edit', $album)
            ->with('status', __('admin.gallery.created', ['title' => $album->untranslated('title')]));
    }

    public function show(GalleryAlbum $album): RedirectResponse
    {
        return redirect()->route('admin.gallery.edit', $album);
    }

    public function edit(GalleryAlbum $album): View
    {
        return view('admin.gallery.form', [
            'album' => $album,
            'photos' => $album->photos()->get(),
        ]);
    }

    public function update(GalleryAlbumRequest $request, GalleryAlbum $album): RedirectResponse
    {
        $this->persist($album, $request);

        return back()->with('status', __('admin.gallery.updated', ['title' => $album->untranslated('title')]));
    }

    public function destroy(GalleryAlbum $album): RedirectResponse
    {
        // The rows cascade with the album; the files do not, and an orphaned
        // upload is invisible from the panel forever after.
        foreach ($album->photos()->get() as $photo) {
            $this->media->delete($photo->untranslated('path'));
        }

        $this->media->delete($album->untranslated('image'));
        $album->delete();

        return redirect()->route('admin.gallery.index')
            ->with('status', __('admin.gallery.deleted'));
    }

    private function persist(GalleryAlbum $album, GalleryAlbumRequest $request): GalleryAlbum
    {
        $data = $request->validated();

        $data['slug'] = $this->uniqueSlug('gallery_albums', $data['slug'] ?? null, $data['title'], $album->id);
        $data['sort_order'] ??= 0;

        $image = $this->media->replace(
            $request->file('image'),
            'gallery',
            $album->untranslated('image'),
            $request->boolean('image_remove'),
        );

        $this->fillTranslatable($album, Arr::except($data, ['image', 'image_remove']));
        $album->image = $image;
        $album->save();

        return $album;
    }
}
