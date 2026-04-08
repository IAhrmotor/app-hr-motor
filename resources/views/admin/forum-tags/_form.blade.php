@php
    $initialName = old('name', $tag->name ?? '');
    $initialColor = old('color', $tag->color ?? '#1d4ed8');
@endphp

<div x-data="{
        name: @js($initialName),
        color: @js($initialColor),
        randomColor() {
            const hex = Math.floor(Math.random() * 0xffffff).toString(16).padStart(6, '0');
            this.color = `#${hex}`;
        },
    }"
    class="grid gap-6 lg:grid-cols-[minmax(0,1.05fr)_22rem]">
    <form method="POST" action="{{ $action }}" class="space-y-6">
        @csrf
        @if (($method ?? 'POST') !== 'POST')
            @method($method)
        @endif

        <div class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50/80 p-5">
            <label for="name" class="mb-2 block text-sm font-semibold text-brand-secondary">Nombre del tag</label>
            <input id="name" type="text" name="name" x-model="name" value="{{ $initialName }}"
                placeholder="Ej. Duda técnica"
                class="w-full rounded-2xl border border-brand-secondary/15 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
            @error('name')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50/80 p-5">
            <div class="flex items-center justify-between gap-3">
                <label for="color" class="block text-sm font-semibold text-brand-secondary">Color</label>
                <button type="button" @click="randomColor()"
                    class="inline-flex cursor-pointer items-center rounded-xl border border-brand-secondary/15 bg-white px-3 py-2 text-xs font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                    Aleatorio
                </button>
            </div>

            <div class="mt-3 flex items-center gap-3">
                <input id="color" type="color" name="color" x-model="color"
                    class="h-12 w-16 cursor-pointer rounded-2xl border border-brand-secondary/15 bg-white p-1">
                <input type="text" x-model="color" value="{{ $initialColor }}" maxlength="7" spellcheck="false" autocapitalize="off" autocomplete="off"
                    placeholder="#1d4ed8"
                    class="w-full rounded-2xl border border-brand-secondary/15 bg-white px-4 py-3 text-sm font-mono outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
            </div>
            @error('color')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-sm text-brand-secondary/60">Usa un color hexadecimal como <span class="font-mono">#000000</span> o genera uno con el botón aleatorio.</p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.forum-tags.index') }}"
                class="inline-flex items-center justify-center rounded-2xl border border-brand-secondary/15 px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                Cancelar
            </a>
            <button type="submit"
                class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                {{ $submitLabel }}
            </button>
        </div>
    </form>

    <aside class="rounded-[1.75rem] border border-brand-secondary/10 bg-white p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-primary">Vista previa</p>
        <div class="mt-4 rounded-[1.5rem] border border-brand-secondary/10 bg-slate-50 p-4">
            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white shadow-sm"
                :style="`background-color: ${color}`">
                <span class="h-2.5 w-2.5 rounded-full bg-white/70"></span>
                <span x-text="name || 'Nombre del tag'"></span>
            </span>
            <p class="mt-3 text-sm text-brand-secondary/70">
                Así se verá el tag cuando lo uses en el foro.
            </p>
        </div>
    </aside>
</div>
