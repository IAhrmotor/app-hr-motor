<form
    method="POST"
    action="{{ route('it-tickets.store') }}"
    enctype="multipart/form-data"
    class="space-y-5"
    x-data="itTicketCreateForm()"
    x-ref="form"
    @submit="handleSubmit($event)"
>
    @csrf
    @php
        $submissionToken = old('submission_token', (string) \Illuminate\Support\Str::uuid());
    @endphp

    <input type="hidden" name="submission_token" value="{{ $submissionToken }}">
    <input type="hidden" name="after_hours_acknowledged" value="0" x-ref="afterHoursAcknowledged">

    <div
        class="space-y-2"
        x-data="itTicketToolSelector(@js($ticketTools), @js(old('tool', '')), @js(old('tool') ? ($ticketTools[old('tool')]['label'] ?? '') : ''))"
        @click.outside="open = false"
        @keydown.escape.window="open = false"
    >
        <label for="tool-search" class="text-sm font-semibold text-brand-secondary">
            Tipo de incidencia
        </label>

        <input type="hidden" name="tool" :value="selected">

        <div class="relative">
            <input
                id="tool-search"
                type="text"
                x-model="query"
                @focus="open = true"
                @input="open = true; clearSelection()"
                @blur="syncQuery()"
                @keydown.enter.prevent="if (filteredOptions.length) { select(filteredOptions[0][0]); }"
                placeholder="Busca y selecciona un tipo de incidencia"
                autocomplete="off"
                class="w-full rounded-2xl border border-brand-secondary/15 bg-white px-4 py-3 pr-10 text-sm outline-none transition placeholder:text-brand-secondary/35 focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
            >

            <button
                type="button"
                @click="open = !open"
                class="absolute inset-y-0 right-0 flex items-center px-4 text-brand-secondary/45 transition hover:text-brand-secondary"
                aria-label="Abrir selector de tipos de incidencia"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>

            <div
                x-cloak
                x-show="open"
                x-transition
                class="absolute z-20 mt-2 max-h-72 w-full overflow-auto rounded-2xl border border-brand-secondary/15 bg-white shadow-lg"
            >
                <template x-for="option in filteredOptions" :key="option[0]">
                    <button
                        type="button"
                        @click="select(option[0])"
                        class="flex w-full items-start gap-3 border-b border-brand-secondary/5 px-4 py-3 text-left text-sm transition last:border-b-0 hover:bg-slate-50"
                    >
                        <span
                            class="mt-0.5 inline-flex h-2.5 w-2.5 shrink-0 rounded-full"
                            :style="`background-color: ${option[1].color || '#1d4ed8'}`"
                        ></span>
                        <span class="block font-medium text-brand-secondary" x-text="option[1].label"></span>
                    </button>
                </template>

                <div x-show="filteredOptions.length === 0" class="px-4 py-3 text-sm text-brand-secondary/60">
                    No hay coincidencias.
                </div>
            </div>
        </div>

        @error('tool')
            <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-2">
        <label for="priority" class="text-sm font-semibold text-brand-secondary">
            Prioridad
        </label>
        <select id="priority" name="priority" required class="w-full rounded-2xl border border-brand-secondary/15 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
            <option value="" disabled @selected(old('priority') === null || old('priority') === '')>Selecciona una prioridad</option>
            @foreach ($ticketPriorities as $priorityKey => $priority)
                <option value="{{ $priorityKey }}" @selected(old('priority') === $priorityKey)>
                    {{ $priority['label'] }}
                </option>
            @endforeach
        </select>
        @error('priority')
            <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-2">
        <label for="title" class="text-sm font-semibold text-brand-secondary">
            Título
        </label>
        <input id="title" name="title" type="text" value="{{ old('title') }}" required maxlength="120" placeholder="Resume el problema en una frase" class="w-full rounded-2xl border border-brand-secondary/15 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-brand-secondary/35 focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
        @error('title')
            <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-2">
        <label for="description" class="text-sm font-semibold text-brand-secondary">
            Problema
        </label>
        <textarea id="description" name="description" rows="6" required maxlength="5000" placeholder="Describe lo que ocurre, pasos para reproducirlo y el impacto que tiene." class="w-full rounded-2xl border border-brand-secondary/15 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-brand-secondary/35 focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">{{ old('description') }}</textarea>
        @error('description')
            <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-2">
        <label for="screenshots" class="text-sm font-semibold text-brand-secondary">
            Capturas de pantalla
        </label>
        <input
            id="screenshots"
            name="screenshots[]"
            type="file"
            accept="image/*"
            multiple
            x-ref="screenshots"
            class="block w-full cursor-pointer rounded-2xl border border-dashed border-brand-secondary/20 bg-slate-50 px-4 py-3 text-sm text-brand-secondary transition file:mr-4 file:cursor-pointer file:rounded-full file:border-0 file:bg-brand-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:cursor-pointer"
        >
        <p class="text-xs text-brand-secondary/55">
            Puedes adjuntar varias imágenes en PNG, JPG o WEBP.
        </p>
        @error('screenshots')
            <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
        @enderror
        @error('screenshots.*')
            <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <button
        type="submit"
        class="inline-flex w-full items-center justify-center rounded-2xl bg-brand-primary px-5 py-4 text-base font-semibold text-white shadow-[0_18px_30px_rgba(229,26,46,0.18)] transition duration-200 hover:-translate-y-0.5 hover:bg-brand-primary/95 hover:shadow-[0_22px_40px_rgba(229,26,46,0.24)] disabled:cursor-not-allowed disabled:opacity-70"
        :disabled="isSubmitting"
    >
        Preparar incidencia
    </button>

    @error('after_hours_acknowledged')
        <p class="text-sm font-medium text-amber-700">{{ $message }}</p>
    @enderror

    @include('it-tickets.partials.after-hours-warning', ['mode' => 'create'])

    <div
        x-cloak
        x-ref="confirmDialog"
        x-show="showConfirmDialog"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/60 p-4"
        @click.self="cancelWithoutScreenshots()"
        @keydown.escape.window="cancelWithoutScreenshots()"
        style="display: none;"
    >
        <div
            x-transition.scale.origin.center
            class="mx-auto w-full max-w-lg rounded-[2rem] border border-brand-secondary/10 bg-white shadow-2xl"
        >
            <div class="p-6 sm:p-7">
                <div class="flex items-start gap-4">
                    <div class="mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 9v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            <path d="M12 17h.01" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" />
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-lg font-semibold text-brand-secondary">¿Estás seguro de que no quieres adjuntar capturas de pantalla?</h2>
                        <p class="mt-2 text-sm leading-6 text-brand-secondary/70">
                            Las capturas ayudan a IT a entender el problema más rápido. Si no vas a adjuntarlas, puedes continuar igualmente.
                        </p>

                        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                @click="cancelWithoutScreenshots()"
                                class="inline-flex cursor-pointer items-center justify-center rounded-2xl border border-brand-secondary/15 bg-white px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:border-brand-primary hover:text-brand-primary"
                            >
                                No, adjuntar
                            </button>

                            <button
                                type="button"
                                @click="confirmWithoutScreenshots()"
                                class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-70"
                                :disabled="isSubmitting"
                            >
                                Sí, continuar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
