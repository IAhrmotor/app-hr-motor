<x-mail::message>
# Nueva incidencia IT

Se ha creado una nueva incidencia desde la plataforma.

**Usuario:** {{ $reporterName }}  
**Prioridad:** {{ $priorityLabel }}  
**Número:** {{ $ticketNumber }}  
**Tipo de incidencia:** {{ $ticketTool }}  
**Título:** {{ $ticketTitle }}

<x-mail::panel>
Revisa la bandeja de tickets IT para continuar con la gestión.
</x-mail::panel>

Gracias,  
{{ config('app.name', 'HR Motor') }}
</x-mail::message>
