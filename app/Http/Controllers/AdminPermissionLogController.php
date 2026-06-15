<?php

namespace App\Http\Controllers;

use App\Models\AdminPermissionActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPermissionLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $action = $this->sanitizeAction($request->query('action'));
        $dateFrom = $this->sanitizeDate($request->query('date_from'));
        $dateTo = $this->sanitizeDate($request->query('date_to'));
        [$dateFrom, $dateTo] = $this->normalizeDateRange($dateFrom, $dateTo);
        $actorId = $this->sanitizeActorId($request->query('actor'));
        $actors = $this->availableActors();

        if (! Schema::hasTable('admin_permission_activity_logs')) {
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
            $missingTable = true;

            return view('admin.permission-logs.index', compact('logs', 'action', 'dateFrom', 'dateTo', 'actorId', 'actors', 'missingTable'));
        }

        $logs = $this->filteredLogsQuery($action, $dateFrom, $dateTo, $actorId)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $missingTable = false;

        return view('admin.permission-logs.index', compact('logs', 'action', 'dateFrom', 'dateTo', 'actorId', 'actors', 'missingTable'));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeAdmin();

        $action = $this->sanitizeAction($request->query('action'));
        $dateFrom = $this->sanitizeDate($request->query('date_from'));
        $dateTo = $this->sanitizeDate($request->query('date_to'));
        [$dateFrom, $dateTo] = $this->normalizeDateRange($dateFrom, $dateTo);
        $actorId = $this->sanitizeActorId($request->query('actor'));

        if (! Schema::hasTable('admin_permission_activity_logs')) {
            return response()->streamDownload(function (): void {
                $output = fopen('php://output', 'w');
                fputcsv($output, ['fecha_hora', 'accion', 'gestionado_por', 'email_gestor', 'tipo', 'objetivo', 'permiso', 'alcance', 'cambios'], ';');
                fclose($output);
            }, 'logs-permisos-pendiente-migracion.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $logs = $this->filteredLogsQuery($action, $dateFrom, $dateTo, $actorId)
            ->orderByDesc('created_at')
            ->get();

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = $action
            ? "logs-permisos-{$action}-{$timestamp}.csv"
            : "logs-permisos-{$timestamp}.csv";

        return response()->streamDownload(function () use ($logs): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, ['fecha_hora', 'accion', 'gestionado_por', 'email_gestor', 'tipo', 'objetivo', 'permiso', 'alcance', 'cambios'], ';');

            foreach ($logs as $log) {
                fputcsv($output, [
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $log->action_label,
                    $log->actor_name,
                    $log->actor_email,
                    $log->target_type,
                    $log->target_name,
                    $log->permission_key,
                    $log->scope,
                    $this->formatChanges($log->changes ?? []),
                ], ';');
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function sanitizeAction(?string $action): ?string
    {
        $allowedActions = [
            AdminPermissionActivityLog::ACTION_GROUP_CREATED,
            AdminPermissionActivityLog::ACTION_GROUP_UPDATED,
            AdminPermissionActivityLog::ACTION_GROUP_DELETED,
            AdminPermissionActivityLog::ACTION_PERMISSION_SYNCED,
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
            ->where('role', User::ROLE_ADMIN)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function filteredLogsQuery(?string $action, ?string $dateFrom, ?string $dateTo, ?int $actorId): Builder
    {
        return AdminPermissionActivityLog::query()
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
            ->map(function (mixed $change, string $field): string {
                $from = is_array($change) ? ($change['from'] ?? null) : null;
                $to = is_array($change) ? ($change['to'] ?? null) : null;

                if (is_array($to)) {
                    $to = implode(', ', array_map('strval', $to));
                }

                return sprintf('%s: "%s" -> "%s"', $field, $from ?? 'vacío', $to ?? 'vacío');
            })
            ->implode(' | ');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(app_user_can_view_admin_logs(auth()->user()), 403);
    }
}
