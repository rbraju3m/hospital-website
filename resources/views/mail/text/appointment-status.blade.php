{{ __("mail.patient_status.heading_{$appointment->status}") }}

{{ __('mail.greeting', ['name' => $appointment->patient_name]) }}

{{ __("mail.patient_status.intro_{$appointment->status}", ['doctor' => $appointment->doctor?->name, 'date' => $date]) }}
@foreach (array_filter($rows, fn ($value) => filled($value)) as $label => $value)
{{ $label }}: {{ $value }}
@endforeach
@if ($appointment->status === 'confirmed')

{{ __('mail.patient_booked.cta') }}: {{ route('appointment.confirmed', $appointment) }}

{{ __('mail.patient_booked.change_body', ['number' => setting('appointment_number', setting('hotline'))]) }}
@else

{{ __('mail.patient_status.cta_rebook') }}: {{ route('appointment.create') }}

{{ __('mail.patient_status.rebook_body', ['number' => setting('appointment_number', setting('hotline'))]) }}
@endif

--
{{ __('mail.signoff') }}
{{ setting('site_name', 'RBR Hospital') }}
{{ __('mail.auto_note', ['hotline' => setting('hotline')]) }}
