<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activa tu cuenta - HR Motor</title>
</head>
<body style="margin:0; padding:0; background:#f5f6f8; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f5f6f8; padding:40px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px; background:#ffffff; border-radius:20px; overflow:hidden; box-shadow:0 10px 30px rgba(15, 23, 42, 0.08);">
                    <tr>
                        <td style="padding:32px 32px 16px; border-bottom:1px solid #e5e7eb;">
                            <div style="font-size:22px; font-weight:700; color:#111827;">HR Motor</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h1 style="margin:0 0 16px; font-size:24px; line-height:1.2; color:#111827;">Activa tu cuenta</h1>
                            <p style="margin:0 0 16px; font-size:15px; line-height:1.6; color:#4b5563;">Hola{{ filled($name ?? null) ? ', ' . $name : '' }}.</p>
                            <p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#4b5563;">Ya puedes crear tu contrase&ntilde;a para entrar en la aplicaci&oacute;n interna de HR Motor.</p>
                            <p style="margin:0 0 32px;">
                                <a href="{{ $url }}" style="display:inline-block; background:#bb1f2f; color:#ffffff; text-decoration:none; font-size:15px; font-weight:700; padding:14px 22px; border-radius:12px;">Crear contrase&ntilde;a</a>
                            </p>
                            <p style="margin:0; font-size:13px; line-height:1.6; color:#6b7280;">Si no has solicitado este correo, puedes ignorarlo sin problema.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
