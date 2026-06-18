@extends('layouts.app')

@section('content')
    <main class="mx-auto min-h-screen max-w-7xl px-6 py-8 lg:px-8">
        <section class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="max-w-3xl">
                <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary">
                    Tabl&oacute;n
                </span>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-brand-secondary md:text-4xl">Nueva publicaci&oacute;n</h1>
                <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                    Crea un nuevo anuncio para toda la plantilla. Puedes guardarlo como borrador o publicarlo al instante.
                </p>
            </div>

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 shadow-sm">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.tablon.store') }}" enctype="multipart/form-data" class="mt-8 space-y-8">
                @csrf
                @include('admin.tablon._form', ['post' => $post])

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <a href="{{ route('admin.tablon.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-brand-secondary/15 bg-white px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                        Volver
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        Guardar publicaci&oacute;n
                    </button>
                </div>
            </form>
        </section>
    </main>
@endsection
