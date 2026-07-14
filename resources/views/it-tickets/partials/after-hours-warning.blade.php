@php
    $mode = $mode ?? 'create';
    $isPreviewMode = $mode === 'preview';
@endphp

<div
    x-cloak
    x-show="showAfterHoursDialog"
    x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/70 p-4"
    @click.self="{{ $isPreviewMode ? 'showAfterHoursDialog = false' : 'cancelAfterHours()' }}"
    @keydown.escape.window="{{ $isPreviewMode ? 'showAfterHoursDialog = false' : 'cancelAfterHours()' }}"
    style="display: none;"
>
    <div
        x-transition.scale.origin.center
        class="mx-auto w-full max-w-2xl overflow-hidden rounded-[2rem] border border-brand-secondary/10 bg-white shadow-2xl"
    >
        <div class="border-b border-brand-secondary/10 bg-amber-50/70 px-6 py-5 sm:px-7">
            <div class="flex items-start gap-4">
                <div class="mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 9v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        <path d="M12 17h.01" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" />
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700/80">
                        Horario de verano
                    </p>
                    <h2 class="mt-1 text-lg font-semibold text-brand-secondary sm:text-xl">
                        {{ $isPreviewMode ? 'Aviso antes de abrir una incidencia' : 'Antes de enviar tu incidencia' }}
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-brand-secondary/70">
                        Durante el horario de verano, a partir de las 15:00 el equipo de IT no está completo.
                        Si tu incidencia es una urgencia extrema, habla primero con tu responsable y, si lo considera necesario,
                        que contacte por teléfono con Carlos Torres.
                    </p>

                    <p class="mt-3 text-sm leading-6 text-brand-secondary/70">
                        Si no se trata de una urgencia crítica, puedes continuar con normalidad y la revisaremos en cuanto haya personal disponible.
                    </p>

                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                        @if ($isPreviewMode)
                            <button
                                type="button"
                                @click="showAfterHoursDialog = false"
                                class="inline-flex cursor-pointer items-center justify-center rounded-2xl border border-brand-secondary/15 bg-white px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:border-brand-primary hover:text-brand-primary"
                            >
                                Cerrar
                            </button>

                            <a
                                href="{{ route('it-tickets.create') }}"
                                class="inline-flex items-center justify-center rounded-2xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                            >
                                Ir a crear incidencia
                            </a>
                        @else
                            <button
                                type="button"
                                @click="cancelAfterHours()"
                                class="inline-flex cursor-pointer items-center justify-center rounded-2xl border border-brand-secondary/15 bg-white px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:border-brand-primary hover:text-brand-primary"
                            >
                                Cancelar
                            </button>

                            <button
                                type="button"
                                @click="confirmAfterHours()"
                                class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                            >
                                Entendido, continuar
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
