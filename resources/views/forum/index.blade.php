@extends('layouts.app')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <section class="overflow-hidden rounded-[2rem] border border-brand-secondary/10 bg-white shadow-sm">
            <div class="border-b border-brand-secondary/10 bg-brand-secondary px-6 py-8 text-white sm:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.28em] text-white/90">
                            Comunidad interna
                        </span>
                        <h1 class="mt-4 max-w-2xl text-3xl font-bold tracking-tight sm:text-4xl">Foro de dudas del equipo comercial</h1>
                        <p class="mt-4 max-w-2xl text-sm leading-6 text-white/80 sm:text-base">
                            Un espacio para abrir consultas, compartir contexto y resolver bloqueos entre compañeros. Los hilos abiertos aparecen primero y los resueltos quedan ordenados después, siempre del más reciente al más antiguo.
                        </p>
                    </div>

                    @if ($canCreateThreads)
                        <a href="{{ route('forum.create') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-slate-100">
                            Crear nuevo hilo
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </a>
                    @endif
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    <article class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/65">Abiertas</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $threadStats['open'] }}</p>
                    </article>
                    <article class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/65">Resueltas</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $threadStats['resolved'] }}</p>
                    </article>
                    <article class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/65">Respuestas</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $threadStats['totalReplies'] }}</p>
                    </article>
                </div>
            </div>

            <div class="px-6 py-6 sm:px-8">
                @if (session('success'))
                    <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
                @endif

                <form method="GET" action="{{ route('forum.index') }}" class="mb-6">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="relative w-full lg:max-w-xl">
                            <div class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-brand-secondary/50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" name="search" value="{{ $search }}"
                                placeholder="Buscar por título, contenido, autor o delegación"
                                class="w-full rounded-2xl border border-gray-300 py-3 pl-11 pr-4 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <div class="relative w-full sm:w-52">
                                <select name="status"
                                    class="w-full cursor-pointer appearance-none rounded-2xl border border-gray-300 bg-white px-4 py-3 pr-11 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                                    <option value="">Todos los estados</option>
                                    <option value="open" @selected($status === 'open')>Abiertos</option>
                                    <option value="resolved" @selected($status === 'resolved')>Resueltos</option>
                                </select>

                                <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-brand-secondary/60">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <button type="submit" class="inline-flex cursor-pointer items-center rounded-xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90">Filtrar</button>
                                @if ($search !== '' || $status)
                                    <a href="{{ route('forum.index') }}" class="inline-flex items-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Limpiar</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>

                <div class="grid gap-4 xl:grid-cols-2">
                    @forelse ($threads as $thread)
                        @php
                            $isOpen = $thread->status === \App\Models\ForumThread::STATUS_OPEN;
                            $lastActivityAt = $thread->latestReply?->created_at ?? $thread->created_at;
                            $isStoreManager = $thread->creator->role === \App\Models\User::ROLE_STORE_MANAGER;
                        @endphp

                        <article class="group overflow-hidden rounded-[1.75rem] border {{ $isOpen ? 'border-brand-primary/15 bg-[linear-gradient(180deg,rgba(229,26,46,0.04),rgba(255,255,255,1))]' : 'border-brand-secondary/10 bg-slate-50/80' }} p-5 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg">
                            <div class="flex flex-col gap-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide {{ $isOpen ? 'bg-brand-primary/10 text-brand-primary' : 'bg-emerald-100 text-emerald-700' }}">
                                                {{ $thread->status_label }}
                                            </span>
                                            <span class="inline-flex rounded-full bg-brand-secondary/5 px-3 py-1 text-xs font-semibold text-brand-secondary/70">
                                                {{ $thread->replies_count }} {{ $thread->replies_count === 1 ? 'respuesta' : 'respuestas' }}
                                            </span>
                                        </div>

                                        <h2 class="mt-3 text-xl font-bold tracking-tight text-brand-secondary">
                                            <a href="{{ route('forum.show', $thread) }}" class="transition group-hover:text-brand-primary">{{ $thread->title }}</a>
                                        </h2>

                                        <p class="mt-3 line-clamp-3 text-sm leading-6 text-brand-secondary/75">
                                            {{ $thread->content }}
                                        </p>
                                    </div>

                                    <a href="{{ route('forum.show', $thread) }}"
                                        class="inline-flex items-center gap-2 rounded-2xl border border-brand-secondary/10 bg-white px-4 py-2 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                                        Ver hilo
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5l6 6m0 0-6 6m6-6h-15" />
                                        </svg>
                                    </a>
                                </div>

                                <div class="grid gap-3 rounded-[1.5rem] border border-brand-secondary/10 bg-white/90 px-4 py-4 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-center">
                                    <a href="{{ route('users.show', $thread->creator) }}" class="shrink-0">
                                        <img src="{{ $thread->creator->avatar_url }}" alt="Avatar de {{ $thread->creator->name }}"
                                            class="h-12 w-12 rounded-2xl object-cover ring-1 ring-brand-secondary/10 transition hover:opacity-90">
                                    </a>

                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('users.show', $thread->creator) }}" class="text-sm font-semibold {{ $isStoreManager ? 'text-amber-700' : 'text-brand-secondary' }} transition hover:text-brand-primary">
                                                {{ $thread->creator->name }}
                                            </a>
                                            @if ($isStoreManager)
                                                <span class="inline-flex rounded-full border border-amber-300/80 bg-[linear-gradient(135deg,#f59e0b_0%,#facc15_55%,#fde68a_100%)] px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-amber-950">
                                                    Jefe de tienda
                                                </span>
                                            @endif
                                        </div>
                                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-brand-secondary/60">
                                            <span>{{ $thread->creator->role_label }}</span>
                                            <span>{{ $thread->creator->resolved_dealership_name ?: 'Sin delegación' }}</span>
                                            <span>Creado {{ $thread->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>

                                    <div class="text-left sm:text-right">
                                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/45">Última actividad</p>
                                        <p class="mt-1 text-sm font-semibold text-brand-secondary">{{ $lastActivityAt->diffForHumans() }}</p>
                                        @if ($thread->latestReply?->author)
                                            <p class="mt-1 text-xs text-brand-secondary/60">Última respuesta de {{ $thread->latestReply->author->name }}</p>
                                        @elseif ($thread->status === \App\Models\ForumThread::STATUS_RESOLVED && $thread->resolver)
                                            <p class="mt-1 text-xs text-brand-secondary/60">Resuelto por {{ $thread->resolver->name }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="xl:col-span-2">
                            <div class="rounded-[1.75rem] border border-dashed border-brand-secondary/20 bg-slate-50 px-6 py-12 text-center">
                                <h2 class="text-xl font-bold tracking-tight text-brand-secondary">No hay hilos que coincidan con tu filtro</h2>
                                <p class="mt-2 text-sm text-brand-secondary/70">Prueba con otra búsqueda o abre una nueva consulta si necesitas ayuda.</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                @if ($threads->hasPages())
                    <div class="mt-6">{{ $threads->links() }}</div>
                @endif
            </div>
        </section>
    </main>
@endsection
