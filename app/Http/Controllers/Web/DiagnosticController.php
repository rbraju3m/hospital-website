<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDiagnosticRequestRequest;
use App\Models\ContactMessage;
use App\Models\DiagnosticTest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiagnosticController extends Controller
{
    /** Category slugs, in the order the price list presents them. */
    public const CATEGORIES = ['pathology', 'imaging', 'cardiology', 'endoscopy'];

    public function index(Request $request): View
    {
        $term = $request->string('q')->trim()->value();
        $category = $request->string('category')->trim()->value();

        $tests = DiagnosticTest::active()
            ->search($term)
            ->when(in_array($category, self::CATEGORIES, true), fn ($query) => $query->where('category', $category))
            ->orderByRaw('FIELD(category, ?, ?, ?, ?)', self::CATEGORIES)
            ->ordered()
            ->paginate(30)
            ->withQueryString();

        return view('pages.diagnostics.index', [
            'tests' => $tests,
            'term' => $term,
            'category' => $category,
            // Counts per category, ignoring the category filter itself so the
            // chips do not all read zero once one is chosen.
            'counts' => DiagnosticTest::active()
                ->search($term)
                ->selectRaw('category, count(*) as total')
                ->groupBy('category')
                ->pluck('total', 'category'),
        ]);
    }

    public function show(DiagnosticTest $diagnosticTest): View
    {
        abort_unless($diagnosticTest->is_active, 404);

        return view('pages.diagnostics.show', [
            'test' => $diagnosticTest,
            'related' => DiagnosticTest::active()
                ->where('category', $diagnosticTest->category)
                ->whereKeyNot($diagnosticTest->getKey())
                ->ordered()
                ->take(6)
                ->get(),
        ]);
    }

    /**
     * A call-back request, written into the same inbox as the contact form.
     *
     * The test is recorded in the subject and body rather than as a relation:
     * this is an enquiry, not a booking, and the desk needs to read it, not
     * query it.
     */
    public function store(StoreDiagnosticRequestRequest $request, DiagnosticTest $diagnosticTest): RedirectResponse
    {
        abort_unless($diagnosticTest->is_active, 404);

        $validated = $request->validated();

        // Written in the site's default locale, not the visitor's: this is
        // staff-facing, and the desk alert for appointments made the same
        // choice. The test name stays the base column for the same reason —
        // it is what the counter and the report call it.
        $staffLocale = config('app.fallback_locale');

        ContactMessage::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'subject' => __('diagnostics.request.subject', [
                'test' => $diagnosticTest->untranslated('name'),
            ], $staffLocale),
            'message' => trim(__('diagnostics.request.body', [
                'test' => $diagnosticTest->untranslated('name'),
                'code' => $diagnosticTest->code ?: '—',
                'price' => '৳'.number_format($diagnosticTest->effectivePrice()),
            ], $staffLocale)."\n\n".($validated['notes'] ?? '')),
        ]);

        return back()->with('status', __('diagnostics.request.success', ['phone' => $validated['phone']]));
    }
}
