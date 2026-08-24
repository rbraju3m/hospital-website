<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableContent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GalleryPhotoRequest;
use App\Http\Requests\Admin\GalleryPhotoUploadRequest;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Services\MediaLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The pictures inside an album.
 *
 * Every action here is one small write that answers JSON, because the screen
 * they drive is a media manager rather than a form: files land as they are
 * dropped, a caption saves as it is typed, an order saves as it is dragged.
 * There is no Save button to forget to press, and no page reload between
 * choosing twenty photographs and seeing them.
 *
 * **One file per request** is the other reason. A batch large enough to pass
 * `post_max_size` arrives with its body discarded — CSRF token and all — and
 * reads as an expired page; uploading one at a time makes that impossible and
 * buys a per-picture progress bar for free.
 */
class GalleryPhotoController extends Controller
{
    use HandlesTranslatableContent;

    public function __construct(private readonly MediaLibrary $media) {}

    public function store(GalleryPhotoUploadRequest $request, GalleryAlbum $album): JsonResponse|RedirectResponse
    {
        // Uploads continue the existing order rather than restarting at zero,
        // so a second batch lands after the first instead of interleaving.
        $order = (int) $album->photos()->max('sort_order');
        $created = collect();

        foreach ($request->file('photos') as $file) {
            $created->push($album->photos()->create([
                'path' => $this->media->store($file, 'gallery'),
                'sort_order' => ++$order,
            ]));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'photos' => $created->map(fn (GalleryPhoto $photo) => $this->present($photo, $album)),
            ], 201);
        }

        return back()->with('status', trans_choice('admin.gallery.photos_added', $created->count(), [
            'count' => $created->count(),
        ]));
    }

    public function update(GalleryPhotoRequest $request, GalleryAlbum $album, GalleryPhoto $photo): JsonResponse
    {
        $this->belongsTo($photo, $album);

        $this->fillTranslatable($photo, $request->validated())->save();

        return response()->json(['photo' => $this->present($photo, $album)]);
    }

    public function order(Request $request, GalleryAlbum $album): JsonResponse
    {
        $ids = collect($request->validate([
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer'],
        ])['ids'])->map(fn ($id) => (int) $id);

        // Scoped to the album, so an id from somewhere else is simply not in
        // the set rather than something to guard against separately.
        $photos = $album->photos()->get()->keyBy('id');
        $position = 0;

        foreach ($ids as $id) {
            if ($photos->has($id)) {
                $album->photos()->whereKey($id)->update(['sort_order' => ++$position]);
            }
        }

        return response()->json(['ordered' => $position]);
    }

    /**
     * Promote one photograph to the album's cover.
     *
     * Only an uploaded file can be a cover: a stand-in has no path to copy, and
     * writing one would freeze today's placeholder into the row for good.
     */
    public function cover(GalleryAlbum $album, GalleryPhoto $photo): JsonResponse
    {
        $this->belongsTo($photo, $album);

        $path = $photo->untranslated('path');

        abort_if(blank($path), 422);

        // Point the column at this photograph and nothing more. Deleting the
        // cover the album had before looks tidy and is not: it is a file
        // somebody deliberately uploaded, and one click on a star would destroy
        // it with no way back. Replacing or clearing a cover is what the image
        // field on the album's own form is for, and that does delete.
        $album->forceFill(['image' => $path])->save();

        return response()->json(['cover' => $photo->id]);
    }

    public function destroy(GalleryAlbum $album, GalleryPhoto $photo): JsonResponse
    {
        $this->belongsTo($photo, $album);

        $path = $photo->untranslated('path');

        // The cover holds a copy of a photograph's path, so deleting that
        // photograph would otherwise leave the album pointing at a file that is
        // no longer there.
        if ($path && $album->untranslated('image') === $path) {
            $album->forceFill(['image' => null])->save();
        }

        $this->media->delete($path);
        $photo->delete();

        return response()->json(['deleted' => $photo->id]);
    }

    private function belongsTo(GalleryPhoto $photo, GalleryAlbum $album): void
    {
        abort_unless($photo->gallery_album_id === $album->id, 404);
    }

    /** The shape the screen renders a photograph from. */
    private function present(GalleryPhoto $photo, GalleryAlbum $album): array
    {
        $path = $photo->untranslated('path');

        return [
            'id' => $photo->id,
            'url' => $photo->url(),
            'uploaded' => filled($path),
            'is_cover' => filled($path) && $album->untranslated('image') === $path,
            'captions' => collect(config('app.available_locales', []))
                ->mapWithKeys(fn ($meta, $code) => [
                    $code => $code === config('app.fallback_locale')
                        ? $photo->untranslated('caption')
                        : $photo->translation('caption', $code),
                ])->all(),
        ];
    }
}
