<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva incidencia IT</title>
</head>
<body style="margin:0;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <div style="max-width:640px;margin:0 auto;padding:32px 20px;">
        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:20px;padding:28px;">
            <h1 style="margin:0 0 16px;font-size:24px;line-height:1.2;">Nueva incidencia IT</h1>

            <p style="margin:0 0 20px;line-height:1.7;">Se ha creado una nueva incidencia desde la plataforma.</p>

            <div style="line-height:1.8;font-size:15px;">
                <p style="margin:0;"><strong>Usuario:</strong> {{ $reporterName }}</p>
                <p style="margin:0;"><strong>Prioridad:</strong> {{ $priorityLabel }}</p>
                <p style="margin:0;"><strong>Número:</strong> {{ $ticketNumber }}</p>
                <p style="margin:0;"><strong>Tipo de incidencia:</strong> {{ $ticketTool }}</p>
                <p style="margin:0;"><strong>Título:</strong> {{ $ticketTitle }}</p>
            </div>

            <div style="margin:24px 0 0;padding:18px 20px;background:#eff6ff;border-radius:16px;border:1px solid #bfdbfe;">
                Revisa la bandeja de tickets IT para continuar con la gestión.
            </div>

            <p style="margin:24px 0 0;">Gracias,<br>{{ config('app.name', 'HR Motor') }}</p>
        </div>
    </div>
</body>
</html>
