<?php

namespace App\Services;

use App\Mail\AppointmentBooked;
use App\Mail\AppointmentStatusChanged;
use App\Mail\NewAppointmentAlert;
use App\Models\Appointment;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Every appointment email in one place, so the website, the front desk and the
 * status buttons cannot drift apart on who gets told what.
 *
 * Nothing here is allowed to fail loudly: the booking is the thing that
 * matters, and a mail server having a bad afternoon must never turn a
 * successful booking into a 500. Failures are logged and swallowed.
 */
class AppointmentNotifier
{
    /**
     * A booking now exists.
     *
     * $alertDesk is false for bookings the desk took itself — they do not need
     * an email about the call they just answered.
     */
    public function booked(Appointment $appointment, bool $alertDesk = true): void
    {
        $this->dispatch($appointment->email, new AppointmentBooked($appointment), $this->localeFor($appointment));

        if ($alertDesk) {
            // The desk reads both languages; the site default is as good a
            // choice as any and keeps alerts consistent with each other.
            $this->dispatch($this->deskAddress(), new NewAppointmentAlert($appointment), config('app.fallback_locale'));
        }
    }

    /** The desk confirmed or cancelled — only those two are worth an email. */
    public function statusChanged(Appointment $appointment): void
    {
        if (! in_array($appointment->status, AppointmentStatusChanged::NOTIFIABLE, true)) {
            return;
        }

        $this->dispatch($appointment->email, new AppointmentStatusChanged($appointment), $this->localeFor($appointment));
    }

    /**
     * The language the patient booked in.
     *
     * Discarded unless it is still a configured locale — a stale row from a
     * locale that has since been removed must not point the translator at a
     * directory that no longer exists.
     */
    private function localeFor(Appointment $appointment): string
    {
        $locale = $appointment->locale;

        return is_string($locale) && array_key_exists($locale, config('app.available_locales', []))
            ? $locale
            : config('app.fallback_locale');
    }

    private function deskAddress(): ?string
    {
        return setting('appointment_email') ?: setting('email');
    }

    private function dispatch(?string $to, Mailable $mailable, string $locale): void
    {
        // Patient email is optional, and the desk address is an editable
        // setting somebody can empty out. Neither is an error.
        if (blank($to)) {
            return;
        }

        try {
            Mail::to($to)->locale($locale)->queue($mailable);
        } catch (\Throwable $e) {
            Log::warning('Could not queue an appointment email.', [
                'mailable' => $mailable::class,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
