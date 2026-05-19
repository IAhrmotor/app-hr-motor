<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $reportTypeLabel }}</title>
</head>
<body style="margin:0; padding:0; background:#f8fafc; font-family:Arial, Helvetica, sans-serif; color:#0f172a;">
    <div style="max-width:680px; margin:0 auto; padding:24px;">
        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:20px; padding:28px;">
            <p style="margin:0 0 8px; font-size:12px; letter-spacing:0.08em; text-transform:uppercase; color:#64748b;">
                {{ $reportTypeEmoji }} {{ $reportTypeLabel }}
            </p>

            <h1 style="margin:0 0 18px; font-size:24px; line-height:1.3;">
                {{ $title }}
            </h1>

            <p style="margin:0 0 18px; font-size:15px; line-height:1.7; white-space:pre-line;">
                {{ $description }}
            </p>

            <table cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse; margin-top:12px; font-size:14px;">
                <tr>
                    <td style="padding:10px 0; width:140px; color:#64748b; vertical-align:top;">Enviado por</td>
                    <td style="padding:10px 0; font-weight:600;">{{ $reporterName }} &lt;{{ $reporterEmail }}&gt;</td>
                </tr>
                <tr>
                    <td style="padding:10px 0; width:140px; color:#64748b; vertical-align:top;">Página</td>
                    <td style="padding:10px 0; word-break:break-word;">
                        @if($pageTitle)
                            <div style="font-weight:600; margin-bottom:4px;">{{ $pageTitle }}</div>
                        @endif
                        <a href="{{ $pageUrl }}" style="color:#2563eb; text-decoration:none;">{{ $pageUrl }}</a>
                    </td>
                </tr>
                <tr>
                    <td style="padding:10px 0; width:140px; color:#64748b; vertical-align:top;">Capturas</td>
                    <td style="padding:10px 0;">
                        @if(count($screenshots) > 0)
                            <ul style="margin:0; padding-left:18px;">
                                @foreach($screenshots as $screenshot)
                                    <li style="margin:0 0 4px;">{{ $screenshot }}</li>
                                @endforeach
                            </ul>
                        @else
                            Ninguna captura adjunta.
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
