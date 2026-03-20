@extends('layouts.app')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
            <div class="mb-8">
                <h1 class="text-2xl font-bold tracking-tight text-brand-secondary">Crear delegación</h1>
                <p class="mt-2 text-sm text-brand-secondary/70">Da de alta una nueva delegación con su información de Salesforce, mapas y reseñas.</p>
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

            <form method="POST" action="{{ route('dealerships.store') }}" enctype="multipart/form-data" class="space-y-8">
                @csrf
                @include('dealerships._form')

                <div class="flex items-center justify-between gap-4">
                    <a href="{{ route('dealerships.index') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Volver</a>
                    <input type="submit" value="Guardar delegación" class="cursor-pointer rounded-xl bg-brand-primary px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90" />
                </div>
            </form>
        </section>
    </main>
@endsection
