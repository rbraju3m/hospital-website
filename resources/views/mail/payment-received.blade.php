@extends('mail.layout')

@section('subject', __('mail.payment_received.subject'))
@section('preheader', __('mail.payment_received.preheader'))
@section('heading', __('mail.payment_received.heading'))

@section('body')
    <p style="margin:0 0 16px 0; font-size:15px; line-height:1.65; color:#0b2c4d;">
        {{ __('mail.greeting', ['name' => $document->patient?->name ?? 'Patient']) }}
    </p>

    <p style="margin:0 0 16px 0; font-size:15px; line-height:1.65; color:#0b2c4d; opacity:0.8;">
        {{ __('mail.payment_received.intro', ['amount' => $amount, 'title' => $document->title]) }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="margin:0 0 16px 0; background-color:#f8faf9; border-radius:12px;">
        <tr>
            <td style="padding:16px 18px; font-size:14px; line-height:1.6; color:#0b2c4d;">
                <strong>{{ __('mail.payment_received.amount_label')}}</strong><br>
                {{ $amount }}
            </td>
            <td style="padding:16px 18px; font-size:14px; line-height:1.6; color:#0b2c4d;">
                <strong>{{ __('mail.payment_received.date_label') }}</strong><br>
                {{ $paidDate }}
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px 0; font-size:14px; line-height:1.65; color:#0b2c4d; opacity:0.7;">
        {{ __('mail.payment_received.closing') }}
    </p>
@endsection
