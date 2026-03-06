<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App HR Motor</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-6xl flex-col px-6 py-10">
        <header class="mb-10">
            <div class="mb-3 inline-flex rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-700">
                App HR Motor
            </div>

            <h1 class="text-4xl font-bold tracking-tight">
                Accesos rápidos
            </h1>

            <p class="mt-3 max-w-2xl text-base text-slate-600">
                Selecciona uno de los accesos disponibles para abrir la herramienta o recurso correspondiente.
            </p>
        </header>

        <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($buttons as $button)
                <a
                    href="{{ $button['url'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
                >
                    <div class="flex h-full flex-col">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-slate-900">
                                {{ $button['label'] }}
                            </h2>

                            <span class="text-slate-400 transition group-hover:text-blue-600">
                                →
                            </span>
                        </div>

                        <p class="text-sm leading-6 text-slate-600">
                            {{ $button['description'] }}
                        </p>

                        <div class="mt-6 text-sm font-medium text-blue-600">
                            Abrir enlace
                        </div>
                    </div>
                </a>
            @endforeach
        </section>
    </main>
</body>
</html>