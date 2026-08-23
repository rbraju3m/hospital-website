@props(['rows'])

{{-- A label/value table. Blank values are dropped rather than shown empty:
     most of these fields are optional on an appointment. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="margin:24px 0; border:1px solid #e6ecf3; border-radius:12px; border-collapse:separate; overflow:hidden;">
    @foreach (array_filter($rows, fn ($value) => filled($value)) as $label => $value)
        <tr>
            <td style="padding:12px 16px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#0b2c4d; opacity:0.55; white-space:nowrap;">
                {{ $label }}
            </td>
            <td style="padding:12px 16px; border-bottom:1px solid #f1f5f9; font-size:14px; color:#0b2c4d; font-weight:600; text-align:right;">
                {!! nl2br(e($value)) !!}
            </td>
        </tr>
    @endforeach
</table>
