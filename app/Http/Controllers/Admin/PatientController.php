<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Sms\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Who has portal access.
 *
 * Read-only apart from switching an account off. Staff do not create these —
 * patients register themselves — and nobody here should be able to change the
 * mobile number an account is matched on, because that would silently move
 * somebody else's records under it.
 */
class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $term = $request->string('q')->trim()->value();

        return view('admin.patients.index', [
            'patients' => Patient::query()
                ->when($term, function ($query) use ($term) {
                    $like = '%'.str_replace('%', '\%', $term).'%';

                    return $query->where(fn ($inner) => $inner
                        ->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', '%'.PhoneNumber::national($term).'%'));
                })
                ->withCount(['documents'])
                ->orderByDesc('id')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function toggle(Patient $patient): RedirectResponse
    {
        $patient->update(['is_active' => ! $patient->is_active]);

        return back()->with('status', $patient->is_active
            ? __('admin.patients.enabled', ['name' => $patient->name])
            : __('admin.patients.disabled', ['name' => $patient->name]));
    }

    /** Documents are addressed to a number, so this is a lookup, not a relation. */
    public function documents(Patient $patient): View
    {
        return view('admin.patients.documents', [
            'patient' => $patient,
            'documents' => PatientDocument::forPhone($patient->phone)->latestFirst()->get(),
        ]);
    }
}
