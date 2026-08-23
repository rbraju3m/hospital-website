<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function create(): View
    {
        return view('portal.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        auth('patient')->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('portal.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        auth('patient')->logout();

        // Only this guard's session is discarded — a staff member testing the
        // portal on the same browser should not be signed out of the panel.
        $request->session()->regenerate();

        return redirect()->route('portal.login')->with('status', __('portal.login.signed_out'));
    }
}
