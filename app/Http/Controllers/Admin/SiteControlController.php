<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SiteFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Site controls — what the public site shows and what visitors may do.
 *
 * Every switch is declared in App\Support\SiteFeatures; this controller only
 * renders that registry and writes the submitted state back. Nothing here
 * takes a key from the request, so a crafted payload cannot create a setting
 * or flip something that is not on the page.
 */
class SiteControlController extends Controller
{
    public function edit(): View
    {
        return view('admin.site-controls.edit', [
            'groups' => SiteFeatures::groups(),
            'state' => SiteFeatures::all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Checkboxes post nothing when unchecked, so the registry — not the
        // payload — decides which keys get written.
        SiteFeatures::store($request->input('features', []));

        return back()->with('status', __('admin.site_controls.saved'));
    }
}
