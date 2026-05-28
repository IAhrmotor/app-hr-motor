@extends('layouts.app')

@section('content')
    @php
        $allSections = collect($managementSections ?? $adminSections ?? []);
        $sectionKind = fn (array $section) => $section['kind'] ?? (str_contains($section['route'] ?? '', 'logs') ? 'logs' : 'management');

        $management = collect($managementSections ?? []);
        if ($management->isEmpty()) {
            $management = $allSections->filter(fn (array $section) => $sectionKind($section) === 'management')->values();
        }

        $logs = collect($logSections ?? []);
        if ($logs->isEmpty()) {
            $logs = $allSections->filter(fn (array $section) => $sectionKind($section) === 'logs')->values();
        }

        $icons = [
            'users' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M4 21C4 17.4735 6.60771 14.5561 10 14.0709M19.8726 15.2038C19.8044 15.2079 19.7357 15.21 19.6667 15.21C18.6422 15.21 17.7077 14.7524 17 14C16.2923 14.7524 15.3578 15.2099 14.3333 15.2099C14.2643 15.2099 14.1956 15.2078 14.1274 15.2037C14.0442 15.5853 14 15.9855 14 16.3979C14 18.6121 15.2748 20.4725 17 21C18.7252 20.4725 20 18.6121 20 16.3979C20 15.9855 19.9558 15.5853 19.8726 15.2038ZM15 7C15 9.20914 13.2091 11 11 11C8.79086 11 7 9.20914 7 7C7 4.79086 8.79086 3 11 3C13.2091 3 15 4.79086 15 7Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'dealership' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M6 7H7M6 10H7M11 10H12M11 13H12M6 13H7M11 7H12M11 21V18C11 16.8954 10.1046 16 9 16C7.89543 16 7 16.8954 7 18V21M11 21H12.5M11 21H7M7 21H3V4.6C3 4.03995 3 3.75992 3.10899 3.54601C3.20487 3.35785 3.35785 3.20487 3.54601 3.10899C3.75992 3 4.03995 3 4.6 3H13.4C13.9601 3 14.2401 3 14.454 3.10899C14.6422 3.20487 14.7951 3.35785 14.891 3.54601C15 3.75992 15 4.03995 15 4.6V12M20.8832 16.0318C20.8207 16.0353 20.7578 16.0371 20.6944 16.0371C19.7553 16.0371 18.8987 15.6449 18.25 15C17.6013 15.6449 16.7446 16.0371 15.8056 16.0371C15.7422 16.0371 15.6793 16.0353 15.6168 16.0318C15.5405 16.3588 15.5 16.7018 15.5 17.0554C15.5 18.9532 16.6685 20.5479 18.25 21C19.8315 20.5479 21 18.9532 21 17.0554C21 16.7019 20.9595 16.3589 20.8832 16.0318Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'contacts' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M5 7V5C5 3.89543 5.89543 3 7 3H13H19C20.1046 3 21 3.89543 21 5V7V17V19C21 20.1046 20.1046 21 19 21H13H7C5.89543 21 5 20.1046 5 19V17V7Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 12C16 13.6569 14.6569 15 13 15C11.3431 15 10 13.6569 10 12C10 10.3431 11.3431 9 13 9C14.6569 9 16 10.3431 16 12Z" stroke="currentColor" stroke-width="1.5"/>
                <path d="M9 21C9.42546 18.6928 10.52 18 13 18C15.48 18 16.5745 18.6425 17 20.9497" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M3 7H5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3 17H5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3 12H5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'tags' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M8.5 3H11.5118C12.2455 3 12.6124 3 12.9577 3.08289C13.2638 3.15638 13.5564 3.27759 13.8249 3.44208C14.1276 3.6276 14.387 3.88703 14.9059 4.40589L20.5 10M7.5498 10.0498H7.5598M9.51178 6H8.3C6.61984 6 5.77976 6 5.13803 6.32698C4.57354 6.6146 4.1146 7.07354 3.82698 7.63803C3.5 8.27976 3.5 9.11984 3.5 10.8V12.0118C3.5 12.7455 3.5 13.1124 3.58289 13.4577C3.65638 13.7638 3.77759 14.0564 3.94208 14.3249C4.1276 14.6276 4.38703 14.887 4.90589 15.4059L8.10589 18.6059C9.29394 19.7939 9.88796 20.388 10.5729 20.6105C11.1755 20.8063 11.8245 20.8063 12.4271 20.6105C13.112 20.388 13.7061 19.7939 14.8941 18.6059L16.1059 17.3941C17.2939 16.2061 17.888 15.612 18.1105 14.9271C18.3063 14.3245 18.3063 13.6755 18.1105 13.0729C17.888 12.388 17.2939 11.7939 16.1059 10.6059L12.9059 7.40589C12.387 6.88703 12.1276 6.6276 11.8249 6.44208C11.5564 6.27759 11.2638 6.15638 10.9577 6.08289C10.6124 6 10.2455 6 9.51178 6ZM8.0498 10.0498C8.0498 10.3259 7.82595 10.5498 7.5498 10.5498C7.27366 10.5498 7.0498 10.3259 7.0498 10.0498C7.0498 9.77366 7.27366 9.5498 7.5498 9.5498C7.82595 9.5498 8.0498 9.77366 8.0498 10.0498Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'magazine' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <g clip-path="url(#clip0_429_11031)">
                <path d="M3 4V18C3 19.1046 3.89543 20 5 20H17H19C20.1046 20 21 19.1046 21 18V8H17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3 4H17V18C17 19.1046 17.8954 20 19 20V20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13 8L7 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13 12L9 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </g>
                <defs>
                <clipPath id="clip0_429_11031">
                <rect width="24" height="24" fill="white"/>
                </clipPath>
                </defs>
                </svg>
            SVG,
            'notifications' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M12 5.5C14.7614 5.5 17 7.73858 17 10.5V12.7396C17 13.2294 17.1798 13.7022 17.5052 14.0683L18.7808 15.5035C19.6407 16.4708 18.954 18 17.6597 18H6.34025C5.04598 18 4.35927 16.4708 5.21913 15.5035L6.4948 14.0683C6.82022 13.7022 6.99998 13.2294 6.99998 12.7396L7 10.5C7 7.73858 9.23858 5.5 12 5.5ZM12 5.5V3M10.9999 21H12.9999" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'notification-log' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M12 5.5C14.7614 5.5 17 7.73858 17 10.5V12.7396C17 13.2294 17.1798 13.7022 17.5052 14.0683L18.7808 15.5035C19.6407 16.4708 18.954 18 17.6597 18H6.34025C5.04598 18 4.35927 16.4708 5.21913 15.5035L6.4948 14.0683C6.82022 13.7022 6.99998 13.2294 6.99998 12.7396L7 10.5C7 7.73858 9.23858 5.5 12 5.5ZM12 5.5V3M10.9999 21H12.9999" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'user-log' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M4 21C4 17.4735 6.60771 14.5561 10 14.0709M19.8726 15.2038C19.8044 15.2079 19.7357 15.21 19.6667 15.21C18.6422 15.21 17.7077 14.7524 17 14C16.2923 14.7524 15.3578 15.2099 14.3333 15.2099C14.2643 15.2099 14.1956 15.2078 14.1274 15.2037C14.0442 15.5853 14 15.9855 14 16.3979C14 18.6121 15.2748 20.4725 17 21C18.7252 20.4725 20 18.6121 20 16.3979C20 15.9855 19.9558 15.5853 19.8726 15.2038ZM15 7C15 9.20914 13.2091 11 11 11C8.79086 11 7 9.20914 7 7C7 4.79086 8.79086 3 11 3C13.2091 3 15 4.79086 15 7Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'dealership-log' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M6 7H7M6 10H7M11 10H12M11 13H12M6 13H7M11 7H12M11 21V18C11 16.8954 10.1046 16 9 16C7.89543 16 7 16.8954 7 18V21M11 21H12.5M11 21H7M7 21H3V4.6C3 4.03995 3 3.75992 3.10899 3.54601C3.20487 3.35785 3.35785 3.20487 3.54601 3.10899C3.75992 3 4.03995 3 4.6 3H13.4C13.9601 3 14.2401 3 14.454 3.10899C14.6422 3.20487 14.7951 3.35785 14.891 3.54601C15 3.75992 15 4.03995 15 4.6V12M20.8832 16.0318C20.8207 16.0353 20.7578 16.0371 20.6944 16.0371C19.7553 16.0371 18.8987 15.6449 18.25 15C17.6013 15.6449 16.7446 16.0371 15.8056 16.0371C15.7422 16.0371 15.6793 16.0353 15.6168 16.0318C15.5405 16.3588 15.5 16.7018 15.5 17.0554C15.5 18.9532 16.6685 20.5479 18.25 21C19.8315 20.5479 21 18.9532 21 17.0554C21 16.7019 20.9595 16.3589 20.8832 16.0318Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'content-log' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <g clip-path="url(#clip0_429_11031)">
                <path d="M3 4V18C3 19.1046 3.89543 20 5 20H17H19C20.1046 20 21 19.1046 21 18V8H17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3 4H17V18C17 19.1046 17.8954 20 19 20V20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13 8L7 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13 12L9 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </g>
                <defs>
                <clipPath id="clip0_429_11031">
                <rect width="24" height="24" fill="white"/>
                </clipPath>
                </defs>
                </svg>
            SVG,
            'policy-acceptance-log' => <<<'SVG'
                <svg width="800px" height="800px" viewBox="0 0 1024 1024" class="h-11 w-11" version="1.1" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M146.3 73.06v877.71h512l219.43-239.38V73.06H146.3z m512.01 769.46V694.77h135.44L658.31 842.52z m146.27-220.89H585.16v256H219.44V146.2h585.14v475.43z" fill="currentColor" />
                    <path d="M292.59 219.34h438.86v73.14H292.59zM292.59 365.63H658.3v73.14H292.59zM292.59 511.91h219.43v73.14H292.59z" fill="currentColor" />
                </svg>
            SVG,
            'default' => <<<'SVG'
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M13.5 4.5 20.25 12 13.5 19.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M20.25 12H3.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            SVG,
        ];

        $groupIcon = fn (array $section) => $icons[$section['icon'] ?? 'default'] ?? $icons['default'];
        $compactLabel = function (array $section) {
            return match ($section['icon'] ?? '') {
                'users', 'user-log' => 'Usuarios',
                'dealership', 'dealership-log' => 'Delegaciones',
                'contacts' => 'Contactos',
                'tags' => 'Tags',
                'magazine' => 'Revista',
                'notifications', 'notification-log' => 'Notificaciones',
                'content-log' => 'Contenidos',
                'policy-acceptance-log' => 'Política',
                default => $section['label'] ?? '',
            };
        };
    @endphp

    <section
        class="relative overflow-hidden"
        style="background-image: url('{{ asset('images/hero/hero-admin.jpg') }}'); background-size: cover; background-position: center;"
    >
        <div class="absolute inset-0 bg-black/55"></div>

        <div class="relative mx-auto flex min-h-[220px] max-w-7xl items-center px-6 py-6 sm:min-h-[240px] sm:py-8 lg:min-h-[260px] lg:px-8 lg:py-10">
            <div class="max-w-3xl">
                <span
                    class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-white backdrop-blur-sm"
                >
                    Panel interno
                </span>

                <h1 class="mt-3 text-2xl font-bold tracking-tight text-white md:text-3xl lg:text-4xl">
                    {{ html_entity_decode('Administraci&oacute;n') }}
                </h1>

                <p class="mt-3 max-w-2xl text-sm leading-6 text-white/85 md:text-base">
                    {{ html_entity_decode('Centraliza desde aqu&iacute; los accesos administrativos del portal y entra r&aacute;pidamente en cada &aacute;rea de gesti&oacute;n.') }}
                </p>
            </div>
        </div>
    </section>

    <main class="mx-auto max-w-7xl px-6 py-8 lg:px-8 lg:py-10">
        @if (session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <section class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">
                        {{ html_entity_decode('Gesti&oacute;n interna') }}
                    </span>

                    <h2 class="mt-3 text-2xl font-semibold text-brand-secondary md:text-3xl">
                        {{ html_entity_decode('Panel de administraci&oacute;n') }}
                    </h2>

                    <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                        {{ html_entity_decode('Las herramientas operativas quedan como accesos directos muy compactos, mientras que los logs permanecen en formato bot&oacute;n peque&ntilde;o.') }}
                    </p>
                </div>

                <div class="w-full max-w-sm rounded-[1.5rem] border border-brand-secondary/10 bg-slate-50 p-4 shadow-sm lg:mt-1">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/80">
                                Rankings
                            </p>
                            <p class="mt-1 text-sm font-medium text-brand-secondary">
                                Sincronización manual
                            </p>
                        </div>

                        <form method="POST" action="{{ route('leaderboard.sync') }}" data-sync-loader-form>
                            @csrf
                            <button
                                type="submit"
                                data-sync-loader-button
                                data-sync-loader-default="Actualizar"
                                data-sync-loader-loading="Actualizando..."
                                class="inline-flex cursor-pointer items-center gap-2 rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-300 ease-out hover:-translate-y-1 hover:scale-[1.02] hover:shadow-[0_16px_30px_rgba(15,23,42,0.14)]"
                            >
                                <svg data-sync-loader-icon xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356m-1.636 10.26a9 9 0 11-2.867-9.668L21 9.348" />
                                </svg>
                                <span data-sync-loader-label>Actualizar</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="mt-8 grid gap-6">
                <section class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-sm md:p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div class="max-w-2xl">
                            <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">
                                Operativa
                            </span>

                            <h3 class="mt-3 text-2xl font-semibold text-brand-secondary">
                                {{ html_entity_decode('Herramientas de gesti&oacute;n') }}
                            </h3>

                                <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                                Accesos directos para crear, editar y mantener la estructura interna del portal.
                            </p>
                        </div>

                        <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60 shadow-sm">
                            Acción principal
                        </span>
                    </div>

                    <div class="mt-6 grid gap-4 grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 xl:grid-cols-6">
                        @foreach ($management as $section)
                            <div class="flex flex-col items-center rounded-[1.25rem] px-4 py-5 text-center">
                                <a
                                    href="{{ route($section['route']) }}"
                                    aria-label="{{ $compactLabel($section) }}"
                                    class="flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-primary/10 text-brand-primary ring-1 ring-brand-primary/10 transition duration-300 ease-out hover:-translate-y-1.5 hover:scale-[1.04] hover:bg-brand-primary/15 hover:shadow-[0_18px_34px_rgba(15,23,42,0.12)]"
                                >
                                    {!! $groupIcon($section) !!}
                                </a>

                                <span class="mt-3 text-xs font-semibold leading-4 text-brand-secondary">
                                    {{ $compactLabel($section) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-sm md:p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div class="max-w-2xl">
                            <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary">
                                Auditoría
                            </span>

                            <h3 class="mt-3 text-2xl font-semibold text-brand-secondary">
                                Logs y trazabilidad
                            </h3>

                            <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                                Consulta el historial de cambios con accesos pequeños y directos, tipo launcher.
                            </p>
                        </div>

                        <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60 shadow-sm">
                            Acceso rápido
                        </span>
                    </div>

                    <div class="mt-6 grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-5">
                        @foreach ($logs as $section)
                            <div class="flex flex-col items-center rounded-[1.5rem] px-4 py-5 text-center">
                                <a
                                    href="{{ route($section['route']) }}"
                                    aria-label="{{ $compactLabel($section) }}"
                                    class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-brand-primary ring-1 ring-slate-200 transition duration-300 ease-out hover:-translate-y-1.5 hover:scale-[1.04] hover:bg-brand-primary/10 hover:ring-brand-primary/20 hover:shadow-[0_18px_34px_rgba(15,23,42,0.10)]"
                                >
                                    {!! $groupIcon($section) !!}
                                </a>

                                <span class="mt-4 text-sm font-semibold leading-5 text-brand-secondary">
                                    {{ $compactLabel($section) }}
                                </span>

                                <span class="mt-2 text-xs font-medium uppercase tracking-[0.14em] text-brand-secondary/45">
                                    Ver log
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </section>

    </main>

    <div
        id="leaderboard-sync-loader"
        class="pointer-events-none fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/45 px-6 py-8 opacity-0 backdrop-blur-sm transition-opacity duration-200"
    >
        <div class="w-full max-w-md rounded-[2rem] border border-white/60 bg-white/95 p-7 text-center shadow-[0_30px_80px_rgba(15,23,42,0.22)]">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[radial-gradient(circle_at_top,rgba(239,68,68,0.18),rgba(255,255,255,0.95))] ring-1 ring-brand-primary/10">
                <div class="h-8 w-8 animate-spin rounded-full border-[3px] border-brand-primary/20 border-t-brand-primary"></div>
            </div>
            <h2 class="mt-5 text-xl font-semibold text-brand-secondary">Recargando rankings</h2>
            <p class="mt-2 text-sm leading-6 text-brand-secondary/70">
                Estamos sincronizando Salesforce y actualizando los datos. Esta pantalla se cerrará sola al terminar.
            </p>
        </div>
    </div>

    <script>
        (() => {
            const overlay = document.getElementById('leaderboard-sync-loader');

            document.querySelectorAll('[data-sync-loader-form]').forEach((form) => {
                let submitted = false;

                form.addEventListener('submit', (event) => {
                    if (submitted) {
                        return;
                    }

                    submitted = true;
                    event.preventDefault();

                    const button = form.querySelector('[data-sync-loader-button]');
                    const label = form.querySelector('[data-sync-loader-label]');
                    const icon = form.querySelector('[data-sync-loader-icon]');

                    if (button) {
                        button.disabled = true;
                        button.classList.add('opacity-90');
                    }

                    if (label && button?.dataset.syncLoaderLoading) {
                        label.textContent = button.dataset.syncLoaderLoading;
                    }

                    if (icon) {
                        icon.classList.add('animate-spin');
                    }

                    if (overlay) {
                        overlay.classList.remove('hidden');

                        requestAnimationFrame(() => {
                            overlay.classList.remove('pointer-events-none', 'opacity-0');
                            overlay.classList.add('flex', 'opacity-100');
                        });
                    }

                    window.setTimeout(() => form.submit(), 80);
                });
            });
        })();
    </script>
@endsection
