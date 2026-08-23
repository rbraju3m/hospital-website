<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\RegisterRequest;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('portal.auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $patient = Patient::create([
            ...$request->safe()->only(['name', 'email', 'password']),
            // The setter normalises; the validated national form is the same.
            'phone' => $request->string('phone')->value(),
            'locale' => app()->getLocale(),
            'last_login_at' => now(),
        ]);

        auth('patient')->login($patient);
        $request->session()->regenerate();

        return redirect()->route('portal.dashboard')
            ->with('status', __('portal.register.welcome', ['name' => $patient->name]));
    }
}
