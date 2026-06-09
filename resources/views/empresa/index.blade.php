@extends('layouts.app')

@section('content')
    <div class="relative overflow-hidden bg-slate-50">
        <section
            class="relative isolate overflow-hidden bg-slate-900"
            style="background-image: linear-gradient(180deg, rgba(3, 7, 18, 0.72) 0%, rgba(3, 7, 18, 0.68) 100%), url('{{ asset('images/hero/hero-delegacion.jpeg') }}'); background-size: cover; background-position: center;"
        >
            <div class="absolute inset-0 bg-black/20"></div>
            <div class="relative mx-auto max-w-7xl px-6 py-10 lg:px-8 lg:py-14">
                <div class="max-w-3xl">
                    <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/85 backdrop-blur">
                        Empresa
                    </span>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-white md:text-4xl xl:text-5xl">
                        Quiénes somos HR Motor
                    </h1>
                    <p class="mt-4 max-w-3xl text-sm leading-7 text-white/80 md:text-base">
                        HR Motor es una empresa española especializada en la compra y venta de vehículos de ocasión y segunda mano.
                        Fundada en 1999 por los hermanos Emilio y Javier Hernández en Tudela, la compañía ha crecido de forma sostenida
                        hasta convertirse en uno de los referentes nacionales del sector.
                    </p>
                </div>

                <div class="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-md">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/60">Red nacional</p>
                        <p class="mt-2 text-2xl font-bold text-white">34</p>
                        <p class="mt-1 text-xs leading-5 text-white/75">delegaciones en España</p>
                    </article>
                    <article class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-md">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/60">Equipo</p>
                        <p class="mt-2 text-2xl font-bold text-white">300</p>
                        <p class="mt-1 text-xs leading-5 text-white/75">profesionales aproximadamente</p>
                    </article>
                    <article class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-md">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/60">Trayectoria</p>
                        <p class="mt-2 text-2xl font-bold text-white">100.000+</p>
                        <p class="mt-1 text-xs leading-5 text-white/75">vehículos vendidos</p>
                    </article>
                    <article class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-md">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/60">Control</p>
                        <p class="mt-2 text-2xl font-bold text-white">250</p>
                        <p class="mt-1 text-xs leading-5 text-white/75">puntos de revisión por vehículo</p>
                    </article>
                </div>
            </div>
        </section>

        <main class="relative mx-auto -mt-6 max-w-7xl px-6 pb-14 lg:px-8">
            <section class="grid gap-5 lg:grid-cols-[1.28fr_0.92fr]">
                <article class="rounded-[1.5rem] border border-white/70 bg-white p-5 shadow-[0_20px_50px_rgba(15,23,42,0.08)] lg:p-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-primary">Nuestra forma de trabajar</p>
                    <h2 class="mt-2 text-xl font-semibold tracking-tight text-brand-secondary md:text-2xl">
                        Confianza, transparencia y atención personalizada
                    </h2>
                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-primary">Vehículos revisados</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                La compañía ofrece una amplia selección de coches revisados, reacondicionados y certificados, con procesos
                                orientados a asegurar una experiencia de compra clara y fiable.
                            </p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-primary">Garantía y prueba</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Cada vehículo cuenta con garantía legal de un año y la posibilidad de probarlo durante 15 días o 1.000
                                kilómetros, según las condiciones publicadas por la marca.
                            </p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-primary">Compra y venta</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                HR Motor facilita tanto la compra como la venta de vehículos mediante procesos ágiles, financiación y
                                servicios adaptados a las necesidades de cada cliente.
                            </p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-primary">Evolución</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Su crecimiento refleja el auge del mercado de ocasión en España y su posicionamiento como una compañía de
                                referencia en movilidad de segunda mano.
                            </p>
                        </div>
                    </div>
                </article>

                <aside class="rounded-[1.5rem] border border-slate-200 bg-brand-secondary p-5 text-white shadow-[0_20px_50px_rgba(15,23,42,0.12)] lg:p-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/60">HR Motor</p>
                    <h2 class="mt-2 text-xl font-bold tracking-tight text-white">
                        Una red nacional en expansión
                    </h2>
                    <p class="mt-3 text-sm leading-7 text-white/75">
                        Desde 1999, HR Motor ha mantenido un crecimiento sostenido basado en la confianza, la transparencia y una
                        atención personalizada que acompaña al cliente en cada paso del proceso.
                    </p>

                    <div class="mt-5 space-y-3">
                        <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                            <p class="text-[11px] uppercase tracking-[0.16em] text-white/55">Origen</p>
                            <p class="mt-1 text-sm font-semibold text-white">Tudela, 1999</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                            <p class="text-[11px] uppercase tracking-[0.16em] text-white/55">Especialidad</p>
                            <p class="mt-1 text-sm font-semibold text-white">Vehículos de ocasión y segunda mano</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                            <p class="text-[11px] uppercase tracking-[0.16em] text-white/55">Compromiso</p>
                            <p class="mt-1 text-sm font-semibold text-white">Procesos ágiles y atención cercana</p>
                        </div>
                    </div>
                </aside>
            </section>

            <section class="mt-7 rounded-[1.5rem] border border-white/70 bg-white p-5 shadow-[0_20px_50px_rgba(15,23,42,0.08)] lg:p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-brand-primary">Mapa</p>
                        <h2 class="mt-1 text-xl font-semibold text-brand-secondary">Dónde estamos</h2>
                    </div>
                    <p class="max-w-2xl text-sm leading-6 text-slate-500">
                        Aquí puedes consultar la ubicación de todas las delegaciones.
                    </p>
                </div>

                <div class="mt-4 overflow-hidden rounded-[1.25rem] border border-slate-200 bg-slate-100">
                    @if ($myMapsEmbedUrl)
                        <iframe
                            src="{{ $myMapsEmbedUrl }}"
                            class="h-[28rem] w-full"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen
                        ></iframe>
                    @else
                        <div class="flex min-h-[22rem] flex-col items-center justify-center px-6 py-16 text-center">
                            <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-primary/10 text-brand-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 21s7-4.5 7-11a7 7 0 10-14 0c0 6.5 7 11 7 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M12 10.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-brand-secondary">Mapa pendiente de configurar</h3>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                Cuando me pases el enlace de Google My Maps lo incrustaremos aquí para que toda la red de delegaciones quede
                                visible de un vistazo.
                            </p>
                        </div>
                    @endif
                </div>
            </section>
        </main>
    </div>
@endsection
