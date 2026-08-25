<?php

namespace App\Services;

use App\Jobs\SendSms;
use App\Mail\PaymentReceived;
use App\Models\NotificationLog;
use App\Models\PatientDocument;
use App\Sms\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentNotifier
{
    public function paid(PatientDocument $document): void
    {
        $locale = $this->localeFor($document);

        $this->dispatch($document->patient?->email, new PaymentReceived($document), $locale, $document);
        $this->text($document->phone, $document, $locale);
    }

    private function localeFor(PatientDocument $document): string
    {
        return config('app.fallback_locale');
    }

    private function dispatch(?string $to, Mailable $mailable, string $locale, PatientDocument $document): void
    {
        if (blank($to)) {
            return;
        }

        $log = NotificationLog::queued('mail', 'payment_received', $to, $locale, $document);

        try {
            Mail::to($to)->locale($locale)->queue($mailable->recordAs($log?->id));
        } catch (\Throwable $e) {
            Log::warning('Could not queue a payment email.', [
                'mailable' => $mailable::class,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function text(?string $number, PatientDocument $document, string $locale): void
    {
        if (!PhoneNumber::isMobile($number)) {
            return;
        }

        $number = PhoneNumber::forGateway($number);
        $text = $this->line($document, $locale);

        $log = NotificationLog::queued('sms', 'payment_received', $number, $locale, $document, $text);

        try {
            SendSms::dispatch($number, $text, $log?->id);
        } catch (\Throwable $e) {
            Log::warning('Could not queue a payment SMS.', [
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function line(PatientDocument $document, string $locale): string
    {
        return $this->inLocale($locale, fn () => __('sms.payment_received', [
            'hospital' => setting('site_name', config('app.name')),
            'amount' => $document->amount,
            'title' => $document->title,
        ]));
    }

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
