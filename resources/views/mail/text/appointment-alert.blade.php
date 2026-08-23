{{ __('mail.staff_alert.heading') }}

{{ __('mail.staff_alert.intro', ['patient' => $appointment->patient_name]) }}
@foreach (array_filter($rows, fn ($value) => filled($value)) as $label => $value)
{{ $label }}: {{ $value }}
@endforeach

{{ __('mail.staff_alert.cta') }}: {{ route('admin.appointments.show', $appointment) }}
