<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GalleryAlbum;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function index(): View
    {
        return view('pages.gallery.index', [
            'albums' => GalleryAlbum::active()->withCount('photos')->ordered()->paginate(12),
        ]);
    }

    public function show(GalleryAlbum $album): View
    {
        abort_unless($album->is_active, 404);

        return view('pages.gallery.show', [
            'album' => $album,
            // Whole rows: a partial select drops the translations column and the
            // captions all quietly fall back to English.
            'photos' => $album->photos()->get(),
            'more' => GalleryAlbum::active()
                ->withCount('photos')
                ->whereKeyNot($album->id)
                ->ordered()
                ->take(3)
                ->get(),
        ]);
    }
}
