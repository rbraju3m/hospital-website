<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Slide;
use App\Support\HomeLayouts;
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
            'layouts' => HomeLayouts::all(),
            'layout' => HomeLayouts::current(),
            // Shown against the slider option: choosing a layout with nothing
            // to put in it is the one way to end up with a worse home page
            // than the one you started with.
            'slideCount' => Slide::active()->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Checkboxes post nothing when unchecked, so the registry — not the
        // payload — decides which keys get written.
        SiteFeatures::store($request->input('features', []));

        /* Validated against the registry rather than trusted: a setting naming
           a layout that does not exist would be a blank home page, and
           HomeLayouts falls back for exactly that reason — but there is no
           sense writing the bad value down in the first place. */
        $layout = $request->string('home_layout')->value();

        if (HomeLayouts::exists($layout)) {
            Setting::updateOrCreate(['key' => HomeLayouts::SETTING], ['value' => $layout]);
        }

        return back()->with('status', __('admin.site_controls.saved'));
    }
}
