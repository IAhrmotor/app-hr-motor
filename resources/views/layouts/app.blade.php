<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'App HR Motor') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-brand-secondary">
    <x-layout.navbar />

    <main class="flex-1">
        @yield('content')
    </main>

    <x-layout.footer />
    <x-layout.feedback-widget />
</body>
</html>
