<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Bulletins\BulletinPostResource;
use App\Models\ContentActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminContentLogController extends Controller
{
    public function index(Request $request)
    {
        if ($request->query('content_type') === ContentActivityLog::CONTENT_TYPE_BULLETIN) {
            return redirect()->to($this->bulletinLogsUrl($request));
        }

        $contentType = $this->sanitizeContentType($request->query('content_type'));
        $action = $this->sanitizeAction($request->query('action'));
        $dateFrom = $this->sanitizeDate($request->query('date_from'));
        $dateTo = $this->sanitizeDate($request->query('date_to'));
        [$dateFrom, $dateTo] = $this->normalizeDateRange($dateFrom, $dateTo);
        $actorId = $this->sanitizeActorId($request->query('actor'));
        $actors = $this->availableActors();

        if (! Schema::hasTable('content_activity_logs')) {
            $logs = new LengthAwarePaginator(
                items: new Collection(),
                total: 0,
                perPage: 20,
                currentPage: LengthAwarePaginator::resolveCurrentPage(),
                options: [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'query' => $request->query(),
                ],
            );
            $logs->withQueryString();

            return $this->renderIndexResponse($request, [
                'logs' => $logs,
                'contentType' => $contentType,
                'action' => $action,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'actorId' => $actorId,
                'actors' => $actors,
                'missingTable' => true,
            ]);
        }

        $logs = $this->filteredLogsQuery($contentType, $action, $dateFrom, $dateTo, $actorId)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return $this->renderIndexResponse($request, [
            'logs' => $logs,
            'contentType' => $contentType,
            'action' => $action,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'actorId' => $actorId,
            'actors' => $actors,
            'missingTable' => false,
        ]);
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        if ($request->query('content_type') === ContentActivityLog::CONTENT_TYPE_BULLETIN) {
            return redirect()->to($this->bulletinLogsExportUrl($request));
        }

        $contentType = $this->sanitizeContentType($request->query('content_type'));
        $action = $this->sanitizeAction($request->query('action'));
        $dateFrom = $this->sanitizeDate($request->query('date_from'));
        $dateTo = $this->sanitizeDate($request->query('date_to'));
        [$dateFrom, $dateTo] = $this->normalizeDateRange($dateFrom, $dateTo);
        $actorId = $this->sanitizeActorId($request->query('actor'));

        if (! Schema::hasTable('content_activity_logs')) {
            return response()->streamDownload(function (): void {
                $output = fopen('php://output', 'w');

                fputcsv($output, [
                    'fecha_hora',
                    'tipo_contenido',
                    'accion',
                    'gestionado_por',
                    'email_gestor',
                    'elemento',
                    'referencia',
                    'cambios',
                ], ';');

                fclose($output);
            }, 'logs-contenidos-pendiente-migracion.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $logs = $this->filteredLogsQuery($contentType, $action, $dateFrom, $dateTo, $actorId)
            ->orderByDesc('created_at')
            ->get();

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = 'logs-contenidos';

        if ($contentType) {
            $filename .= "-{$contentType}";
        }

        if ($action) {
            $filename .= "-{$action}";
        }

        $filename .= "-{$timestamp}.csv";

        return response()->streamDownload(function () use ($logs): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'fecha_hora',
                'tipo_contenido',
                'accion',
                'gestionado_por',
                'email_gestor',
                'elemento',
                'referencia',
                'cambios',
            ], ';');

            foreach ($logs as $log) {
                fputcsv($output, [
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $log->content_type_label,
                    $log->action_label,
                    $log->actor_name,
                    $log->actor_email,
                    $log->target_name,
                    $log->target_reference,
                    $this->formatChanges($log->changes ?? []),
                ], ';');
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function sanitizeContentType(?string $contentType): ?string
    {
        $allowedTypes = [
            ContentActivityLog::CONTENT_TYPE_MAGAZINE,
            ContentActivityLog::CONTENT_TYPE_CONTACT,
        ];

        return in_array($contentType, $allowedTypes, true) ? $contentType : null;
    }

    private function sanitizeAction(?string $action): ?string
    {
        $allowedActions = [
            ContentActivityLog::ACTION_CREATED,
            ContentActivityLog::ACTION_UPDATED,
            ContentActivityLog::ACTION_DELETED,
        ];

        return in_array($action, $allowedActions, true) ? $action : null;
    }

    private function sanitizeDate(?string $date): ?string
    {
        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        return $date;
    }

    private function normalizeDateRange(?string $dateFrom, ?string $dateTo): array
    {
        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            return [$dateTo, $dateFrom];
        }

        return [$dateFrom, $dateTo];
    }

    private function sanitizeActorId(mixed $actorId): ?int
    {
        if (! is_numeric($actorId)) {
            return null;
        }

        $actorId = (int) $actorId;

        return $actorId > 0 ? $actorId : null;
    }

    private function availableActors(): Collection
    {
        return User::query()
            ->whereIn('role', ['admin', 'gestor'])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function bulletinLogsUrl(Request $request): string
    {
        $query = array_filter([
            'action' => $this->sanitizeAction($request->query('action')),
            'actor' => $this->sanitizeActorId($request->query('actor')),
            'date_from' => $this->sanitizeDate($request->query('date_from')),
            'date_to' => $this->sanitizeDate($request->query('date_to')),
        ], static fn (mixed $value): bool => filled($value));

        $url = BulletinPostResource::getUrl('logs');

        return $query === [] ? $url : $url . '?' . http_build_query($query);
    }

    private function bulletinLogsExportUrl(Request $request): string
    {
        return route('admin.bulletin-logs.export', array_filter([
            'action' => $this->sanitizeAction($request->query('action')),
            'actor' => $this->sanitizeActorId($request->query('actor')),
            'date_from' => $this->sanitizeDate($request->query('date_from')),
            'date_to' => $this->sanitizeDate($request->query('date_to')),
        ], static fn (mixed $value): bool => filled($value)));
    }

    private function filteredLogsQuery(?string $contentType, ?string $action, ?string $dateFrom, ?string $dateTo, ?int $actorId): Builder
    {
        return ContentActivityLog::query()
            ->when($contentType, fn (Builder $query) => $query->where('content_type', $contentType))
            ->when($action, fn (Builder $query) => $query->where('action', $action))
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('created_at', '<=', $dateTo))
            ->when($actorId, fn (Builder $query) => $query->where('actor_user_id', $actorId));
    }

    private function formatChanges(array $changes): string
    {
        if ($changes === []) {
            return '';
        }

        return collect($changes)
            ->map(function (array $change, string $field): string {
                $from = $change['from'] ?? null;
                $to = $change['to'] ?? null;

                return sprintf(
                    '%s: "%s" -> "%s"',
                    $field,
                    $from ?? 'vacio',
                    $to ?? 'vacio',
                );
            })
            ->implode(' | ');
    }

    private function renderIndexResponse(Request $request, array $data)
    {
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.content-logs.partials.content', $data)->render(),
            ]);
        }

        return view('admin.content-logs.index', $data);
    }
}
