<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('portal.profile', ['patient' => auth('patient')->user()]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $patient = auth('patient')->user();

        $patient->fill($request->safe()->only(['name', 'email', 'date_of_birth', 'gender']));

        if ($request->filled('password')) {
            $patient->password = $request->string('password')->value();
        }

        $patient->save();

        // The mobile is the account's identity and the key everything is
        // matched on, so it is shown but never editable here — changing it
        // would silently move somebody's records.
        return back()->with('status', __('portal.profile.saved'));
    }
}
