@props(['url', 'label'])

<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 24px 0;">
    <tr>
        <td style="background-color:#0d867c; border-radius:9999px;">
            <a href="{{ $url }}"
               style="display:inline-block; padding:13px 28px; font-size:14px; font-weight:700;
                      color:#ffffff; text-decoration:none;">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>
