@php
    $selectedIds = collect($getState() ?? [])->map(fn ($id): int => (int) $id)->values();
    $selectedUsers = \App\Models\User::query()
        ->whereKey($selectedIds->all())
        ->orderBy('name')
        ->get();
@endphp

<style>
    .hr-chat-group-members { width: 100%; padding: 1rem; border: 1px solid #303036; border-radius: .625rem; background: #0f0f11; box-shadow: 0 1px 2px rgb(0 0 0 / 18%); }
    .hr-chat-group-members__heading { margin: 0; color: #fff; font-size: .875rem; font-weight: 600; line-height: 1.25rem; }
    .hr-chat-group-members__count { color: #a1a1aa; font-weight: 500; }
    .hr-chat-group-members__description { margin: .35rem 0 1rem; color: #a1a1aa; font-size: .75rem; line-height: 1rem; }
    .hr-chat-group-members__list { display: grid; grid-template-columns: minmax(0, 1fr); gap: .625rem; }
    .hr-chat-group-member { display: flex; min-height: 2.5rem; width: 100%; align-items: center; justify-content: space-between; gap: .75rem; padding: .5rem .75rem; border: 1px solid #303036; border-radius: .5rem; background: #18181b; box-shadow: 0 1px 2px rgb(0 0 0 / 12%); }
    .hr-chat-group-member__name { min-width: 0; overflow: hidden; color: #fff; font-size: .875rem; font-weight: 600; line-height: 1.25rem; text-overflow: ellipsis; white-space: nowrap; }
    .hr-chat-group-member__remove { display: inline-flex; height: 28px; width: 28px; align-items: center; justify-content: center; padding: 0; border: 0; border-radius: 6px; background: transparent; box-shadow: none; color: #d4d4d8; cursor: pointer; line-height: 0; overflow: visible; }
    .hr-chat-group-member__remove:hover { border: 0; background: transparent; box-shadow: none; color: #d4d4d8; }
    .hr-chat-group-member__remove:hover svg { color: #e51a2e; }
    .hr-chat-group-member__remove:focus-visible { border: 0; outline: 1px solid #71717a; outline-offset: 2px; box-shadow: none; }
    .hr-chat-group-members__empty { padding: .75rem 1rem; border: 1px dashed #303036; border-radius: .5rem; color: #a1a1aa; font-size: .875rem; background: #151517; }
    @media (min-width: 1024px) { .hr-chat-group-members__list { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div x-data="{ selected: $wire.$entangle(@js($getStatePath())).live }" class="hr-chat-group-members">
    <div class="mb-3 flex items-center justify-between gap-3">
        <div>
            <h3 class="hr-chat-group-members__heading">
                Usuarios seleccionados <span class="hr-chat-group-members__count">({{ $selectedUsers->count() }})</span>
            </h3>
            <p class="hr-chat-group-members__description">Estos usuarios formarán parte del grupo.</p>
        </div>
    </div>

    @if ($selectedUsers->isEmpty())
        <div class="hr-chat-group-members__empty">
            Todavía no hay usuarios seleccionados.
        </div>
    @else
        <div class="hr-chat-group-members__list">
            @foreach ($selectedUsers as $user)
                <div
                    wire:key="chat-group-selected-user-{{ $user->id }}"
                    x-show="selected.map(String).includes('{{ $user->id }}')"
                    class="hr-chat-group-member"
                >
                    <span class="hr-chat-group-member__name">{{ $user->name }}</span>
                    <button
                        type="button"
                        title="Quitar usuario"
                        aria-label="Quitar usuario {{ $user->name }}"
                        x-on:click="selected = selected.filter(id => String(id) !== '{{ $user->id }}')"
                        class="hr-chat-group-member__remove"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="display: block; width: 16px; height: 16px; flex: 0 0 16px; overflow: visible;">
                            <line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <line x1="18" y1="6" x2="6" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>
