<x-filament-panels::page>
    <style>
        .fi-modal-window-ctn:has(> .conversation-access-modal-window) {
            grid-template-rows: 1fr auto 1fr;
        }

        .fi-modal-window-ctn:has(> .conversation-access-modal-window) > .conversation-access-modal-window {
            max-height: calc(100dvh - 2rem);
            overflow-y: auto;
        }

        .conversation-access-messages {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .conversation-access-message-row {
            display: flex;
            width: 100%;
        }

        .conversation-access-message-row.is-left { justify-content: flex-start; }
        .conversation-access-message-row.is-right { justify-content: flex-end; }
        .conversation-access-message-row.is-system { justify-content: center; }

        .conversation-access-message-bubble {
            width: fit-content;
            max-width: 80%;
            min-width: 0;
            padding: 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 1rem;
            background: var(--gray-0, #fff);
            box-shadow: 0 1px 2px rgb(0 0 0 / 5%);
        }

        .conversation-access-message-row.is-right .conversation-access-message-bubble {
            border-color: color-mix(in srgb, var(--primary-200) 80%, transparent);
            background: color-mix(in srgb, var(--primary-50) 75%, transparent);
        }

        .conversation-access-message-row.is-system .conversation-access-message-bubble {
            max-width: 90%;
            border-radius: 999px;
            padding: 0.5rem 1rem;
            background: var(--gray-50);
            color: var(--gray-500);
            text-align: center;
            font-size: 0.75rem;
        }

        .conversation-access-message-bubble.is-deleted {
            border-color: color-mix(in srgb, var(--danger-200) 80%, transparent);
            background: color-mix(in srgb, var(--danger-50) 80%, transparent);
        }

        .dark .conversation-access-message-bubble {
            border-color: var(--gray-700);
            background: var(--gray-900);
        }

        .dark .conversation-access-message-row.is-right .conversation-access-message-bubble {
            border-color: color-mix(in srgb, var(--primary-500) 30%, transparent);
            background: color-mix(in srgb, var(--primary-500) 10%, transparent);
        }

        .dark .conversation-access-message-row.is-system .conversation-access-message-bubble {
            border-color: var(--gray-700);
            background: var(--gray-900);
            color: var(--gray-400);
        }

        .dark .conversation-access-message-bubble.is-deleted {
            border-color: color-mix(in srgb, var(--danger-500) 30%, transparent);
            background: color-mix(in srgb, var(--danger-500) 10%, transparent);
        }

        .conversation-access-message-bubble .message-meta {
            color: #64748b;
            font-size: 0.875rem;
            line-height: 1.25rem;
        }

        .conversation-access-message-bubble .message-sender {
            color: #475569;
            font-weight: 600;
        }

        .conversation-access-message-bubble .message-date {
            color: #94a3b8;
            font-size: 0.75rem;
            font-weight: 400;
        }

        .conversation-access-message-bubble .message-content {
            color: #0f172a;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5rem;
        }

        .conversation-access-message-bubble.is-deleted .message-sender {
            color: #b91c1c;
        }

        .conversation-access-message-bubble.is-deleted .message-content {
            color: #991b1b !important;
        }

        .dark .conversation-access-message-bubble .message-meta {
            color: #94a3b8;
        }

        .dark .conversation-access-message-bubble .message-sender {
            color: #cbd5e1;
        }

        .dark .conversation-access-message-bubble .message-date {
            color: #64748b;
        }

        .dark .conversation-access-message-bubble .message-content {
            color: #f1f5f9;
        }

        .dark .conversation-access-message-bubble.is-deleted .message-sender {
            color: #fca5a5;
        }

        .dark .conversation-access-message-bubble.is-deleted .message-content {
            color: #fecaca !important;
        }

        .conversation-view {
            overflow: hidden !important;
            border-radius: 0.75rem !important;
        }

        .conversation-view .conversation-messages {
            width: 100% !important;
            box-sizing: border-box !important;
            padding-bottom: 1.25rem !important;
        }

        .conversation-view .conversation-pagination {
            width: calc(100% + 3rem) !important;
            box-sizing: border-box !important;
            margin-top: 0 !important;
            margin-right: -1.5rem !important;
            margin-bottom: -1.5rem !important;
            margin-left: -1.5rem !important;
            border: 0 !important;
            border-top: 1px solid rgb(55 65 81) !important;
            border-radius: 0 !important;
            padding: 1rem 1.25rem !important;
            background: inherit !important;
        }

        .dark .conversation-view .conversation-pagination {
            background: inherit !important;
        }

        .conversation-pagination-content {
            width: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.65rem !important;
            text-align: center !important;
        }

        .conversation-pagination-summary {
            width: 100% !important;
            text-align: center !important;
        }

        .conversation-pagination-controls {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.5rem !important;
            width: 100% !important;
            flex-wrap: wrap !important;
        }

        .message-status-icon {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 16px !important;
            height: 16px !important;
            min-width: 16px !important;
            min-height: 16px !important;
            max-width: 16px !important;
            max-height: 16px !important;
            flex: 0 0 16px !important;
            vertical-align: middle !important;
            margin-right: 4px !important;
        }

        .message-status-icon svg,
        .message-status-svg {
            display: block !important;
            width: 14px !important;
            height: 14px !important;
            min-width: 14px !important;
            min-height: 14px !important;
            max-width: 14px !important;
            max-height: 14px !important;
            flex: none !important;
        }

        @media (max-width: 640px) {
            .conversation-access-message-bubble { max-width: 88%; }
        }
    </style>

    <div class="space-y-6">
        <p class="mb-6 max-w-3xl text-sm leading-6 text-gray-500 dark:text-gray-400" style="margin-bottom: 1.5rem;">
            Busca una conversación real por ID, grupo, nombre o email. El contenido permanece bloqueado hasta registrar un motivo.
        </p>

        {{ $this->content }}

        <?php if ($this->contentUnlocked && $this->selectedConversation): ?>
            <x-filament::section class="conversation-view mt-6">
                <x-slot name="heading">Conversación</x-slot>
                <x-slot name="afterHeader">
                    <x-filament::button
                        wire:click="downloadConversationCsv"
                        wire:loading.attr="disabled"
                        wire:target="downloadConversationCsv"
                        icon="heroicon-o-arrow-down-tray"
                        color="gray"
                        size="sm"
                    >
                        Descargar CSV
                    </x-filament::button>
                </x-slot>
                <x-slot name="description">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            {{ $this->selectedConversation->conversation_type_label }}
                        </span>
                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            #{{ $this->selectedConversation->id }}
                        </span>
                        <span class="inline-flex items-center rounded-md bg-success-50 px-2 py-1 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">
                            Acceso justificado y auditado
                        </span>
                    </div>
                    <div class="mt-3 grid gap-2 text-sm text-gray-500 dark:text-gray-400 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-200">Conversación:</span>
                            {{ $this->selectedConversation->retention_hold_target_label ?: 'Sin nombre' }}
                        </div>
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-200">Mensajes:</span>
                            {{ $this->selectedMessages->count() }}
                        </div>
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-200">Último mensaje:</span>
                            {{ $this->selectedConversation->last_message_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                        </div>
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-200">Estado:</span>
                            Acceso autorizado
                        </div>
                    </div>
                </x-slot>

                <div class="conversation-messages max-h-[38rem] overflow-y-auto p-4 sm:p-6">
                    @php
                        $conversation = $this->selectedConversation;
                        $participantIds = $conversation->isGroupConversation()
                            ? $conversation->chatGroup?->participants?->pluck('id')->filter()->unique()->sort()->values() ?? collect()
                            : collect([$conversation->user_one_id, $conversation->user_two_id])->filter()->unique()->sort()->values();
                        $senderSideMap = $participantIds
                            ->values()
                            ->mapWithKeys(fn (int $participantId, int $index): array => [(string) $participantId => $index % 2 === 0 ? 'left' : 'right'])
                            ->all();
                    @endphp

                    <div class="conversation-access-messages">
                        <?php if ($this->selectedMessages->isNotEmpty()): ?>
                        <?php foreach ($this->selectedMessages as $message): ?>
                            @php
                                $isSystemMessage = $message->isSystemMessage();
                                $isDeletedMessage = $message->trashed();
                                $isEditedMessage = $message->edited_at !== null;
                                $messageState = $message->accessMessageState();
                                $messageSide = $senderSideMap[(string) $message->sender_id] ?? 'left';
                                $messageContent = $message->accessMessageContent();
                            @endphp

                            <?php if ($isSystemMessage): ?>
                                <div class="conversation-access-message-row is-system px-2 py-1" data-message-id="{{ $message->id }}" data-message-type="system">
                                    <div class="conversation-access-message-bubble">
                                        <div class="message-meta" data-message-meta>
                                            <span class="message-sender">{{ $message->sender?->name ?? 'Sistema' }}</span>
                                            <span class="mx-1">·</span>
                                            <span class="message-date">{{ $message->created_at?->format('d/m/Y H:i') }}</span>
                                        </div>
                                        <div class="message-content mt-1" data-message-content><?php echo nl2br(e($messageContent)); ?></div>
                                        <?php if ($isEditedMessage): ?>
                                            <div class="mt-1 text-[0.7rem] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                Editado{{ $message->edited_at ? ' · ' . $message->edited_at->format('d/m/Y H:i') : '' }}
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="conversation-access-message-row {{ $messageSide === 'right' ? 'is-right' : 'is-left' }}" data-message-id="{{ $message->id }}" data-message-type="user" data-sender-id="{{ $message->sender_id }}">
                                    <article class="conversation-access-message-bubble {{ $isDeletedMessage ? 'is-deleted' : '' }}">
                                        <header class="message-meta mb-2 flex flex-wrap items-center justify-start gap-x-4 gap-y-1" data-message-meta>
                                            <span class="message-sender {{ $isDeletedMessage ? 'text-danger-700 dark:text-danger-300' : '' }}">
                                                {{ $message->sender?->name ?? 'Usuario eliminado' }}
                                            </span>
                                            <time class="message-date" datetime="{{ $message->created_at?->toIso8601String() }}">
                                                {{ $message->created_at?->format('d/m/Y H:i') }}
                                            </time>
                                            <?php if ($isDeletedMessage): ?>
                                                <span
                                                    class="message-status-icon"
                                                    title="Mensaje eliminado"
                                                    aria-label="Mensaje eliminado"
                                                >
                                                    <x-heroicon-o-trash class="message-status-svg text-red-400" />
                                                </span>
                                            <?php elseif ($isEditedMessage): ?>
                                                <span
                                                    class="message-status-icon"
                                                    title="Mensaje editado"
                                                    aria-label="Mensaje editado"
                                                >
                                                    <x-heroicon-o-pencil-square class="message-status-svg text-amber-400" />
                                                </span>
                                            <?php endif; ?>
                                        </header>

                                        <?php if ($messageState !== 'normal'): ?>
                                            <div class="mb-2 flex items-center gap-1 text-xs font-semibold uppercase tracking-wide {{ $isDeletedMessage ? 'text-danger-700 dark:text-danger-300' : 'text-primary-700 dark:text-primary-300' }}">
                                                <span>{{ $message->accessMessageStateLabel() }}</span>
                                                <?php if ($isEditedMessage && $message->edited_at): ?>
                                                    · {{ $message->edited_at->format('d/m/Y H:i') }}
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($isEditedMessage && ! $isDeletedMessage): ?>
                                            <?php if ($message->revisions->count() > 1): ?>
                                                <div class="mb-2 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-950/40">
                                                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Historial de ediciones</div>
                                                    <div class="space-y-2">
                                                        <?php foreach ($message->revisions as $revision): ?>
                                                            <div class="rounded-md border border-gray-200/80 bg-white/70 p-2 dark:border-gray-700 dark:bg-gray-900/60">
                                                                <div class="text-[0.7rem] font-medium text-gray-500 dark:text-gray-400">
                                                                    Versión anterior · {{ $revision->edited_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                                                                </div>
                                                                <div class="mt-1 whitespace-pre-line break-words text-sm text-gray-600 dark:text-gray-300"><?php echo e($revision->body ?: 'Mensaje sin texto.'); ?></div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php elseif ($message->revisions->count() === 1): ?>
                                                <?php $revision = $message->revisions->first(); ?>
                                                <div class="mb-2">
                                                    <div class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Contenido anterior:</div>
                                                    <div class="whitespace-pre-line break-words rounded-md border border-gray-200 bg-gray-50 p-2 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-950/40 dark:text-gray-300"><?php echo e($revision?->body ?: 'Mensaje sin texto.'); ?></div>
                                                </div>
                                            <?php else: ?>
                                                <div class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                                                    Contenido anterior: <span class="italic">No disponible. Esta edición se realizó antes de activar el historial de versiones.</span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Contenido actual:</div>
                                        <?php endif; ?>

                                        <div class="message-content break-words {{ $isDeletedMessage ? 'text-danger-800 line-through decoration-danger-400 dark:text-danger-200' : '' }}" data-message-content>
                                            <?php echo nl2br(e($messageContent)); ?>
                                        </div>

                                        <?php if (filled($message->attachments)): ?>
                                            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                                {{ count($message->attachments) }} {{ count($message->attachments) === 1 ? 'adjunto' : 'adjuntos' }}
                                            </div>
                                        <?php endif; ?>
                                    </article>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                La conversación no contiene mensajes.
                            </div>
                <?php endif; ?>
                    </div>
                </div>

                <?php if ($this->selectedMessagesTotal > $this->selectedMessagesPerPage): ?>
                    @php
                        $lastMessagesPage = (int) ceil($this->selectedMessagesTotal / $this->selectedMessagesPerPage);
                        $firstMessageNumber = (($this->selectedMessagesPage - 1) * $this->selectedMessagesPerPage) + 1;
                        $lastMessageNumber = min($this->selectedMessagesPage * $this->selectedMessagesPerPage, $this->selectedMessagesTotal);
                    @endphp
                    <footer class="conversation-pagination text-sm text-gray-500 dark:text-gray-400">
                        <div class="conversation-pagination-content flex flex-col items-center justify-center gap-3 text-center">
                            <div class="conversation-pagination-summary">
                                Mensajes {{ $firstMessageNumber }}-{{ $lastMessageNumber }} de {{ $this->selectedMessagesTotal }}
                            </div>
                            <div class="conversation-pagination-controls flex w-full flex-wrap items-center justify-center gap-2">
                            <x-filament::button
                                wire:click="goToMessagesPage({{ $this->selectedMessagesPage - 1 }})"
                                wire:loading.attr="disabled"
                                wire:target="goToMessagesPage"
                                color="gray"
                                size="sm"
                                :disabled="$this->selectedMessagesPage <= 1"
                            >
                                Anteriores
                            </x-filament::button>
                            <span class="px-2 text-xs font-medium">Página {{ $this->selectedMessagesPage }} de {{ $lastMessagesPage }}</span>
                            <x-filament::button
                                wire:click="goToMessagesPage({{ $this->selectedMessagesPage + 1 }})"
                                wire:loading.attr="disabled"
                                wire:target="goToMessagesPage"
                                color="gray"
                                size="sm"
                                :disabled="$this->selectedMessagesPage >= $lastMessagesPage"
                            >
                                Siguientes
                            </x-filament::button>
                            </div>
                        </div>
                    </footer>
                <?php endif; ?>
            </x-filament::section>
        <?php endif; ?>
    </div>
</x-filament-panels::page>
