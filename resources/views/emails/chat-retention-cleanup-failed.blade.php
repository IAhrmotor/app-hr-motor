<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error en la limpieza diaria del chat</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <h1 style="margin: 0 0 12px; font-size: 20px;">Error en la limpieza diaria del chat</h1>

    <p style="margin: 0 0 10px;">Se ha ejecutado la limpieza automática de mensajes de chat con más de seis meses, pero se han producido errores durante el proceso.</p>

    <ul style="margin: 0 0 16px; padding-left: 18px;">
        <li><strong>Fecha de corte:</strong> {{ $cutoff }}</li>
        <li><strong>Mensajes eliminados:</strong> {{ $deletedCount }}</li>
        <li><strong>Usuarios afectados:</strong> {{ filled($affectedUsers) ? implode(', ', $affectedUsers) : 'Ninguno' }}</li>
    </ul>

    <h2 style="margin: 0 0 8px; font-size: 16px;">Errores detectados</h2>

    <ul style="margin: 0; padding-left: 18px;">
        @forelse ($errors as $error)
            <li style="margin-bottom: 8px;">
                Mensaje #{{ $error['message_id'] ?? 'N/D' }} (conversación {{ $error['conversation_id'] ?? 'N/D' }}, usuario {{ $error['sender_name'] ?? 'N/D' }}): {{ $error['error'] ?? 'Error desconocido' }}
            </li>
        @empty
            <li>No se han registrado detalles adicionales.</li>
        @endforelse
    </ul>
</body>
</html>
