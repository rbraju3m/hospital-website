<?php

namespace App\Http\Requests;

use App\Services\AppointmentSlotService;
use App\Support\Rules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $window = AppointmentSlotService::BOOKING_WINDOW_DAYS;

        return [
            'doctor_id' => ['required', Rule::exists('doctors', 'id')->where('is_active', true)],
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.now()->addDays($window)->toDateString()],
            'appointment_time' => ['required', 'date_format:H:i'],
            'patient_name' => ['required', 'string', 'min:3', 'max:120'],
            // Bangladeshi mobile format: 01XXXXXXXXX, optionally +88 prefixed.
            'phone' => ['required', 'string', Rules::BD_MOBILE],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'gender' => ['nullable', 'in:male,female,other'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'visit_type' => ['nullable', 'in:new,follow_up'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => __('forms.phone_invalid'),
            'appointment_date.before_or_equal' => __('forms.booking_window', [
                'days' => AppointmentSlotService::BOOKING_WINDOW_DAYS,
            ]),
        ];
    }

    public function attributes(): array
    {
        return __('forms.attributes');
    }
}
