@php
    $contact = $contact ?? null;
@endphp

<form method="POST" action="{{ $action }}" class="space-y-8">
    @csrf
    @if (! empty($method) && strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="name" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Nombre</label>
            <input id="name" name="name" type="text" value="{{ old('name', $contact->name ?? '') }}" required
                class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
        </div>

        <div>
            <label for="email" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email', $contact->email ?? '') }}"
                class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="phone" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Teléfono</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone', $contact->phone ?? '') }}"
                class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
        </div>

        <div>
            <label for="enreach_extension" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Extensión Enreach</label>
            <input id="enreach_extension" name="enreach_extension" type="text" value="{{ old('enreach_extension', $contact->enreach_extension ?? '') }}"
                class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
            <p class="mt-2 pl-2 text-xs text-brand-secondary/60">Si existe, también debe ser única.</p>
        </div>
    </div>

    <div class="flex items-center justify-between gap-4">
        <a href="{{ route('admin.contacts.index') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Volver</a>
        <input type="submit" value="{{ $submitLabel ?? 'Guardar contacto' }}" class="cursor-pointer rounded-xl bg-brand-primary px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90" />
    </div>
</form>
