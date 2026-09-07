{{-- This page reuses the retention UI from the public application. Load its
     Tailwind theme in addition to Filament's own stylesheet. --}}
@vite('resources/css/app.css')

<style>
    /* Reuse Filament's active theme tokens for the legacy interaction markup. */
    .retention-backoffice .bg-white { background-color: var(--fi-section-bg, var(--fi-color-gray-900)); }
    .retention-backoffice .bg-slate-50 { background-color: color-mix(in srgb, var(--fi-section-bg, #111827) 92%, var(--fi-color-gray-500, #6b7280)); }
    .retention-backoffice .text-brand-secondary,
    .retention-backoffice .text-brand-secondary\/70,
    .retention-backoffice .text-brand-secondary\/65,
    .retention-backoffice .text-brand-secondary\/60,
    .retention-backoffice .text-brand-secondary\/45,
    .retention-backoffice .text-brand-secondary\/90 { color: var(--fi-color-gray-100, #f3f4f6) !important; }
    .retention-backoffice .text-brand-primary,
    .retention-backoffice .text-brand-primary\/80 { color: var(--fi-color-gray-400, #9ca3af); }
    .retention-backoffice .border-brand-secondary\/10,
    .retention-backoffice .border-slate-200,
    .retention-backoffice .border-slate-300 { border-color: var(--fi-color-gray-700, #374151); }
    .retention-backoffice input,
    .retention-backoffice select,
    .retention-backoffice textarea { color: var(--fi-color-gray-100, #f3f4f6); background-color: var(--fi-input-background, var(--fi-color-gray-950, #030712)); }
    .retention-backoffice input::placeholder,
    .retention-backoffice textarea::placeholder { color: var(--fi-color-gray-400, #9ca3af); }
    .retention-backoffice .hover\:bg-brand-secondary\/5:hover { background-color: var(--fi-color-gray-800, #1f2937); }
    .retention-backoffice .bg-brand-primary\/5 { background-color: var(--fi-color-gray-800, #1f2937); }
    .retention-backoffice .border-brand-primary\/10 { border-color: var(--fi-color-gray-700, #374151); }
    .retention-backoffice .bg-brand-primary,
    .retention-backoffice .hover\:bg-brand-primary\/95:hover { background-color: var(--fi-color-gray-700, #374151); }
    .retention-backoffice .focus\:border-brand-primary:focus { border-color: var(--fi-color-gray-500, #6b7280); }
    .retention-backoffice .focus\:ring-brand-primary\/10:focus { --tw-ring-color: color-mix(in srgb, var(--fi-color-gray-500, #6b7280) 20%, transparent); }
    .retention-backoffice .text-rose-700,
    .retention-backoffice .text-rose-600 { color: var(--fi-color-gray-300, #d1d5db); }
    .retention-backoffice .bg-rose-50,
    .retention-backoffice .bg-rose-600 { background-color: var(--fi-color-gray-700, #374151); }
    .retention-backoffice .hover\:bg-rose-100:hover,
    .retention-backoffice .hover\:bg-rose-700:hover { background-color: var(--fi-color-gray-600, #4b5563); }
    .retention-backoffice .border-rose-200 { border-color: var(--fi-color-gray-600, #4b5563); }

    /* Flatten the inherited public-page layout. Filament supplies the page
       surface; only interactive controls keep a visible surface here. */
    .retention-backoffice form:not(.retention-modal),
    .retention-backoffice form:not(.retention-modal) > div,
    .retention-backoffice .overflow-hidden.rounded-\[1\.75rem\],
    .retention-backoffice .overflow-hidden.rounded-\[1\.75rem\] > div,
    .retention-backoffice table thead,
    .retention-backoffice table tbody { background: transparent !important; }
    .retention-backoffice form:not(.retention-modal),
    .retention-backoffice form:not(.retention-modal) > div,
    .retention-backoffice .overflow-hidden.rounded-\[1\.75rem\] { border-color: transparent !important; }
    .retention-backoffice form:not(.retention-modal),
    .retention-backoffice .shadow-sm,
    .retention-backoffice .shadow-inner,
    .retention-backoffice .shadow-xl { box-shadow: none !important; }
    .retention-backoffice form:not(.retention-modal) { padding: 0 !important; }
    .retention-backoffice form:not(.retention-modal) > div { padding-left: 0; padding-right: 0; }
    .retention-backoffice .overflow-hidden.rounded-\[1\.75rem\] { overflow: visible; }
    .retention-backoffice .retention-user-options {
        background-color: #111827 !important;
        border-color: var(--fi-color-gray-700, #374151) !important;
        opacity: 1 !important;
        isolation: isolate;
    }
    .retention-backoffice form:has(.retention-user-options) { position: relative; z-index: 2; }
    .retention-backoffice .retention-user-options input { background-color: #030712 !important; }
    .retention-backoffice .retention-modal {
        background-color: var(--fi-section-bg, #111827) !important;
        border: 1px solid var(--fi-color-gray-700, #374151);
        color: var(--fi-color-gray-100, #f3f4f6);
        box-shadow: 0 20px 50px rgb(0 0 0 / 35%) !important;
    }
    .retention-backoffice .retention-modal .text-slate-700 {
        color: var(--fi-color-gray-200, #e5e7eb) !important;
    }
    .retention-backoffice .retention-modal .border-slate-300 {
        border-color: var(--fi-color-gray-600, #4b5563) !important;
    }
    .retention-backoffice .retention-modal .hover\:bg-slate-100:hover {
        background-color: var(--fi-color-gray-800, #1f2937) !important;
    }
    .retention-backoffice table thead {
        background-color: var(--fi-color-gray-800, #1f2937) !important;
        border-bottom: 1px solid var(--fi-color-gray-600, #4b5563);
    }
    .retention-backoffice table thead th {
        color: var(--fi-color-gray-200, #e5e7eb) !important;
        border-right: 1px solid var(--fi-color-gray-700, #374151);
        white-space: nowrap;
    }
    .retention-backoffice table thead th:last-child { border-right: 0; }
    .retention-backoffice table {
        border: 1px solid var(--fi-color-gray-700, #374151);
        border-radius: .75rem;
        border-collapse: separate;
        border-spacing: 0;
        overflow: hidden;
    }
    .retention-backoffice table tbody td {
        border-right: 1px solid var(--fi-color-gray-800, #1f2937);
    }
    .retention-backoffice table tbody td:last-child { border-right: 0; }
    .retention-backoffice table tbody tr:not(:last-child) td {
        border-bottom: 1px solid var(--fi-color-gray-800, #1f2937);
    }
</style>

<x-filament-panels::page>
    @include('admin.chat-retention-holds.index', [
        'backoffice' => true,
        'retentionRoutes' => [
            'index' => 'backoffice.chat-retention-holds.index',
            'conversation.store' => 'backoffice.chat-retention-holds.store',
            'conversation.update' => 'backoffice.chat-retention-holds.update',
            'conversation.destroy' => 'backoffice.chat-retention-holds.destroy',
            'user.store' => 'backoffice.chat-retention-holds.users.store',
            'user.update' => 'backoffice.chat-retention-holds.users.update',
            'user.destroy' => 'backoffice.chat-retention-holds.users.destroy',
        ],
    ])
</x-filament-panels::page>
