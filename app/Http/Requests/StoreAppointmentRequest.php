<?php

namespace App\Http\Requests;

use App\Services\AppointmentSlotService;
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
            'phone' => ['required', 'string', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'],
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
            'phone.regex' => 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.',
            'appointment_date.before_or_equal' => 'Appointments can be booked up to '.AppointmentSlotService::BOOKING_WINDOW_DAYS.' days ahead.',
        ];
    }

    public function attributes(): array
    {
        return [
            'doctor_id' => 'doctor',
            'appointment_date' => 'date',
            'appointment_time' => 'time slot',
            'patient_name' => 'patient name',
        ];
    }
}
