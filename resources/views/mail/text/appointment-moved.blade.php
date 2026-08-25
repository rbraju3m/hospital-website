{{ __('mail.patient_moved.heading') }}

{{ __('mail.greeting', ['name' => $appointment->patient_name]) }}

{{ __('mail.patient_moved.intro', ['doctor' => $appointment->doctor?->name, 'date' => $date, 'time' => $appointment->formattedTime()]) }}

{{ __('mail.patient_moved.was', ['previous' => $previous]) }}
@foreach (array_filter($rows, fn ($value) => filled($value)) as $label => $value)
{{ $label }}: {{ $value }}
@endforeach

{{ __('mail.patient_moved.body', ['number' => setting('appointment_number', setting('hotline'))]) }}

--
{{ __('mail.signoff') }}
{{ setting('site_name', 'RBR Hospital') }}
{{ __('mail.auto_note', ['hotline' => setting('hotline')]) }}
