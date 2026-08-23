{{ $confirmed ? __('mail.patient_booked.heading_confirmed') : __('mail.patient_booked.heading_pending') }}

{{ __('mail.greeting', ['name' => $appointment->patient_name]) }}

{{ $confirmed ? __('mail.patient_booked.intro_confirmed') : __('mail.patient_booked.intro_pending', ['phone' => $appointment->phone]) }}
@foreach (array_filter($rows, fn ($value) => filled($value)) as $label => $value)
{{ $label }}: {{ $value }}
@endforeach

{{ __('mail.patient_booked.cta') }}: {{ $confirmationUrl }}

{{ __('mail.patient_booked.change_body', ['number' => setting('appointment_number', setting('hotline'))]) }}

{{ __('mail.emergency_note', ['number' => setting('ambulance_number', setting('hotline'))]) }}

--
{{ __('mail.signoff') }}
{{ setting('site_name', 'RBR Hospital') }}
{{ __('mail.auto_note', ['hotline' => setting('hotline')]) }}
