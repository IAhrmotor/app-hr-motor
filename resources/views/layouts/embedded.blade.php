<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'App HR Motor') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex h-screen flex-col overflow-hidden bg-slate-50 text-brand-secondary">
    <x-layout.navbar />

    <main class="flex-1 min-h-0 overflow-hidden">
        @yield('content')
    </main>
</body>
</html>
