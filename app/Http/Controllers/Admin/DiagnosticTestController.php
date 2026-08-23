<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableContent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DiagnosticTestRequest;
use App\Models\DiagnosticTest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiagnosticTestController extends Controller
{
    use HandlesTranslatableContent;

    public function index(Request $request): View
    {
        $query = DiagnosticTest::query()
            ->search($request->string('q')->trim()->value())
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->ordered();

        return view('admin.diagnostics.index', ['tests' => $this->paginateContent($query, $request)]);
    }

    public function create(): View
    {
        return view('admin.diagnostics.form', [
            'test' => new DiagnosticTest(['category' => 'pathology', 'is_active' => true, 'price' => 0]),
        ]);
    }

    public function store(DiagnosticTestRequest $request): RedirectResponse
    {
        $test = $this->persist(new DiagnosticTest, $request);

        return redirect()->route('admin.diagnostics.edit', $test)
            ->with('status', __('admin.diagnostics.created', ['name' => $test->untranslated('name')]));
    }

    public function show(DiagnosticTest $diagnostic): RedirectResponse
    {
        return redirect()->route('admin.diagnostics.edit', $diagnostic);
    }

    public function edit(DiagnosticTest $diagnostic): View
    {
        return view('admin.diagnostics.form', ['test' => $diagnostic]);
    }

    public function update(DiagnosticTestRequest $request, DiagnosticTest $diagnostic): RedirectResponse
    {
        $this->persist($diagnostic, $request);

        return back()->with('status', __('admin.diagnostics.updated', ['name' => $diagnostic->untranslated('name')]));
    }

    public function destroy(DiagnosticTest $diagnostic): RedirectResponse
    {
        $diagnostic->delete();

        return redirect()->route('admin.diagnostics.index')->with('status', __('admin.diagnostics.deleted'));
    }

    private function persist(DiagnosticTest $test, DiagnosticTestRequest $request): DiagnosticTest
    {
        $data = $request->validated();

        $data['slug'] = $this->uniqueSlug('diagnostic_tests', $data['slug'] ?? null, $data['name'], $test->id);
        $data['sort_order'] ??= 0;

        $this->fillTranslatable($test, $data)->save();

        return $test;
    }
}
