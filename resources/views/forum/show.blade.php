@extends('layouts.app')

@section('content')
    @php
        $isOpen = $thread->status === \App\Models\ForumThread::STATUS_OPEN;
        $canDelete = $canModerateThread;
        $isThreadCreatorStoreManager = $thread->creator->role === \App\Models\User::ROLE_STORE_MANAGER;
        $threadCreatorProfileUrl = route('users.show', $thread->creator);
    @endphp

    <main x-data="{ isImageOpen: false, imageUrl: '', imageAlt: '' }" x-effect="document.body.classList.toggle('overflow-hidden', isImageOpen)" @keydown.escape.window="isImageOpen = false"
        class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_22rem]">
            <section class="overflow-hidden rounded-[2rem] border border-brand-secondary/10 bg-white shadow-sm">
                <div class="border-b border-brand-secondary/10 px-6 py-6 sm:px-8">
                    <a href="{{ route('forum.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-secondary/65 transition hover:text-brand-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                        Volver al foro
                    </a>

                    <div class="mt-5 flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide {{ $isOpen ? 'bg-brand-primary/10 text-brand-primary' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ $thread->status_label }}
                                </span>
                                <span class="inline-flex rounded-full bg-brand-secondary/5 px-3 py-1 text-xs font-semibold text-brand-secondary/70">
                                    {{ $thread->replies->count() }} {{ $thread->replies->count() === 1 ? 'respuesta' : 'respuestas' }}
                                </span>
                            </div>

                            <h1 class="mt-3 text-3xl font-bold tracking-tight text-brand-secondary">{{ $thread->title }}</h1>

                            @if ($thread->tags->isNotEmpty())
                                <div class="mt-4 flex flex-wrap gap-1.5 sm:gap-2">
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

                            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-brand-secondary/65">
                                <a href="{{ $threadCreatorProfileUrl }}" class="flex items-center gap-3 rounded-2xl border border-brand-secondary/10 bg-slate-50 px-3 py-2 transition hover:border-brand-primary/20 hover:bg-slate-100">
                                    <img src="{{ $thread->creator->avatar_url }}" alt="Avatar de {{ $thread->creator->name }}" class="h-10 w-10 rounded-xl object-cover transition hover:opacity-90">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-semibold {{ $isThreadCreatorStoreManager ? 'text-amber-700' : 'text-brand-secondary' }}">
                                                {{ $thread->creator->name }}
                                            </span>
                                            @if ($isThreadCreatorStoreManager)
                                                <span class="inline-flex rounded-full border border-amber-300/80 bg-[linear-gradient(135deg,#f59e0b_0%,#facc15_55%,#fde68a_100%)] px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-amber-950">
                                                    Jefe de tienda
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs">{{ $thread->creator->role_label }} · {{ $thread->creator->resolved_dealership_name ?: 'Sin delegación' }}</p>
                                    </div>
                                </a>
                                <span>Creado {{ $thread->created_at->translatedFormat('d/m/Y H:i') }}</span>
                                @if ($thread->resolved_at)
                                    <span>Resuelto {{ $thread->resolved_at->translatedFormat('d/m/Y H:i') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="mt-5 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            Revisa el formulario y vuelve a intentarlo.
                        </div>
                    @endif
                </div>

                <div class="px-6 py-6 sm:px-8">
                    <article class="rounded-[1.75rem] border border-brand-secondary/10 bg-[linear-gradient(180deg,rgba(31,41,68,0.02),rgba(255,255,255,1))] p-6">
                        <p class="whitespace-pre-line text-sm leading-7 text-brand-secondary/80">{{ $thread->content }}</p>

                        @if ($thread->attachments->isNotEmpty())
                            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($thread->attachments as $attachment)
                                    <button type="button"
                                        @click="imageUrl = @js($attachment->image_url); imageAlt = @js('Adjunto del hilo'); isImageOpen = true"
                                        class="group relative cursor-pointer overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white text-left shadow-sm transition hover:-translate-y-1 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/40">
                                        <img src="{{ $attachment->image_url }}" alt="Adjunto del hilo"
                                            class="h-52 w-full object-cover transition duration-300 group-hover:scale-105 group-hover:brightness-75">
                                        <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-brand-secondary/0 text-xs font-semibold uppercase tracking-[0.18em] text-white opacity-0 transition duration-300 group-hover:bg-brand-secondary/30 group-hover:opacity-100">
                                            Ver
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </article>

                    <div class="mt-8">
                        <div class="mb-5 flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">Conversación</h2>
                                <p class="mt-1 text-sm text-brand-secondary/70">Las respuestas quedan ordenadas cronológicamente para seguir el hilo con claridad.</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            @forelse ($thread->replies as $reply)
                                @php
                                    $isReplyAuthorStoreManager = $reply->author->role === \App\Models\User::ROLE_STORE_MANAGER;
                                    $replyAuthorProfileUrl = route('users.show', $reply->author);
                                @endphp
                                <article class="rounded-[1.5rem] border border-brand-secondary/10 bg-white p-5 shadow-sm">
                                    <div class="flex items-start gap-4">
                                        <a href="{{ $replyAuthorProfileUrl }}" class="shrink-0">
                                            <img src="{{ $reply->author->avatar_url }}" alt="Avatar de {{ $reply->author->name }}"
                                                class="h-12 w-12 rounded-2xl object-cover ring-1 ring-brand-secondary/10 transition hover:opacity-90">
                                        </a>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                <a href="{{ $replyAuthorProfileUrl }}" class="font-semibold {{ $isReplyAuthorStoreManager ? 'text-amber-700' : 'text-brand-secondary' }} transition hover:text-brand-primary">
                                                    {{ $reply->author->name }}
                                                </a>
                                                <span class="inline-flex rounded-full {{ $isReplyAuthorStoreManager ? 'border border-amber-300/80 bg-[linear-gradient(135deg,#f59e0b_0%,#facc15_55%,#fde68a_100%)] text-amber-950' : 'bg-brand-secondary/5 text-brand-secondary/65' }} px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide">
                                                    {{ $reply->author->role_label }}
                                                </span>
                                                <span class="text-xs text-brand-secondary/50">{{ $reply->created_at->translatedFormat('d/m/Y H:i') }}</span>
                                            </div>
                                            <p class="mt-2 whitespace-pre-line text-sm leading-7 text-brand-secondary/80">{{ $reply->content }}</p>

                                            @if ($reply->attachments->isNotEmpty())
                                                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                                    @foreach ($reply->attachments as $attachment)
                                                        <button type="button"
                                                            @click="imageUrl = @js($attachment->image_url); imageAlt = @js('Adjunto de la respuesta'); isImageOpen = true"
                                                            class="group relative cursor-pointer overflow-hidden rounded-2xl border border-brand-secondary/10 bg-slate-50 text-left shadow-sm transition hover:-translate-y-1 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/40">
                                                            <img src="{{ $attachment->image_url }}" alt="Adjunto de la respuesta"
                                                                class="h-44 w-full object-cover transition duration-300 group-hover:scale-105 group-hover:brightness-75">
                                                            <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-brand-secondary/0 text-xs font-semibold uppercase tracking-[0.18em] text-white opacity-0 transition duration-300 group-hover:bg-brand-secondary/30 group-hover:opacity-100">
                                                                Ver
                                                            </span>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-[1.5rem] border border-dashed border-brand-secondary/20 bg-slate-50 px-6 py-10 text-center">
                                    <h3 class="text-lg font-bold tracking-tight text-brand-secondary">Todavía no hay respuestas</h3>
                                    <p class="mt-2 text-sm text-brand-secondary/70">Sé el primero en ayudar con esta duda.</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="mt-8 rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50/80 p-5">
                            <h3 class="text-lg font-bold tracking-tight text-brand-secondary">Responder al hilo</h3>
                            <p class="mt-1 text-sm text-brand-secondary/70">Aporta una solución, una pista o más contexto para ayudar a cerrarlo.</p>

                            <form method="POST" action="{{ route('forum.reply', $thread) }}" enctype="multipart/form-data" class="mt-4">
                                @csrf
                                <textarea name="content" rows="5" placeholder="Escribe tu respuesta..."
                                    class="w-full rounded-2xl border border-brand-secondary/15 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">{{ old('content') }}</textarea>
                                @error('content')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                <div class="mt-4">
                                    <label for="reply-attachments" class="mb-2 block text-sm font-semibold text-brand-secondary">Imágenes adjuntas</label>
                                    <input id="reply-attachments" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.webp"
                                        class="block w-full cursor-pointer rounded-2xl border border-dashed border-brand-secondary/20 bg-white px-4 py-4 text-sm text-brand-secondary/75 file:mr-4 file:cursor-pointer file:rounded-xl file:border-0 file:bg-brand-primary file:px-4 file:py-2 file:font-semibold file:text-white hover:file:opacity-90">
                                    <p class="mt-2 text-sm text-brand-secondary/60">Puedes adjuntar hasta 4 imágenes en JPG, PNG o WEBP.</p>
                                    @error('attachments')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    @error('attachments.*')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="mt-4 flex justify-end">
                                    <button type="submit" class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                                        Publicar respuesta
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="space-y-6">
                <section class="rounded-[2rem] border border-brand-secondary/10 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-primary">Estado del hilo</p>
                    <h2 class="mt-2 text-xl font-bold tracking-tight text-brand-secondary">Gestión rápida</h2>

                    @if ($canChangeThreadStatus)
                        <div class="mt-5 space-y-3">
                            @if ($isOpen)
                                <form method="POST" action="{{ route('forum.status.update', $thread) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="resolved">
                                    <button type="submit" class="inline-flex w-full cursor-pointer items-center justify-center rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                        Marcar como resuelta
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('forum.status.update', $thread) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="open">
                                    <button type="submit" class="inline-flex w-full cursor-pointer items-center justify-center rounded-2xl bg-brand-secondary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-95">
                                        Reabrir hilo
                                    </button>
                                </form>
                            @endif
                        </div>
                    @else
                        <div class="mt-5 rounded-2xl border border-brand-secondary/10 bg-slate-50 px-4 py-4 text-sm text-brand-secondary/75">
                            Solo la persona que creó este hilo o un gestor/administrador puede cambiar su estado.
                        </div>
                    @endif

                    <div class="mt-5 rounded-[1.5rem] border border-brand-secondary/10 bg-slate-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/50">Resumen</p>
                        <dl class="mt-3 space-y-3 text-sm text-brand-secondary/80">
                            <div class="flex items-center justify-between gap-4">
                                <dt>Estado actual</dt>
                                <dd class="font-semibold">{{ $thread->status_label }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt>Respuestas</dt>
                                <dd class="font-semibold">{{ $thread->replies->count() }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt>Autor</dt>
                                <dd class="font-semibold text-right">{{ $thread->creator->name }}</dd>
                            </div>
                            @if ($thread->resolver)
                                <div class="flex items-center justify-between gap-4">
                                    <dt>Marcado por</dt>
                                    <dd class="font-semibold text-right">{{ $thread->resolver->name }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </section>

                @if ($canDelete)
                    <section class="rounded-[2rem] border border-red-200 bg-red-50 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-red-600">Moderación</p>
                        <h2 class="mt-2 text-xl font-bold tracking-tight text-red-800">Eliminar hilo</h2>
                        <p class="mt-2 text-sm leading-6 text-red-700">
                            Esta acción solo está disponible para gestores y administradores. El hilo y todas sus respuestas se eliminarán.
                        </p>

                        <form method="POST" action="{{ route('forum.destroy', $thread) }}" class="mt-5" onsubmit="return confirm('¿Seguro que quieres eliminar este hilo?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex w-full cursor-pointer items-center justify-center rounded-2xl border border-red-300 bg-white px-4 py-3 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                                Eliminar hilo
                            </button>
                        </form>
                    </section>
                @endif
            </aside>
        </div>
        <div
            x-cloak
            x-show="isImageOpen"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-6 py-8 backdrop-blur-sm"
            @click.self="isImageOpen = false"
        >
            <div class="relative w-full max-w-4xl">
                <button
                    type="button"
                    @click="isImageOpen = false"
                    class="absolute right-3 top-3 z-10 inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white"
                    aria-label="Cerrar imagen ampliada"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 shadow-2xl">
                    <img
                        :src="imageUrl"
                        :alt="imageAlt"
                        class="max-h-[80vh] w-full object-contain bg-slate-900"
                    >
                </div>
            </div>
        </div>
    </main>
@endsection
