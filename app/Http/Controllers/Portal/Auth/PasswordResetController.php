<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ResetPasswordRequest;
use App\Jobs\SendSms;
use App\Models\Patient;
use App\Services\PasswordResetCodes;
use App\Sms\PhoneNumber;
use App\Support\Rules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Recovery by SMS.
 *
 * Email is optional on a portal account, so a code to the registered mobile is
 * the only route back in that reaches every patient.
 */
class PasswordResetController extends Controller
{
    public function __construct(private readonly PasswordResetCodes $codes) {}

    public function request(): View
    {
        return view('portal.auth.forgot');
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            ['phone' => ['required', 'string', 'max:32', Rules::BD_MOBILE]],
            ['phone.regex' => __('forms.phone_format')]
        );

        $national = PhoneNumber::national($validated['phone']);
        $patient = Patient::active()->where('phone', $national)->first();

        if ($patient) {
            $this->text($patient, $this->codes->issue($national));
        }

        // The same answer either way: whether a number has an account here is
        // not something a stranger gets to find out.
        return redirect()->route('portal.password.reset', ['phone' => $validated['phone']])
            ->with('status', __('portal.forgot.sent'));
    }

    public function reset(Request $request): View
    {
        return view('portal.auth.reset', ['phone' => $request->query('phone', '')]);
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $national = PhoneNumber::national($validated['phone']);

        if (! $this->codes->consume($national, $validated['code'])) {
            return back()->withInput($request->except(['code', 'password', 'password_confirmation']))
                ->withErrors(['code' => __('portal.reset.invalid_code')]);
        }

        $patient = Patient::active()->where('phone', $national)->first();

        if (! $patient) {
            return back()->withErrors(['code' => __('portal.reset.invalid_code')]);
        }

        $patient->forceFill(['password' => $validated['password']])->save();

        // Every other session for this account dies with the password.
        auth('patient')->logoutOtherDevices($validated['password']);

        return redirect()->route('portal.login')->with('status', __('portal.reset.done'));
    }

    private function text(Patient $patient, string $code): void
    {
        $number = PhoneNumber::forGateway($patient->phone);

        try {
            SendSms::dispatch($number, __('sms.password_reset', [
                'hospital' => setting('site_name', config('app.name')),
                'code' => $code,
                'minutes' => PasswordResetCodes::TTL_MINUTES,
            ], $patient->locale ?: config('app.fallback_locale')));
        } catch (\Throwable $e) {
            Log::warning('Could not queue a portal reset code.', ['exception' => $e->getMessage()]);
        }
    }
}
