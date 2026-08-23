<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Validator;

class DoctorScheduleRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'capacity_per_slot' => ['required', 'integer', 'min:1', 'max:20'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // Two overlapping windows on the same day would generate the same slot
        // twice, and the unique index would then reject the second booking with
        // no explanation the patient could act on.
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $doctor = $this->route('doctor');
            $current = $this->route('schedule');

            $clash = $doctor->schedules()
                ->where('day_of_week', $this->integer('day_of_week'))
                ->when($current, fn ($query) => $query->whereKeyNot($current->getKey()))
                ->where('start_time', '<', $this->string('end_time').':00')
                ->where('end_time', '>', $this->string('start_time').':00')
                ->exists();

            if ($clash) {
                $validator->errors()->add('start_time', __('admin.doctors.schedule_overlap'));
            }
        });
    }
}
