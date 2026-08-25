<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\RescheduleAppointmentRequest;
use App\Models\Appointment;
use App\Services\AppointmentNotifier;
use App\Services\AppointmentSlotService;
use App\Sms\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\View\View;

/**
 * A patient's own bookings, and the two things they may do to one.
 *
 * Moving and cancelling run on the same rules as the public booking form — the
 * published grid, the 30-day window, the 60-minute lead — because a slot a
 * patient can reach here is one they could have booked in the first place.
 * What is different is who is told: the desk, and not the patient, who is
 * standing in front of the screen that just said so.
 *
 * A booking that is not theirs answers 404 rather than 403. A reference is
 * short enough to guess at, and "wrong but real" is worth more to somebody
 * guessing than "not found".
 */
class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentSlotService $slots,
        private readonly AppointmentNotifier $notifier,
    ) {}

    public function index(): View
    {
        $patient = auth('patient')->user();
        $today = Carbon::today();

        return view('portal.appointments', [
            'upcoming' => $patient->appointments()
                ->whereDate('appointment_date', '>=', $today)
                ->whereNot('status', 'cancelled')
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->get(),
            'past' => $patient->appointments()
                ->where(fn ($query) => $query
                    ->whereDate('appointment_date', '<', $today)
                    ->orWhere('status', 'cancelled'))
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time')
                ->paginate(20),
        ]);
    }

    public function reschedule(Appointment $appointment): View
    {
        $this->authorizeChange($appointment);

        // A consultant with appointments cannot be deleted, so this is
        // belt and braces rather than a case anybody has seen.
        abort_unless($appointment->doctor, 404);

        return view('portal.reschedule', [
            'appointment' => $appointment,
            // Three weeks of the consultant's actual chamber days, so the page
            // opens on dates that exist rather than on an empty calendar.
            'dates' => $this->slots->availableDates($appointment->doctor, 21)->values(),
        ]);
    }

    /**
     * The same slot data the public booking page reads, scoped to this booking.
     *
     * Validated here rather than through the reschedule request: that one
     * requires a time, and asking what times exist is the question this
     * answers.
     */
    public function slots(Appointment $appointment, Request $request): JsonResponse
    {
        $this->authorizeChange($appointment);

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = CarbonImmutable::parse($validated['date']);

        return response()->json([
            'date' => $date->toDateString(),
            'slots' => $this->slots->slotsFor($appointment->doctor, $date)->values(),
        ]);
    }

    public function move(Appointment $appointment, RescheduleAppointmentRequest $request): RedirectResponse
    {
        $this->authorizeChange($appointment);

        $data = $request->validated();
        $date = CarbonImmutable::parse($data['appointment_date']);
        $doctor = $appointment->doctor;

        // Re-checked at submit time, the same as the public form: the slot may
        // have gone in the minutes the page was open.
        if (! $this->slots->isSlotAvailable($doctor, $date, $data['appointment_time'])) {
            return back()->withErrors(['appointment_time' => __('forms.slot_taken')]);
        }

        $previous = $this->describe($appointment);

        try {
            $appointment->forceFill([
                'appointment_date' => $data['appointment_date'],
                'appointment_time' => $data['appointment_time'].':00',
                'rescheduled_at' => now(),
                /* Back to pending: the desk agreed to a time, and this is not
                   that time. Somebody there has to look at the new one. */
                'status' => 'pending',
            ])->save();
        } catch (QueryException $e) {
            // The unique index is the last guard, as it is for a new booking.
            if ($this->slots->isDuplicateSlotError($e)) {
                return back()->withErrors(['appointment_time' => __('forms.slot_taken')]);
            }

            throw $e;
        }

        $this->notifier->changedByPatient($appointment, 'moved', $previous);

        return redirect()->route('portal.appointments')
            ->with('status', __('portal.appointments.moved', ['reference' => $appointment->reference]));
    }

    public function cancel(Appointment $appointment): RedirectResponse
    {
        $this->authorizeChange($appointment);

        $appointment->forceFill([
            'status' => 'cancelled',
            // Which of the two of us cancelled: a slot the patient gave up is
            // one to offer somebody else, a slot the desk cancelled is a
            // patient somebody may still need to ring.
            'cancelled_by' => 'patient',
        ])->save();

        $this->notifier->changedByPatient($appointment, 'cancelled');

        return redirect()->route('portal.appointments')
            ->with('status', __('portal.appointments.cancelled', ['reference' => $appointment->reference]));
    }

    /**
     * Theirs, and still changeable.
     *
     * Ownership is the phone number in every spelling it is accepted in — the
     * same rule `Patient::appointments()` reads by, because an appointment
     * keeps the number exactly as it was typed.
     */
    private function authorizeChange(Appointment $appointment): void
    {
        $patient = auth('patient')->user();

        abort_unless(
            in_array($appointment->phone, PhoneNumber::variants($patient->phone), true),
            404,
        );

        abort_unless($appointment->isChangeableByPatient(), 403);
    }

    private function describe(Appointment $appointment): string
    {
        return $appointment->appointment_date->translatedFormat('j M Y').', '.$appointment->formattedTime();
    }
}
