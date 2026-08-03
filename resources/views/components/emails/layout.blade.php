<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fa;color:#344054;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:15px;line-height:1.6;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f5f7fa;">
    <tr><td align="center" style="padding:32px 16px;">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;">
            <tr><td style="padding:28px 32px 24px;background:#111827;border-bottom:3px solid #f59e0b;">
                <h1 style="margin:0;color:#ffffff;font-size:22px;line-height:1.25;font-weight:700;">{{ $title }}</h1>
                @if ($subtitle)<p style="margin:7px 0 0;color:#cbd5e1;font-size:13px;">{{ $subtitle }}</p>@endif
            </td></tr>
            <tr><td style="padding:32px;">{{ $slot }}</td></tr>
            <tr><td style="padding:20px 32px;background:#f8fafc;border-top:1px solid #e5e7eb;">
                <p style="margin:0;color:#667085;font-size:12px;">Ovaj email je poslan automatski iz {{ config('app.name') }} aplikacije.</p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
