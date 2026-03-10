@php
    $itSupportUrl = 'https://hrmotor.my.site.com/hrmotorcommunity/s/login/?ec=302&startURL=%2Fhrmotorcommunity%2Fs%2Frecordlist%2FTareas_Departamento_Informatico__c%2FDefault';
@endphp

<footer class="mt-16 bg-brand-secondary text-white">
    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <div class="flex flex-col gap-8 md:flex-row md:items-start md:justify-between">
            <div class="max-w-sm">
                <a href="{{ route('home') }}" class="inline-flex items-center">
                    <img src="{{ asset('images/logo-hr-white.svg') }}" alt="HR Motor" class="h-10 w-auto">
                </a>

                <p class="mt-4 text-sm leading-6 text-white/75">
                    Portal interno de recursos, herramientas y contenidos de apoyo para el equipo.
                </p>
            </div>

            <div class="grid gap-8 sm:grid-cols-2">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-[0.12em] text-white/90">
                        Plataforma
                    </h2>

                    <ul class="mt-4 space-y-3 text-sm text-white/75">
                        <li>
                            <a href="{{ route('home') }}" class="transition hover:text-white">
                                Inicio
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('videos') }}" class="transition hover:text-white">
                                Vídeos
                            </a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-[0.12em] text-white/90">
                        Soporte
                    </h2>

                    <ul class="mt-4 space-y-3 text-sm text-white/75">
                        <li>
                            <a href="{{ $itSupportUrl }}" class="transition hover:text-white">
                                Asistencia IT
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-8 border-t border-white/10 pt-6">
            <p class="text-sm text-white/60">
                © {{ now()->year }} HR Motor. Portal interno.
            </p>
        </div>
    </div>
</footer>
