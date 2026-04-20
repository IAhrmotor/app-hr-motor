@extends('layouts.app')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
            <div class="mb-8">
                <h1 class="text-2xl font-bold tracking-tight text-brand-secondary">Editar contacto</h1>
                <p class="mt-2 text-sm text-brand-secondary/70">Modifica los datos del contacto seleccionado.</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('admin.contacts._form', [
                'contact' => $contact,
                'action' => route('admin.contacts.update', $contact),
                'method' => 'PUT',
                'submitLabel' => 'Guardar cambios',
            ])
        </section>
    </main>
@endsection
