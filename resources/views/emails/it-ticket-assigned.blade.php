<x-mail::message>
# Ticket IT asignado

Hola {{ $assigneeName }},

Te han asignado un nuevo ticket desde la plataforma.

**Número:** {{ $ticketNumber }}  
**Prioridad:** {{ $priorityLabel }}  
**Tipo de incidencia:** {{ $ticketTool }}  
**Título:** {{ $ticketTitle }}  
**Asignado por:** {{ $actorName }}

<x-mail::panel>
Entra en la sección de tickets IT para revisarlo y comenzar la gestión.
</x-mail::panel>

Gracias,  
{{ config('app.name', 'HR Motor') }}
</x-mail::message>
