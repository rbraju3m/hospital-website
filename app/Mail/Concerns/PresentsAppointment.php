<?php

namespace App\Mail\Concerns;

use App\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Shared presentation for the appointment emails.
 *
 * Labels come from `appointment.confirmed.*` rather than a mail-only set: the
 * email says the same things as the confirmation page, and the two drifting
 * apart is exactly the sort of thing nobody notices for a year.
 */
trait PresentsAppointment
{
    /**
     * Carbon carries its own locale, and Mailable::withLocale only moves the
     * translator — without this, a Bangla email would print English month and
     * weekday names. Same trap the SetLocale middleware works around.
     */
    protected function alignCarbonLocale(): void
    {
        $locale = $this->locale ?: config('app.locale');

        Carbon::setLocale($locale);
        CarbonImmutable::setLocale($locale);
    }

    /**
     * The confirmation page link, signed.
     *
     * That page shows a patient's name and phone behind nothing but a booking
     * reference, so it is no longer reachable without a signature. Built here
     * rather than in the view because the views also exist as plain text.
     */
    protected function confirmationUrl(Appointment $appointment): string
    {
        return URL::signedRoute('appointment.confirmed', $appointment);
    }

    protected function longDate(Appointment $appointment): string
    {
        return $appointment->appointment_date->translatedFormat('l, j F Y');
    }

    /** What the patient needs: where to go, when, and what it costs. */
    protected function patientRows(Appointment $appointment): array
    {
        $doctor = $appointment->doctor;

        return [
            __('appointment.confirmed.reference_label') => $appointment->reference,
            __('appointment.confirmed.consultant') => $doctor?->name,
            __('appointment.confirmed.department') => $appointment->department?->name ?? $doctor?->department?->name,
            __('appointment.confirmed.date') => $this->longDate($appointment),
            __('appointment.confirmed.time') => $appointment->formattedTime(),
            // The chamber is where they actually have to stand.
            __('appointment.confirmed.location') => $doctor?->chamber,
            __('appointment.confirmed.patient') => $appointment->patient_name,
            __('appointment.confirmed.fee') => $doctor ? '৳'.number_format($this->fee($appointment)) : null,
        ];
    }

    /** What the desk needs: who to call, and anything the patient wrote down. */
    protected function staffRows(Appointment $appointment): array
    {
        return [
            __('appointment.confirmed.reference_label') => $appointment->reference,
            __('appointment.confirmed.patient') => $appointment->patient_name,
            __('mail.labels.phone') => $appointment->phone,
            __('mail.labels.email') => $appointment->email,
            __('mail.labels.age') => $appointment->age,
            __('appointment.confirmed.consultant') => $appointment->doctor?->name,
            __('appointment.confirmed.date') => $this->longDate($appointment),
            __('appointment.confirmed.time') => $appointment->formattedTime(),
            __('mail.labels.visit_type') => __("admin.visit.{$appointment->visit_type}"),
            __('mail.labels.notes') => $appointment->notes,
        ];
    }

    /** Follow-ups are cheaper where a consultant sets a follow-up rate. */
    private function fee(Appointment $appointment): int
    {
        $doctor = $appointment->doctor;

        return $appointment->visit_type === 'follow_up' && $doctor->follow_up_fee
            ? $doctor->follow_up_fee
            : $doctor->consultation_fee;
    }
}
