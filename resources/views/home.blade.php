<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App HR Motor</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-brand-secondary">
    @php
        $generalSection = collect($buttonSections)->firstWhere('title', 'Herramientas generales');
        $communicationSection = collect($buttonSections)->firstWhere('title', 'Comunicación');
        $officeSection = collect($buttonSections)->firstWhere('title', 'Office 365 online');

        $otherSections = collect($buttonSections)->reject(function ($section) {
            return in_array($section['title'], ['Herramientas generales', 'Comunicación', 'Office 365 online']);
        });
    @endphp

    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-10">
        <header class="mb-6">
            <div
                class="mb-3 inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-sm font-medium text-brand-primary">
                App HR Motor
            </div>

            <h1 class="text-4xl font-bold tracking-tight text-brand-secondary">
                Accesos rápidos
            </h1>

            <p class="mt-3 max-w-2xl text-base text-brand-secondary/70">
                Selecciona una herramienta para abrir su acceso correspondiente.
            </p>
        </header>

        <div class="space-y-8">
            <div class="grid gap-8 lg:grid-cols-2">
                @if ($communicationSection)
                    <section class="rounded-3xl border border-brand-primary/20 bg-brand-primary/5 p-6 shadow-sm">
                        <div class="relative mb-5">
                            <h2 class="text-center text-2xl font-bold tracking-tight text-brand-secondary">
                                {{ $communicationSection['title'] }}
                            </h2>

                            <div
                                class="absolute right-0 top-1/2 inline-flex -translate-y-1/2 rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand-primary">
                                Destacado
                            </div>
                        </div>

                        <div class="grid justify-center grid-cols-[repeat(auto-fit,minmax(136px,136px))] gap-6">
                            @foreach ($communicationSection['buttons'] as $button)
                                <a href="{{ $button['url'] }}" target="_blank" rel="noopener noreferrer"
                                    class="group flex h-full flex-col overflow-hidden rounded-2xl border border-brand-primary/20 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md">
                                    <div class="bg-white">
                                        <img src="{{ $button['image'] }}" alt="{{ $button['label'] }}"
                                            class="block w-full">
                                    </div>

                                    <div
                                        class="flex flex-1 items-center justify-center border-t border-brand-primary/10 px-4 py-3">
                                        <h3
                                            class="text-center text-sm font-semibold uppercase tracking-wide text-brand-secondary">
                                            {{ $button['label'] }}
                                        </h3>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($generalSection)
                    <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
                        <div class="mb-5">
                            <h2 class="text-center text-2xl font-bold tracking-tight text-brand-secondary">
                                {{ $generalSection['title'] }}
                            </h2>
                        </div>

                        <div class="grid justify-center grid-cols-[repeat(auto-fit,minmax(136px,136px))] gap-6">
                            @foreach ($generalSection['buttons'] as $button)
                                <a href="{{ $button['url'] }}" target="_blank" rel="noopener noreferrer"
                                    class="group flex h-full flex-col overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md">
                                    <div class="bg-white">
                                        <img src="{{ $button['image'] }}" alt="{{ $button['label'] }}"
                                            class="block w-full">
                                    </div>

                                    <div
                                        class="flex flex-1 items-center justify-center border-t border-brand-primary/10 px-4 py-3">
                                        <h3
                                            class="text-center text-sm font-semibold uppercase tracking-wide text-brand-secondary">
                                            {{ $button['label'] }}
                                        </h3>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            @if ($officeSection)
                <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
                    <div class="mb-5">
                        <h2 class="text-center text-2xl font-bold tracking-tight text-brand-secondary">
                            {{ $officeSection['title'] }}
                        </h2>
                    </div>

                    <div class="grid justify-center grid-cols-[repeat(auto-fit,minmax(136px,136px))] gap-6">
                        @foreach ($officeSection['buttons'] as $button)
                            <a href="{{ $button['url'] }}" target="_blank" rel="noopener noreferrer"
                                class="group flex h-full flex-col overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md">
                                <div class="bg-white">
                                    <img src="{{ $button['image'] }}" alt="{{ $button['label'] }}"
                                        class="block w-full">
                                </div>

                                <div
                                    class="flex flex-1 items-center justify-center border-t border-brand-secondary/10 px-4 py-3">
                                    <h3
                                        class="text-center text-sm font-semibold uppercase tracking-wide text-brand-secondary">
                                        {{ $button['label'] }}
                                    </h3>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @foreach ($otherSections as $section)
                <section>
                    <div class="mb-5">
                        <h2 class="text-center text-2xl font-bold tracking-tight text-brand-secondary">
                            {{ $section['title'] }}
                        </h2>
                    </div>

                    <div class="grid justify-center grid-cols-[repeat(auto-fit,minmax(136px,136px))] gap-6">
                        @foreach ($section['buttons'] as $button)
                            <a href="{{ $button['url'] }}" target="_blank" rel="noopener noreferrer"
                                class="group flex h-full flex-col overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md">
                                <div class="bg-white">
                                    <img src="{{ $button['image'] }}" alt="{{ $button['label'] }}"
                                        class="block w-full">
                                </div>

                                <div
                                    class="flex flex-1 items-center justify-center border-t border-brand-primary/10 px-4 py-3">
                                    <h3
                                        class="text-center text-sm font-semibold uppercase tracking-wide text-brand-secondary">
                                        {{ $button['label'] }}
                                    </h3>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <section class="mt-8">
            <div class="mb-6">
                <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">
                    Vídeos de formación
                </h2>

                <p class="mt-2 text-brand-secondary/70">
                    Consulta aquí los vídeos destacados.
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($videos as $video)
                    <article class="overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-sm">
                        <div class="aspect-video">
                            <iframe class="h-full w-full"
                                src="https://www.youtube.com/embed/{{ $video['youtube_id'] }}"
                                title="{{ $video['title'] }}" frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen>
                            </iframe>
                        </div>

                        <div class="px-4 py-3">
                            <h3 class="text-center text-sm font-semibold text-brand-secondary">
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
