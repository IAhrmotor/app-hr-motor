<?php

namespace App\Http\Controllers;

use App\Models\ItTicket;
use App\Models\ItTicketMessage;
use App\Models\TicketActivityLog;
use App\Models\User;
use App\Notifications\ItTicketAssignedNotification;
use App\Notifications\ItTicketMessageNotification;
use App\Services\TicketActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketsController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        abort_unless(app_can_access_tickets($request->user()), 403);

        $canManageTickets = app_user_has_admin_permission($request->user(), 'tickets-it.manage');
        $ticketStatuses = $this->ticketStatuses();
        $ticketPriorities = $this->ticketPriorities();
        $managedSection = $canManageTickets ? $this->buildTicketSectionData($request, 'managed', true) : null;
        $assignedSection = $this->buildTicketSectionData($request, 'assigned', false);

        $viewData = [
            'managedSection' => $managedSection,
            'assignedSection' => $assignedSection,
            'ticketStatuses' => $ticketStatuses,
            'ticketPriorities' => $ticketPriorities,
            'assignableUsers' => $this->assignableUsers(),
            'canManageTickets' => $canManageTickets,
        ];

        if ($request->boolean('ajax')) {
            return response()->json([
                'html' => view('tickets.partials.content', $viewData)->render(),
            ]);
        }

        return view('tickets.index', $viewData);
    }

    public function show(Request $request, ItTicket $itTicket): View
    {
        $canManageTickets = app_user_has_admin_permission($request->user(), 'tickets-it.manage');
        $canViewTicket = $canManageTickets
            || $itTicket->user_id === $request->user()->id
            || $itTicket->assigned_to_user_id === $request->user()->id;

        abort_unless($canViewTicket, 403);

        $itTicket->load(['user', 'assignedTo', 'ticketTool', 'messages.author', 'activityLogs.actor']);

        return view('tickets.show', [
            'ticket' => $itTicket,
            'ticketStatuses' => $this->ticketStatuses(),
            'ticketPriorities' => $this->ticketPriorities(),
            'canManageTickets' => $canManageTickets,
            'canCloseTicket' => $this->canCloseTicket($request->user(), $itTicket, $canManageTickets),
            'canReplyToTicket' => $this->canReplyToTicket($request->user(), $itTicket, $canManageTickets),
            'backUrl' => ($canManageTickets || $itTicket->assigned_to_user_id === $request->user()->id)
                ? route('tickets.index')
                : route('it-tickets.index'),
        ]);
    }

    public function assign(Request $request, ItTicket $itTicket): RedirectResponse
    {
        abort_unless(app_user_has_admin_permission($request->user(), 'tickets-it.manage'), 403);

        $validated = $request->validate([
            'priority' => ['required', Rule::in(array_keys($this->ticketPriorities()))],
            'assigned_to_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('is_active', true)
                        ->whereNull('disabled_at')
                        ->where('extra_role', User::ROLE_INFORMATION_TECHNOLOGY);
                }),
            ],
        ]);

        $previousPriority = $itTicket->priority;
        $previousStatus = $itTicket->status;
        $previousAssigneeId = $itTicket->assigned_to_user_id;

        $itTicket->priority = $validated['priority'];
        $itTicket->assigned_to_user_id = (int) $validated['assigned_to_user_id'];

        if ($itTicket->status === 'new') {
            $itTicket->status = 'in_progress';
        }

        $itTicket->save();

        $logger = app(TicketActivityLogger::class);

        if ($previousPriority !== $itTicket->priority) {
            $logger->record(
                $request->user(),
                $itTicket,
                TicketActivityLog::EVENT_PRIORITY_CHANGED,
                'Prioridad cambiada a ' . ($this->ticketPriorities()[$itTicket->priority]['label'] ?? $itTicket->priority),
                [
                    'previous_priority' => $previousPriority,
                    'priority' => $itTicket->priority,
                ]
            );
        }

        if ($previousAssigneeId !== $itTicket->assigned_to_user_id) {
            $assignedUser = User::query()->whereKey($itTicket->assigned_to_user_id)->first();

            $logger->record(
                $request->user(),
                $itTicket,
                TicketActivityLog::EVENT_ASSIGNED,
                'Asignado a ' . ($assignedUser?->name ?? 'Sin asignar'),
                [
                    'previous_assigned_to_user_id' => $previousAssigneeId,
                    'assigned_to_user_id' => $itTicket->assigned_to_user_id,
                ]
            );

            if ($assignedUser) {
                Notification::send($assignedUser, new ItTicketAssignedNotification($itTicket, $request->user()));
            }
        }

        if ($previousStatus !== $itTicket->status) {
            $logger->record(
                $request->user(),
                $itTicket,
                TicketActivityLog::EVENT_STATUS_CHANGED,
                'Estado cambiado a ' . ($this->ticketStatuses()[$itTicket->status]['label'] ?? $itTicket->status),
                [
                    'previous_status' => $previousStatus,
                    'status' => $itTicket->status,
                ]
            );
        }

        return back()->with('success', 'El ticket se ha asignado correctamente.');
    }

    /**
     * @return array{
     *     search: string,
     *     statuses: array<int, string>,
     *     priorities: array<int, string>,
     *     pageName: string,
     *     tickets: \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * }
     */
    private function buildTicketSectionData(Request $request, string $section, bool $managed): array
    {
        $search = trim((string) $request->input($section . '_search', ''));
        $statuses = $this->normalizeSectionFilters($request->input($section . '_status', []));
        $priorities = $this->normalizeSectionFilters($request->input($section . '_priority', []));
        $pageName = $section . '_page';

        $query = $this->orderedTicketsQuery()
            ->with(['user', 'assignedTo', 'ticketTool']);

        if (! $managed) {
            $query->where('assigned_to_user_id', $request->user()->id);
        }

        if ($search !== '') {
            $searchTerm = '%' . $search . '%';

            $query->where(function (Builder $builder) use ($searchTerm, $managed): void {
                $builder->where('number', 'like', $searchTerm)
                    ->orWhereHas('user', function (Builder $userQuery) use ($searchTerm): void {
                        $userQuery->where('name', 'like', $searchTerm)
                            ->orWhere('email', 'like', $searchTerm);
                    });

                if ($managed) {
                    $builder->orWhereHas('assignedTo', function (Builder $assigneeQuery) use ($searchTerm): void {
                        $assigneeQuery->where('name', 'like', $searchTerm)
                            ->orWhere('email', 'like', $searchTerm);
                    });
                }
            });
        }

        if ($statuses !== []) {
            $query->whereIn('status', $statuses);
        }

        if ($priorities !== []) {
            $query->whereIn('priority', $priorities);
        }

        return [
            'search' => $search,
            'statuses' => $statuses,
            'priorities' => $priorities,
            'pageName' => $pageName,
            'tickets' => $query
                ->paginate(10, ['*'], $pageName)
                ->withQueryString(),
        ];
    }

    public function reply(Request $request, ItTicket $itTicket): RedirectResponse
    {
        $canManageTickets = app_user_has_admin_permission($request->user(), 'tickets-it.manage');
        abort_unless($this->canReplyToTicket($request->user(), $itTicket, $canManageTickets), 403);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:3000'],
            'attachments' => ['nullable', 'array', 'max:4'],
            'attachments.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'close_ticket' => ['nullable', 'boolean'],
        ]);

        if ($itTicket->status === 'closed') {
            abort(403);
        }

        $body = trim((string) ($validated['body'] ?? ''));
        $files = collect($request->file('attachments', []));
        $shouldCloseTicket = (bool) ($validated['close_ticket'] ?? false);

        if ($shouldCloseTicket && ! $this->canCloseTicket($request->user(), $itTicket, $canManageTickets)) {
            abort(403);
        }

        if ($body === '' && $files->isEmpty()) {
            return back()->withErrors([
                'body' => 'Escribe un mensaje o adjunta al menos una imagen.',
            ]);
        }

        $attachments = $files
            ->map(function (UploadedFile $file): array {
                $path = $file->storePublicly('it-tickets/messages', 'public');

                return [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                ];
            })
            ->values()
            ->all();

        DB::transaction(function () use ($request, $itTicket, $body, $attachments, $shouldCloseTicket): void {
            $replyingUserIsOwner = $request->user()->id === $itTicket->user_id;
            $previousStatus = $itTicket->status;
            $logger = app(TicketActivityLogger::class);

            $itTicket->messages()->create([
                'user_id' => $request->user()->id,
                'body' => $body !== '' ? $body : null,
                'attachments' => ItTicketMessage::normalizeAttachments($attachments),
            ]);

            $logger->record(
                $request->user(),
                $itTicket,
                TicketActivityLog::EVENT_COMMENT_ADDED,
                'Comentario añadido',
                [
                    'body' => $body !== '' ? $body : null,
                    'attachments_count' => count($attachments),
                ]
            );

            if ($shouldCloseTicket) {
                $itTicket->messages()->create([
                    'user_id' => $request->user()->id,
                    'body' => 'Ticket cerrado por ' . $request->user()->name . '.',
                    'attachments' => [],
                ]);

                $itTicket->status = 'closed';
                $itTicket->save();

                $logger->record(
                    $request->user(),
                    $itTicket,
                    TicketActivityLog::EVENT_CLOSED,
                    'Estado cambiado a Cerrado',
                    [
                        'previous_status' => $previousStatus,
                        'status' => $itTicket->status,
                    ]
                );

                return;
            }

            if ($replyingUserIsOwner) {
                $itTicket->status = 'in_progress';
                $itTicket->save();

                if ($previousStatus !== $itTicket->status) {
                    $logger->record(
                        $request->user(),
                        $itTicket,
                        TicketActivityLog::EVENT_STATUS_CHANGED,
                        'Estado cambiado a ' . ($this->ticketStatuses()[$itTicket->status]['label'] ?? $itTicket->status),
                        [
                            'previous_status' => $previousStatus,
                            'status' => $itTicket->status,
                        ]
                    );
                }

                return;
            }

            if ($request->user()->id !== $itTicket->user_id) {
                $itTicket->status = 'pending_user';
                $itTicket->save();

                if ($previousStatus !== $itTicket->status) {
                    $logger->record(
                        $request->user(),
                        $itTicket,
                        TicketActivityLog::EVENT_STATUS_CHANGED,
                        'Estado cambiado a ' . ($this->ticketStatuses()[$itTicket->status]['label'] ?? $itTicket->status),
                        [
                            'previous_status' => $previousStatus,
                            'status' => $itTicket->status,
                        ]
                    );
                }
            }
        });

        $recipient = $request->user()->id === $itTicket->user_id
            ? $itTicket->assignedTo
            : $itTicket->user;

        if ($recipient && $recipient->id !== $request->user()->id) {
            Notification::send($recipient, new ItTicketMessageNotification(
                $itTicket->fresh(['user', 'assignedTo']),
                $request->user(),
                $body,
                $shouldCloseTicket
            ));
        }

        return back()->with(
            'success',
            $shouldCloseTicket
                ? 'Tu respuesta se ha publicado y el ticket se ha cerrado correctamente.'
                : 'Tu respuesta se ha publicado correctamente.'
        );
    }

    private function orderedTicketsQuery(): Builder
    {
        return ItTicket::query()
            ->orderByRaw('CASE WHEN assigned_to_user_id IS NULL THEN 0 ELSE 1 END')
            ->orderByRaw(
                "CASE status
                    WHEN 'new' THEN 0
                    WHEN 'in_progress' THEN 1
                    WHEN 'pending_user' THEN 2
                    WHEN 'closed' THEN 3
                    ELSE 99
                END"
            )
            ->orderByDesc('updated_at');
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function ticketStatuses(): array
    {
        return [
            'new' => [
                'label' => 'Nuevo',
                'badge' => 'bg-sky-50 text-sky-700 ring-sky-200',
            ],
            'in_progress' => [
                'label' => 'En curso',
                'badge' => 'bg-amber-50 text-amber-700 ring-amber-200',
            ],
            'pending_user' => [
                'label' => 'Pendiente usuario',
                'badge' => 'bg-violet-50 text-violet-700 ring-violet-200',
            ],
            'closed' => [
                'label' => 'Cerrado',
                'badge' => 'bg-slate-100 text-slate-700 ring-slate-200',
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function ticketPriorities(): array
    {
        return [
            'low' => [
                'label' => 'Baja',
                'badge' => 'bg-slate-100 text-slate-700 ring-slate-200',
            ],
            'medium' => [
                'label' => 'Media',
                'badge' => 'bg-blue-50 text-blue-700 ring-blue-200',
            ],
            'high' => [
                'label' => 'Alta',
                'badge' => 'bg-orange-50 text-orange-700 ring-orange-200',
            ],
            'urgent' => [
                'label' => 'Urgente',
                'badge' => 'bg-rose-50 text-rose-700 ring-rose-200',
            ],
        ];
    }

    /**
     * @return array<int, array{id:int,name:string,email:string}>
     */
    private function assignableUsers(): array
    {
        return User::query()
            ->select(['id', 'name', 'email'])
            ->where('is_active', true)
            ->whereNull('disabled_at')
            ->where('extra_role', User::ROLE_INFORMATION_TECHNOLOGY)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->all();
    }

    private function canReplyToTicket(?User $user, ItTicket $ticket, bool $canManageTickets): bool
    {
        if (! $user) {
            return false;
        }

        if ($ticket->status === 'closed') {
            return false;
        }

        return $canManageTickets
            || $ticket->user_id === $user->id
            || $ticket->assigned_to_user_id === $user->id;
    }

    private function canCloseTicket(?User $user, ItTicket $ticket, bool $canManageTickets): bool
    {
        if (! $user || $ticket->status === 'closed') {
            return false;
        }

        return $canManageTickets
            || $ticket->user_id === $user->id
            || $ticket->assigned_to_user_id === $user->id;
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private function normalizeSectionFilters(mixed $value): array
    {
        if (is_string($value)) {
            $value = array_filter(array_map('trim', explode(',', $value)));
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, fn ($item): bool => is_string($item) && $item !== ''));
    }
}
