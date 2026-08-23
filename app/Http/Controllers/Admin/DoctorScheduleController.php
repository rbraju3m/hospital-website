<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DoctorScheduleRequest;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Http\RedirectResponse;

/**
 * Chamber hours. A schedule is a weekly recurring pattern, not a calendar —
 * AppointmentSlotService expands it into concrete slots per date, so editing
 * one here changes availability from the next matching day onwards.
 */
class DoctorScheduleController extends Controller
{
    public function store(DoctorScheduleRequest $request, Doctor $doctor): RedirectResponse
    {
        $doctor->schedules()->create($request->validated());

        return back()->with('status', __('admin.doctors.schedule_added'));
    }

    public function update(DoctorScheduleRequest $request, Doctor $doctor, DoctorSchedule $schedule): RedirectResponse
    {
        abort_unless($schedule->doctor_id === $doctor->id, 404);

        $schedule->update($request->validated());

        return back()->with('status', __('admin.doctors.schedule_updated'));
    }

    public function destroy(Doctor $doctor, DoctorSchedule $schedule): RedirectResponse
    {
        abort_unless($schedule->doctor_id === $doctor->id, 404);

        $schedule->delete();

        return back()->with('status', __('admin.doctors.schedule_removed'));
    }
}
