<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Doctor;
use App\Services\AppointmentSlotService;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'exists:departments,slug'],
            'gender' => ['nullable', 'in:male,female'],
            'sort' => ['nullable', 'in:name,experience,fee'],
        ]);

        $doctors = Doctor::active()
            ->with('department')
            ->search($filters['q'] ?? null)
            ->when($filters['department'] ?? null, fn ($q, $slug) => $q->whereHas('department', fn ($d) => $d->where('slug', $slug)))
            ->when($filters['gender'] ?? null, fn ($q, $gender) => $q->where('gender', $gender))
            ->when(($filters['sort'] ?? null) === 'experience', fn ($q) => $q->orderByDesc('experience_years'))
            ->when(($filters['sort'] ?? null) === 'fee', fn ($q) => $q->orderBy('consultation_fee'))
            ->when(! ($filters['sort'] ?? null) || $filters['sort'] === 'name', fn ($q) => $q->ordered())
            ->paginate(12)
            ->withQueryString();

        return view('pages.doctors.index', [
            'doctors' => $doctors,
            'departments' => Department::active()->ordered()->get(),
            'filters' => $filters,
        ]);
    }

    public function show(Doctor $doctor, AppointmentSlotService $slots)
    {
        abort_unless($doctor->is_active, 404);

        $doctor->load(['department', 'schedules' => fn ($q) => $q->where('is_active', true)
            ->orderBy('day_of_week')->orderBy('start_time')]);

        return view('pages.doctors.show', [
            'doctor' => $doctor,
            'availableDates' => $doctor->accepts_online_appointment
                ? $slots->availableDates($doctor, 14)
                : collect(),
            'colleagues' => Doctor::active()
                ->where('department_id', $doctor->department_id)
                ->whereKeyNot($doctor->id)
                ->ordered()
                ->take(3)
                ->get(),
        ]);
    }
}
