{{ __('mail.reminder.heading') }}

{{ __('mail.greeting', ['name' => $appointment->patient_name]) }}

{{ __('mail.reminder.intro', ['doctor' => $appointment->doctor?->name]) }}
@foreach (array_filter($rows, fn ($value) => filled($value)) as $label => $value)
{{ $label }}: {{ $value }}
@endforeach

{{ __('mail.reminder.bring_title') }}
@foreach (['bring_1', 'bring_2', 'bring_3', 'bring_4'] as $item)
- {{ __("appointment.confirmed.{$item}") }}
@endforeach

{{ __('mail.patient_booked.cta') }}: {{ route('appointment.confirmed', $appointment) }}

{{ __('mail.patient_booked.change_body', ['number' => setting('appointment_number', setting('hotline'))]) }}

--
{{ __('mail.signoff') }}
{{ setting('site_name', 'RBR Hospital') }}
{{ __('mail.auto_note', ['hotline' => setting('hotline')]) }}
