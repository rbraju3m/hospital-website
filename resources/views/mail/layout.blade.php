{{--
    Email layout.

    Table-based with inline styles on purpose: Outlook and most webmail strip
    <style> blocks and ignore flex/grid, so anything structural has to be an
    attribute on a <table>. Keep new markup to the same shape.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('subject')</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; -webkit-font-smoothing:antialiased;">

{{-- Inbox preview line: shown next to the subject, never on the page. --}}
<div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent; height:0; width:0;">
    @yield('preheader')
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background-color:#f1f5f9; padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
                   style="width:100%; max-width:600px; background-color:#ffffff; border-radius:16px; overflow:hidden;
                          font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

                <tr>
                    <td style="background-color:#0b2c4d; padding:24px 32px;">
                        <a href="{{ route('home') }}" style="color:#ffffff; text-decoration:none; font-size:17px; font-weight:700; letter-spacing:-0.2px;">
                            {{ setting('site_name', 'RBR Hospital') }}
                        </a>
                    </td>
                </tr>

                <tr>
                    <td style="padding:32px 32px 8px 32px;">
                        <h1 style="margin:0 0 16px 0; font-size:22px; line-height:1.3; color:#0b2c4d; font-weight:700;">
                            @yield('heading')
                        </h1>

                        @yield('body')
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px 32px 32px 32px;">
                        <p style="margin:0; font-size:15px; line-height:1.6; color:#0b2c4d;">
                            {{ __('mail.signoff') }}<br>
                            <strong>{{ setting('site_name', 'RBR Hospital') }}</strong>
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="background-color:#f8fafc; border-top:1px solid #e6ecf3; padding:20px 32px;">
                        <p style="margin:0 0 8px 0; font-size:12px; line-height:1.6; color:#0b2c4d; opacity:0.65;">
                            {{ setting('address_line') }}@if (setting('address_city')), {{ setting('address_city') }}@endif
                        </p>
                        <p style="margin:0; font-size:12px; line-height:1.6; color:#0b2c4d; opacity:0.55;">
                            {{ __('mail.auto_note', ['hotline' => setting('hotline')]) }}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
