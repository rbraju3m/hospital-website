<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/**
 * A booking taken at the front desk.
 *
 * Deliberately laxer than the public StoreAppointmentRequest: no 30-day window
 * and no 60-minute lead time. Staff take calls for patients already on their
 * way in, and they can see the consultant's actual day — constraints that exist
 * to protect an unattended web form only get in their way.
 */
class AppointmentRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'exists:doctors,id'],
            'patient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'appointment_date' => ['required', 'date_format:Y-m-d'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'visit_type' => ['required', Rule::in(['new', 'follow_up'])],
            'status' => ['required', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            // Which language to email this patient in, now and whenever the
            // desk confirms or cancels later.
            'locale' => ['required', Rule::in(array_keys(config('app.available_locales', [])))],
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => __('forms.phone_format')];
    }

    public function attributes(): array
    {
        return (array) __('admin.fields');
    }
}
