@extends('layouts.app')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-8 lg:px-8">
        <section class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="max-w-3xl">
                <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary">
                    Foro
                </span>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-brand-secondary md:text-4xl">Crear tag</h1>
                <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                    Define el nombre y el color del nuevo tag. La vista previa se actualiza al momento.
                </p>
            </div>

            <div class="mt-8">
                @include('admin.forum-tags._form', [
                    'action' => route('admin.forum-tags.store'),
                    'method' => 'POST',
                    'submitLabel' => 'Crear tag',
                ])
            </div>
        </section>
    </main>
@endsection
