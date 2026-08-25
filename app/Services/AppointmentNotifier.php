<?php

namespace App\Services;

use App\Jobs\SendSms;
use App\Mail\AppointmentBooked;
use App\Mail\AppointmentChangedAlert;
use App\Mail\AppointmentReminder;
use App\Mail\AppointmentStatusChanged;
use App\Mail\NewAppointmentAlert;
use App\Models\Appointment;
use App\Models\NotificationLog;
use App\Sms\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Every appointment notification in one place, so the website, the front desk
 * and the status buttons cannot drift apart on who gets told what.
 *
 * Two channels, and they are not equivalent. Email reaches the minority of
 * patients who gave an address; SMS reaches everyone, because phone is the one
 * contact detail the booking form requires. If only one of them works, it
 * should be the SMS.
 *
 * Nothing here is allowed to fail loudly: the booking is the thing that
 * matters, and a mail server or a gateway having a bad afternoon must never
 * turn a successful booking into a 500. Failures are logged and swallowed.
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
        $locale = $this->localeFor($appointment);

        $this->dispatch($appointment->email, new AppointmentBooked($appointment), $locale, 'booked', $appointment);

        $this->text($appointment->phone, $appointment, $locale, $appointment->status === 'confirmed'
            ? 'booked_confirmed'
            : 'booked_pending');

        if ($alertDesk) {
            // The desk reads both languages; the site default is as good a
            // choice as any and keeps alerts consistent with each other.
            $this->dispatch($this->deskAddress(), new NewAppointmentAlert($appointment), config('app.fallback_locale'), 'desk_alert', $appointment);

            $this->text(setting('desk_sms_number'), $appointment, config('app.fallback_locale'), 'desk_alert');
        }
    }

    /** The desk confirmed or cancelled — only those two are worth an email. */
    public function statusChanged(Appointment $appointment): void
    {
        if (! in_array($appointment->status, AppointmentStatusChanged::NOTIFIABLE, true)) {
            return;
        }

        $locale = $this->localeFor($appointment);

        $this->dispatch($appointment->email, new AppointmentStatusChanged($appointment), $locale, 'status_'.$appointment->status, $appointment);
        $this->text($appointment->phone, $appointment, $locale, $appointment->status);
    }

    /**
     * Tomorrow's nudge, from the scheduled command.
     *
     * Both channels: the SMS is what most patients will actually see, the
     * email carries the "what to bring" list that will not fit in one.
     */
    public function reminder(Appointment $appointment): void
    {
        $locale = $this->localeFor($appointment);

        $this->dispatch($appointment->email, new AppointmentReminder($appointment), $locale, 'reminder', $appointment);
        $this->text($appointment->phone, $appointment, $locale, 'reminder');
    }

    /**
     * A patient moved or cancelled their own booking from the portal.
     *
     * The desk is told and the patient is not: they are the one who just did
     * it, and the portal has already said so on the screen in front of them.
     * Mirror image of the rule that the desk gets no alert for a booking it
     * took itself.
     */
    public function changedByPatient(Appointment $appointment, string $change, ?string $previous = null): void
    {
        $locale = config('app.fallback_locale');

        $this->dispatch(
            $this->deskAddress(),
            new AppointmentChangedAlert($appointment, $change, $previous),
            $locale,
            "patient_{$change}",
            $appointment,
        );

        $this->text(
            setting('desk_sms_number'),
            $appointment,
            $locale,
            $change === 'cancelled' ? 'desk_patient_cancelled' : 'desk_patient_moved',
        );
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

    private function dispatch(?string $to, Mailable $mailable, string $locale, string $type, Appointment $appointment): void
    {
        // Patient email is optional, and the desk address is an editable
        // setting somebody can empty out. Neither is an error.
        if (blank($to)) {
            return;
        }

        // Written down before it goes, and marked sent by RecordMailDelivery
        // when the mail server takes it. A row that stays `queued` is the
        // shape of a queue worker nobody started.
        $log = NotificationLog::queued('mail', $type, $to, $locale, $appointment);

        try {
            Mail::to($to)->locale($locale)->queue($mailable->recordAs($log?->id));
        } catch (\Throwable $e) {
            Log::warning('Could not queue an appointment email.', [
                'mailable' => $mailable::class,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Queue one SMS.
     *
     * The text is rendered here rather than inside the job: the queued payload
     * then carries a finished string, so a message cannot come out in the
     * wrong language because the worker happened to be in a different locale,
     * and it survives a template being edited between queueing and sending.
     */
    private function text(?string $number, Appointment $appointment, string $locale, string $template): void
    {
        // Optional on the desk-alert setting, and a corporate landline cannot
        // receive an SMS however valid it looks.
        if (! PhoneNumber::isMobile($number)) {
            return;
        }

        $number = PhoneNumber::forGateway($number);
        $text = $this->line($appointment, $locale, $template);

        $log = NotificationLog::queued('sms', $template, $number, $locale, $appointment, $text);

        try {
            SendSms::dispatch($number, $text, $log?->id);
        } catch (\Throwable $e) {
            Log::warning('Could not queue an appointment SMS.', [
                'template' => $template,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function line(Appointment $appointment, string $locale, string $template): string
    {
        return $this->inLocale($locale, fn () => __("sms.{$template}", [
            'hospital' => setting('site_name', config('app.name')),
            'reference' => $appointment->reference,
            // Locale-aware accessor: reads the doctor's Bangla name under bn.
            'doctor' => $appointment->doctor?->name,
            'date' => $appointment->appointment_date->translatedFormat('j M'),
            'time' => $appointment->formattedTime(),
            'phone' => setting('appointment_number', setting('hotline')),
            'patient' => $appointment->patient_name,
            'contact' => $appointment->phone,
        ]));
    }

    /**
     * Render inside one locale and put everything back afterwards.
     *
     * Carbon is moved too — it keeps its own locale, and the date in these
     * templates goes through translatedFormat().
     */
    private function inLocale(string $locale, callable $callback): string
    {
        $previousApp = app()->getLocale();
        $previousCarbon = Carbon::getLocale();

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        CarbonImmutable::setLocale($locale);

        try {
            return $callback();
        } finally {
            app()->setLocale($previousApp);
            Carbon::setLocale($previousCarbon);
            CarbonImmutable::setLocale($previousCarbon);
        }
    }
}
