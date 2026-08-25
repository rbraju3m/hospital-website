<?php

namespace App\Http\Requests\Portal;

use App\Services\AppointmentSlotService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A patient moving their own booking.
 *
 * The same window and the same grid the public form uses: a slot a patient can
 * pick here is one they could have booked in the first place, so nothing about
 * the consultant's day arrives through a door the booking rules do not watch.
 * The doctor is not in the payload — moving to a different consultant is a new
 * booking, not a reschedule, and the desk should hear about that as one.
 */
class RescheduleAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('patient')->check();
    }

    public function rules(): array
    {
        $window = AppointmentSlotService::BOOKING_WINDOW_DAYS;

        return [
            'appointment_date' => [
                'required', 'date_format:Y-m-d', 'after_or_equal:today',
                'before_or_equal:'.now()->addDays($window)->toDateString(),
            ],
            'appointment_time' => ['required', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_date.before_or_equal' => __('forms.booking_window', [
                'days' => AppointmentSlotService::BOOKING_WINDOW_DAYS,
            ]),
        ];
    }

    public function attributes(): array
    {
        return (array) __('forms.attributes');
    }
}
