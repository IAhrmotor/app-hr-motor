@if ($search !== '')
    <div class="border-b border-slate-200 px-4 py-3">
        <div class="mb-2 flex items-center justify-between">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Resultados</p>
            <a href="{{ route('chat.beta') }}" class="cursor-pointer text-xs font-semibold text-brand-primary hover:underline">Limpiar</a>
        </div>

        <div class="space-y-2">
            @forelse ($people as $person)
                <a href="{{ route('chat.beta', ['recipient' => $person->id]) }}"
                    data-chat-recipient-link
                    class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 transition hover:border-brand-primary/20 hover:shadow-sm">
                    <img src="{{ $person->avatar_url }}" alt="Avatar de {{ $person->name }}" class="h-10 w-10 rounded-2xl object-cover">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-brand-secondary">{{ $person->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $person->chat_role_label }}</p>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-3 py-4 text-sm text-slate-500">
                    Sin resultados.
                </div>
            @endforelse
        </div>
    </div>
@endif
