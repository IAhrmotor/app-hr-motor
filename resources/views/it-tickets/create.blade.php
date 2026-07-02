@extends('layouts.app')

@section('content')
    <main class="mx-auto w-full max-w-7xl flex-1 px-6 py-6">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-[2.5rem] border border-brand-secondary/10 bg-white shadow-sm">
                <div
                    class="relative bg-cover bg-no-repeat px-6 py-8 sm:px-8 sm:py-10"
                    style="background-image: url('{{ asset('images/hero/hero-interior-ticket.webp') }}'); background-position: center 68%;"
                >
                    <div class="absolute inset-0 bg-slate-950/70"></div>
                    <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div class="space-y-3">
                            <div class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-white backdrop-blur-sm">
                                Portal de incidencias
                            </div>
                            <div class="space-y-2">
                                <h1 class="text-3xl font-bold tracking-tight text-white sm:text-5xl">
                                    Crear incidencia
                                </h1>
                                <p class="max-w-3xl text-sm leading-6 text-white/80 sm:text-base">
                                    Describe el problema con calma y deja que IT tenga todo lo necesario para revisarlo sin perder tiempo.
                                </p>
                            </div>
                        </div>

                        <a href="{{ route('it-tickets.index') }}" class="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-brand-secondary shadow-sm transition hover:-translate-y-0.5">
                            Volver a incidencias
                        </a>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(18rem,0.75fr)]">
                <div class="rounded-[2rem] border border-brand-secondary/10 bg-white p-5 shadow-sm sm:p-6">
                    @include('it-tickets.partials.form')
                </div>

                <aside class="space-y-4">
                    <div class="overflow-hidden rounded-[2rem] border border-brand-secondary/10 bg-brand-secondary text-white shadow-sm">
                        <div class="p-5 sm:p-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">
                                Antes de enviar
                            </p>
                            <ul class="mt-4 space-y-3 text-sm leading-6 text-white/80">
                                <li>1. Elige el tipo de incidencia correcto para que llegue al equipo adecuado.</li>
                                <li>2. Marca la prioridad real del problema.</li>
                                <li>3. Añade capturas si ayudan a entender el fallo.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-brand-secondary/10 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-secondary/45">
                            Sugerencia
                        </p>
                        <p class="mt-3 text-sm leading-6 text-brand-secondary/70">
                            Si el problema afecta a varias personas, indica desde el primer momento qué ocurre y a quién impacta. Eso acelera mucho la revisión.
                        </p>
                    </div>
                </aside>
            </section>
        </div>
    </main>
@endsection
