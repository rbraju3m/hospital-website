<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Services\AppointmentNotifier;
use App\Services\AppointmentSlotService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentSlotService $slots,
        private readonly AppointmentNotifier $notifier,
    ) {}

    /** Step 1–3 of the booking flow, pre-fillable via ?doctor= / ?department= / ?date=. */
    public function create(Request $request)
    {
        $doctor = $request->filled('doctor')
            ? Doctor::active()->with('department')->where('slug', $request->query('doctor'))->first()
            : null;

        return view('pages.appointment.create', [
            'doctor' => $doctor,
            'departments' => Department::active()->ordered()->get(),
            'selectedDepartment' => $request->query('department'),
            'availableDates' => $doctor ? $this->slots->availableDates($doctor, 21) : collect(),
        ]);
    }

    /** JSON used by the booking form to swap doctors/dates without a reload. */
    public function doctors(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department' => ['nullable', 'string', 'exists:departments,slug'],
        ]);

        $doctors = Doctor::active()
            ->where('accepts_online_appointment', true)
            ->when($validated['department'] ?? null, fn ($q, $slug) => $q->whereHas('department', fn ($d) => $d->where('slug', $slug)))
            ->ordered()
            ->get();

        // Built by hand rather than serialising the models: toArray() reads raw
        // attributes and would skip the locale-aware accessors entirely.
        return response()->json([
            'doctors' => $doctors->map(fn (Doctor $doctor) => [
                'id' => $doctor->id,
                'slug' => $doctor->slug,
                'name' => $doctor->name,
                'designation' => $doctor->designation,
                'speciality' => $doctor->speciality,
                'consultation_fee' => $doctor->consultation_fee,
            ])->values(),
        ]);
    }

    public function slots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => ['required', 'exists:doctors,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $doctor = Doctor::active()->findOrFail($validated['doctor_id']);
        $date = CarbonImmutable::parse($validated['date']);

        return response()->json([
            'date' => $date->toDateString(),
            'slots' => $this->slots->slotsFor($doctor, $date)->values(),
            'dates' => $this->slots->availableDates($doctor, 21)->values(),
        ]);
    }

    public function store(StoreAppointmentRequest $request)
    {
        $data = $request->validated();
        $doctor = Doctor::active()->findOrFail($data['doctor_id']);
        $date = CarbonImmutable::parse($data['appointment_date']);

        if (! $doctor->accepts_online_appointment) {
            return back()->withInput()->withErrors([
                'doctor_id' => __('forms.no_online_booking'),
            ]);
        }

        // Re-check at submit time: the slot may have been taken since the page loaded.
        if (! $this->slots->isSlotAvailable($doctor, $date, $data['appointment_time'])) {
            return back()->withInput()->withErrors([
                'appointment_time' => __('forms.slot_taken'),
            ]);
        }

        try {
            $appointment = Appointment::create([
                ...$data,
                'reference' => $this->slots->generateReference(),
                'department_id' => $doctor->department_id,
                'appointment_time' => $data['appointment_time'].':00',
                'visit_type' => $data['visit_type'] ?? 'new',
                'status' => 'pending',
                // Remembered so a confirmation email sent days later, by a
                // staff member working in the other language, still reaches
                // the patient in theirs.
                'locale' => app()->getLocale(),
            ]);
        } catch (QueryException $e) {
            // Unique index on (doctor, date, time) is the final guard against a
            // double booking that slipped through the availability re-check.
            if ($this->slots->isDuplicateSlotError($e)) {
                return back()->withInput()->withErrors([
                    'appointment_time' => __('forms.slot_taken'),
                ]);
            }

            throw $e;
        }

        $this->notifier->booked($appointment);

        return redirect()
            ->to(URL::signedRoute('appointment.confirmed', $appointment))
            ->with('status', 'appointment-booked');
    }

    public function confirmed(Appointment $appointment)
    {
        $appointment->load(['doctor.department']);

        return view('pages.appointment.confirmed', compact('appointment'));
    }
}
