<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PatientDocumentRequest;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Sms\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Publishing reports, prescriptions and bills to a patient.
 *
 * Files go to the **private** disk, never the public one — these are medical
 * records, and a guessable URL to somebody's biopsy result is not a mistake
 * that can be walked back. Both the portal and this panel stream them through
 * a controller that checks who is asking.
 */
class PatientDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $term = $request->string('q')->trim()->value();

        $documents = PatientDocument::with('uploader')
            ->when($term, function ($query) use ($term) {
                $like = '%'.str_replace('%', '\%', $term).'%';

                return $query->where(fn ($inner) => $inner
                    ->where('title', 'like', $like)
                    // Numbers are stored normalised, so search the same way.
                    ->orWhere('phone', 'like', '%'.PhoneNumber::national($term).'%'));
            })
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->latestFirst()
            ->paginate(25)
            ->withQueryString();

        return view('admin.documents.index', [
            'documents' => $documents,
            // Whether the patient has registered yet is the question staff ask
            // most: "have they actually seen it?"
            'registered' => Patient::whereIn('phone', $documents->pluck('phone'))->pluck('name', 'phone'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.documents.form', [
            'document' => new PatientDocument([
                'category' => 'report',
                'phone' => $request->query('phone'),
            ]),
        ]);
    }

    public function store(PatientDocumentRequest $request): RedirectResponse
    {
        $document = new PatientDocument;

        $this->persist($document, $request);

        return redirect()->route('admin.documents.index')
            ->with('status', __('admin.documents.created', ['phone' => $document->phone]));
    }

    public function edit(PatientDocument $document): View
    {
        return view('admin.documents.form', ['document' => $document]);
    }

    public function update(PatientDocumentRequest $request, PatientDocument $document): RedirectResponse
    {
        $this->persist($document, $request);

        return back()->with('status', __('admin.documents.updated'));
    }

    public function destroy(PatientDocument $document): RedirectResponse
    {
        Storage::disk('local')->delete($document->path);
        $document->delete();

        return redirect()->route('admin.documents.index')->with('status', __('admin.documents.deleted'));
    }

    /** Staff need to check they published the right file. */
    public function download(PatientDocument $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    private function persist(PatientDocument $document, PatientDocumentRequest $request): void
    {
        $data = $request->safe()->only(['phone', 'title', 'category', 'issued_at', 'notes']);

        $document->fill($data);

        if ($file = $request->file('file')) {
            // The replaced file goes with it; an orphan on the private disk is
            // still somebody's medical record sitting on a server.
            if ($document->path) {
                Storage::disk('local')->delete($document->path);
            }

            $document->fill($this->storeFile($file, $document->phone));
        }

        $document->uploaded_by ??= auth()->id();
        $document->save();
    }

    /** @return array<string, mixed> */
    private function storeFile(UploadedFile $file, string $nationalPhone): array
    {
        return [
            // Foldered by patient, named unguessably — the filename is never
            // the security boundary, but it should not be a hint either.
            'path' => $file->storeAs(
                "patient-documents/{$nationalPhone}",
                Str::lower(Str::random(24)).'.'.$file->extension(),
                'local'
            ),
            'original_name' => Str::limit($file->getClientOriginalName(), 120, ''),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
    }
}
