<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableContent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GalleryPhotoRequest;
use App\Http\Requests\Admin\GalleryPhotoUploadRequest;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Services\MediaLibrary;
use Illuminate\Http\RedirectResponse;

/**
 * The pictures inside an album, managed from the album's own page.
 *
 * Each row is its own little form for the same reason chamber hours are: HTML
 * does not allow one form inside another, and the album's fields are already a
 * form by the time the photographs are rendered.
 */
class GalleryPhotoController extends Controller
{
    use HandlesTranslatableContent;

    public function __construct(private readonly MediaLibrary $media) {}

    public function store(GalleryPhotoUploadRequest $request, GalleryAlbum $album): RedirectResponse
    {
        // Uploads continue the existing order rather than restarting at zero,
        // so a second batch lands after the first instead of interleaving.
        $order = (int) $album->photos()->max('sort_order');
        $files = $request->file('photos');

        foreach ($files as $file) {
            $album->photos()->create([
                'path' => $this->media->store($file, 'gallery'),
                'sort_order' => ++$order,
            ]);
        }

        return back()->with('status', trans_choice('admin.gallery.photos_added', count($files), [
            'count' => count($files),
        ]));
    }

    public function update(GalleryPhotoRequest $request, GalleryAlbum $album, GalleryPhoto $photo): RedirectResponse
    {
        abort_unless($photo->gallery_album_id === $album->id, 404);

        $data = $request->validated();
        $data['sort_order'] ??= 0;

        $this->fillTranslatable($photo, $data)->save();

        return back()->with('status', __('admin.gallery.photo_updated'));
    }

    public function destroy(GalleryAlbum $album, GalleryPhoto $photo): RedirectResponse
    {
        abort_unless($photo->gallery_album_id === $album->id, 404);

        $this->media->delete($photo->untranslated('path'));
        $photo->delete();

        return back()->with('status', __('admin.gallery.photo_removed'));
    }
}
