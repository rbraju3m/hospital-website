{{ __('mail.greeting', ['name' => $document->patient?->name ?? 'Patient']) }}

{{ __('mail.payment_received.intro', ['amount' => $amount, 'title' => $document->title]) }}

{{ __('mail.payment_received.amount_label') }}: {{ $amount }}
{{ __('mail.payment_received.date_label') }}: {{ $paidDate }}

{{ __('mail.payment_received.closing') }}
