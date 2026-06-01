@extends('layouts.app')

@section('content')
    <main class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        @if ($missingTable ?? false)
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 shadow-sm">
                La tabla de auditoría de accesos a conversaciones todavía no existe en esta base de datos. Ejecuta la migración para consultar el histórico.
            </div>
        @endif

        <div id="admin-logs-container">
            @include('admin.conversation-access-logs.partials.content')
        </div>
    </main>

    @include('admin.conversation-access-logs.partials.script')
@endsection
