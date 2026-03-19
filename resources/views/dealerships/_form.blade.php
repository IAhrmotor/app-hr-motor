@php
    $dealership ??= null;
    $initialImageUrl = $dealership?->image_url;
@endphp

<div
    x-data="{
        imagePreview: @js($initialImageUrl),
        imageName: null,
        updateImagePreview(event) {
            const [file] = event.target.files || [];
            this.imageName = file ? file.name : null;

            if (!file) {
                this.imagePreview = @js($initialImageUrl);
                return;
            }

            const reader = new FileReader();
            reader.onload = (loadEvent) => {
                this.imagePreview = loadEvent.target?.result || null;
            };
            reader.readAsDataURL(file);
        }
    }"
    class="space-y-6"
>
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
            <label for="phone" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Teléfono</label>
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
            <label for="reviews_url" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">URL de reseñas</label>
            <input id="reviews_url" name="reviews_url" type="url" value="{{ old('reviews_url', $dealership?->reviews_url) }}" required
                class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-[minmax(0,1fr)_320px]">
        <div>
            <label for="image" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Imagen</label>
            <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp" @if (! $dealership?->image_path) required @endif
                @change="updateImagePreview($event)"
                class="w-full cursor-pointer rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm text-brand-secondary outline-none transition file:mr-4 file:cursor-pointer file:rounded-xl file:border-0 file:bg-brand-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:opacity-90 focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
            <div class="mt-3 rounded-2xl border border-brand-secondary/10 bg-slate-50 px-4 py-3 text-sm text-brand-secondary/70">
                <p class="font-medium text-brand-secondary">Archivo seleccionado</p>
                <p class="mt-1 break-all" x-text="imageName || 'Todavía no has elegido una imagen nueva'"></p>
            </div>
            <p class="mt-2 pl-2 text-xs text-brand-secondary/60">Formatos permitidos: JPG, PNG o WEBP hasta 2 MB.</p>
        </div>

        <div class="rounded-2xl border border-brand-secondary/10 bg-slate-50 p-4">
            <p class="text-sm font-medium text-brand-secondary">Vista previa</p>
            <div class="mt-4 flex items-center gap-4">
                <template x-if="imagePreview">
                    <img :src="imagePreview" alt="Vista previa de la imagen de la delegación"
                        class="h-24 w-24 rounded-2xl object-cover ring-1 ring-brand-secondary/10">
                </template>

                <template x-if="!imagePreview">
                    <div class="flex h-24 w-24 items-center justify-center rounded-2xl bg-brand-secondary text-xl font-semibold text-white">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(old('name', $dealership?->name ?? 'DE'), 0, 2)) }}
                    </div>
                </template>

                <div class="min-w-0 text-sm text-brand-secondary/70">
                    <p class="font-semibold text-brand-secondary">{{ old('name', $dealership?->name ?? 'Nueva delegación') }}</p>
                    <p>{{ old('phone', $dealership?->phone ?? 'Teléfono pendiente') }}</p>
                    <p class="mt-1 truncate" x-text="imageName || 'Sin cambios pendientes'"></p>
                </div>
            </div>
        </div>
    </div>
</div>
