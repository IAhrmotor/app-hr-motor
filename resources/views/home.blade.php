<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App HR Motor</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-10">
        <header class="mb-10">
            <div class="mb-3 inline-flex rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-700">
                App HR Motor
            </div>

            <h1 class="text-4xl font-bold tracking-tight">
                Accesos rápidos
            </h1>

            <p class="mt-3 max-w-2xl text-base text-slate-600">
                Selecciona una herramienta para abrir su acceso correspondiente.
            </p>
        </header>

        <section class="flex flex-wrap gap-6">
            @foreach ($buttons as $button)
                <a href="{{ $button['url'] }}" target="_blank" rel="noopener noreferrer"
                    class="group w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md">
                    <div class="bg-white">
                        <img src="{{ $button['image'] }}" alt="{{ $button['label'] }}" class="block w-full">
                    </div>

                    <div class="border-t border-slate-200 px-4 py-4">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-800">
                            {{ $button['label'] }}
                        </h2>
                    </div>
                </a>
            @endforeach
        </section>
    </main>
</body>

</html>
