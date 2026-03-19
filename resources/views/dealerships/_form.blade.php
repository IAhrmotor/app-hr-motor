@php
    $dealership ??= null;
@endphp

<div class="grid gap-6 md:grid-cols-2">
    <div>
        <label for="name" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Nombre</label>
        <input id="name" name="name" type="text" value="{{ old('name', $dealership?->name) }}" required
            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
    </div>

    <div>
        <label for="salesforce_id" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">ID de Salesforce</label>
        <input id="salesforce_id" name="salesforce_id" type="text" value="{{ old('salesforce_id', $dealership?->salesforce_id) }}" required
            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
    </div>
</div>

<div class="grid gap-6 md:grid-cols-2">
    <div>
        <label for="phone" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Telefono</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $dealership?->phone) }}" required
            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
    </div>

    <div>
        <label for="google_maps_url" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">URL de Google Maps</label>
        <input id="google_maps_url" name="google_maps_url" type="url" value="{{ old('google_maps_url', $dealership?->google_maps_url) }}" required
            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
    </div>
</div>

<div class="grid gap-6 md:grid-cols-2">
    <div>
        <label for="reviews_url" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">URL de resenas</label>
        <input id="reviews_url" name="reviews_url" type="url" value="{{ old('reviews_url', $dealership?->reviews_url) }}" required
            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
    </div>
</div>

<div class="grid gap-6 md:grid-cols-[220px_minmax(0,1fr)]">
    <div>
        <label for="image" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Imagen</label>
        <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp" @if (! $dealership?->image_path) required @endif
            class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm text-brand-secondary outline-none transition file:mr-4 file:rounded-xl file:border-0 file:bg-brand-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:opacity-90 focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
        <p class="mt-2 pl-2 text-xs text-brand-secondary/60">Formatos permitidos: JPG, PNG o WEBP hasta 2 MB.</p>
    </div>

    <div class="rounded-2xl border border-brand-secondary/10 bg-slate-50 p-4">
        <p class="text-sm font-medium text-brand-secondary">Vista previa actual</p>
        <div class="mt-4 flex items-center gap-4">
            @if ($dealership?->image_url)
                <img src="{{ $dealership->image_url }}" alt="Imagen de {{ $dealership->name }}"
                    class="h-20 w-20 rounded-2xl object-cover ring-1 ring-brand-secondary/10">
            @else
                <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-brand-secondary text-xl font-semibold text-white">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(old('name', $dealership?->name ?? 'DE'), 0, 2)) }}
                </div>
            @endif

            <div class="text-sm text-brand-secondary/70">
                <p class="font-semibold text-brand-secondary">{{ old('name', $dealership?->name ?? 'Nueva delegacion') }}</p>
                <p>{{ old('phone', $dealership?->phone ?? 'Telefono pendiente') }}</p>
            </div>
        </div>
    </div>
</div>
