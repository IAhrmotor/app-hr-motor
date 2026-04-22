<div class="grid gap-4 xl:grid-cols-2">
    @forelse ($threads as $thread)
        @php
            $isOpen = $thread->status === \App\Models\ForumThread::STATUS_OPEN;
            $lastActivityAt = $thread->latestReply?->created_at ?? $thread->created_at;
            $isStoreManager = $thread->creator->isStoreManager();
        @endphp

        <article class="group overflow-hidden rounded-[1.75rem] border {{ $isOpen ? 'border-brand-primary/15 bg-[linear-gradient(180deg,rgba(229,26,46,0.04),rgba(255,255,255,1))]' : 'border-brand-secondary/10 bg-slate-50/80' }} p-5 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between sm:gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide {{ $isOpen ? 'bg-brand-primary/10 text-brand-primary' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ $thread->status_label }}
                            </span>
                            <span class="inline-flex rounded-full bg-brand-secondary/5 px-3 py-1 text-xs font-semibold text-brand-secondary/70">
                                {{ $thread->replies_count }} {{ $thread->replies_count === 1 ? 'respuesta' : 'respuestas' }}
                            </span>
                        </div>

                        @if ($thread->tags->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-1.5 sm:gap-2">
                                @foreach ($thread->tags as $tag)
                                    <a href="{{ route('forum.index', ['search' => $tag->name]) }}"
                                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold text-white shadow-sm transition hover:opacity-90 sm:px-3 sm:py-1 sm:text-xs"
                                        style="background-color: {{ $tag->color }}">
                                        <span class="h-2 w-2 rounded-full bg-white/80"></span>
                                        {{ $tag->name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <h2 class="mt-3 text-xl font-bold tracking-tight text-brand-secondary">
                            <a href="{{ route('forum.show', $thread) }}" class="transition group-hover:text-brand-primary">{{ $thread->title }}</a>
                        </h2>

                        <p class="mt-3 line-clamp-3 text-sm leading-6 text-brand-secondary/75">
                            {{ $thread->content }}
                        </p>
                    </div>

                    <a href="{{ route('forum.show', $thread) }}"
                        class="inline-flex self-start items-center gap-2 rounded-2xl border border-brand-secondary/10 bg-white px-4 py-2 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5 sm:self-auto">
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
    <div class="mt-6" data-forum-pagination>{{ $threads->links() }}</div>
@endif
