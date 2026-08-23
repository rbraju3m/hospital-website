<?php

namespace App\Http\Requests\Portal;

use App\Sms\PhoneNumber;
use App\Support\Rules;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:32', Rules::BD_MOBILE],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => __('forms.phone_format')];
    }

    public function attributes(): array
    {
        return [
            'phone' => __('portal.fields.phone'),
            'password' => __('portal.fields.password'),
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            'phone' => PhoneNumber::national($this->input('phone')),
            'password' => $this->input('password'),
            // A deactivated account fails as though the password were wrong,
            // rather than announcing that it exists.
            'is_active' => true,
        ];

        if (! Auth::guard('patient')->attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages(['phone' => __('portal.login.failed')]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'phone' => __('portal.login.throttled', ['seconds' => RateLimiter::availableIn($this->throttleKey())]),
        ]);
    }

    private function throttleKey(): string
    {
        return 'portal|'.Str::transliterate(PhoneNumber::national($this->input('phone')).'|'.$this->ip());
    }
}
