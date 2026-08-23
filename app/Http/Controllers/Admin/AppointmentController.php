<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AppointmentRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\AppointmentNotifier;
use App\Services\AppointmentSlotService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentSlotService $slots,
        private readonly AppointmentNotifier $notifier,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.appointments.index', [
            'appointments' => $this->filtered($request)->paginate(25)->withQueryString(),
            'doctors' => Doctor::ordered()->get(),
            'counts' => Appointment::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.appointments.form', [
            'doctors' => Doctor::with('department')->ordered()->get(),
            'selectedDoctor' => $request->integer('doctor') ?: null,
            'date' => $request->query('date') ?: Carbon::today()->toDateString(),
        ]);
    }

    public function store(AppointmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $doctor = Doctor::findOrFail($data['doctor_id']);

        try {
            $appointment = Appointment::create([
                ...$data,
                'reference' => $this->slots->generateReference(),
                'department_id' => $doctor->department_id,
                'appointment_time' => $data['appointment_time'].':00',
                'source' => 'front-desk',
            ]);
        } catch (QueryException $e) {
            // The front desk may book outside the published grid, but never on
            // top of an existing booking for the same doctor and minute.
            if ($this->slots->isDuplicateSlotError($e)) {
                return back()->withInput()->withErrors(['appointment_time' => __('admin.appointments.slot_taken')]);
            }

            throw $e;
        }

        // No desk alert: whoever is reading this took the call themselves.
        $this->notifier->booked($appointment, alertDesk: false);

        return redirect()->route('admin.appointments.show', $appointment)
            ->with('status', __('admin.appointments.created', ['reference' => $appointment->reference]));
    }

    public function show(Appointment $appointment): View
    {
        $appointment->load(['doctor.department', 'department']);

        return view('admin.appointments.show', [
            'appointment' => $appointment,
            'sameDay' => Appointment::with('doctor')
                ->where('doctor_id', $appointment->doctor_id)
                ->whereDate('appointment_date', $appointment->appointment_date)
                ->whereKeyNot($appointment->getKey())
                ->orderBy('appointment_time')
                ->get(),
        ]);
    }

    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,cancelled,completed'],
        ]);

        // Re-clicking the status a booking already has must not send the
        // patient a second "your appointment is confirmed" email.
        if ($appointment->status === $validated['status']) {
            return back();
        }

        $appointment->update($validated);

        $this->notifier->statusChanged($appointment);

        return back()->with('status', __('admin.appointments.status_changed', [
            'status' => __("admin.appointments.status.{$validated['status']}"),
        ]));
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $appointment->delete();

        return redirect()->route('admin.appointments.index')
            ->with('status', __('admin.appointments.deleted'));
    }

    /** The current filter as a CSV, for the day sheet the front desk prints. */
    public function export(Request $request): StreamedResponse
    {
        $appointments = $this->filtered($request)->with('doctor')->get();
        $filename = 'appointments-'.CarbonImmutable::now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($appointments) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Reference', 'Date', 'Time', 'Patient', 'Phone', 'Email',
                'Age', 'Gender', 'Doctor', 'Visit type', 'Status', 'Source', 'Notes',
            ]);

            foreach ($appointments as $appointment) {
                fputcsv($handle, [
                    $appointment->reference,
                    $appointment->appointment_date->toDateString(),
                    $appointment->appointment_time,
                    $appointment->patient_name,
                    $appointment->phone,
                    $appointment->email,
                    $appointment->age,
                    $appointment->gender,
                    // Base column, not the accessor: an export is a record, and
                    // it should read the same whichever locale produced it.
                    $appointment->doctor?->untranslated('name'),
                    $appointment->visit_type,
                    $appointment->status,
                    $appointment->source,
                    $appointment->notes,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request)
    {
        return Appointment::with('doctor')
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where(
                fn ($inner) => $inner->where('reference', 'like', "%{$term}%")
                    ->orWhere('patient_name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
            ))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('doctor'), fn ($q) => $q->where('doctor_id', $request->integer('doctor')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('appointment_date', $request->string('date')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('appointment_date', '>=', $request->string('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('appointment_date', '<=', $request->string('to')))
            ->orderByDesc('appointment_date')
            ->orderBy('appointment_time');
    }
}
