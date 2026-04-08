@extends('layouts.app')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-8 lg:px-8">
        <section class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="max-w-3xl">
                <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary">
                    Foro
                </span>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-brand-secondary md:text-4xl">Editar tag</h1>
                <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                    Ajusta el nombre o el color del tag y revisa el resultado antes de guardarlo.
                </p>
            </div>

            <div class="mt-8">
                @include('admin.forum-tags._form', [
                    'tag' => $tag,
                    'action' => route('admin.forum-tags.update', $tag),
                    'method' => 'PUT',
                    'submitLabel' => 'Guardar cambios',
                ])
            </div>

            <form method="POST" action="{{ route('admin.forum-tags.destroy', $tag) }}" class="mt-8">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="inline-flex cursor-pointer items-center justify-center rounded-2xl border border-red-300 bg-red-50 px-5 py-3 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                    Eliminar tag
                </button>
            </form>
        </section>
    </main>
@endsection
