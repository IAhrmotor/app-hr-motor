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

        <section class="grid justify-center grid-cols-[repeat(auto-fill,minmax(136px,136px))] gap-6">
            @foreach ($buttons as $button)
                <a href="{{ $button['url'] }}" target="_blank" rel="noopener noreferrer"
                    class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md">
                    <div class="bg-white">
                        <img src="{{ $button['image'] }}" alt="{{ $button['label'] }}" class="block w-full">
                    </div>

                    <div class="border-t border-slate-200 px-4 py-2">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-800">
                            {{ $button['label'] }}
                        </h2>
                    </div>
                </a>
            @endforeach
        </section>

        <section class="mt-14">
            <div class="mb-6">
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Vídeos de formación
                </h2>

                <p class="mt-2 text-slate-600">
                    Consulta aquí los vídeos destacados.
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($videos as $video)
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="aspect-video">
                            <iframe class="h-full w-full" src="https://www.youtube.com/embed/{{ $video['youtube_id'] }}"
                                title="{{ $video['title'] }}" frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen>
                            </iframe>
                        </div>

                        <div class="px-4 py-3">
                            <h3 class="text-sm font-semibold text-slate-800">
                                {{ $video['title'] }}
                            </h3>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </main>
</body>

</html>
