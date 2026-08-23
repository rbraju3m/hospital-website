<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PatientDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reports, prescriptions and bills.
 *
 * Files live on the private disk and are streamed by this controller after an
 * ownership check. They are never reachable through the public storage
 * symlink: a guessable URL to somebody's biopsy result is not a mistake that
 * can be walked back.
 */
class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $patient = auth('patient')->user();
        $category = $request->string('category')->trim()->value();

        return view('portal.documents', [
            'documents' => $patient->documents()
                ->when(in_array($category, PatientDocument::CATEGORIES, true),
                    fn ($query) => $query->where('category', $category))
                ->latestFirst()
                ->paginate(25)
                ->withQueryString(),
            'category' => $category,
            'counts' => $patient->documents()
                ->selectRaw('category, count(*) as total')
                ->groupBy('category')
                ->pluck('total', 'category'),
        ]);
    }

    public function download(PatientDocument $document): StreamedResponse
    {
        $patient = auth('patient')->user();

        // The document is addressed to a mobile number, and this session
        // belongs to one. They have to be the same number.
        abort_unless($document->phone === $patient->phone, 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        $document->forceFill(['downloaded_at' => now()])->save();

        return Storage::disk('local')->download($document->path, $document->original_name);
    }
}
