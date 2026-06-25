<?php

namespace App\Http\Controllers;

use App\Models\NotificationActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminNotificationLogController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $this->sanitizeDate($request->query('date_from'));
        $dateTo = $this->sanitizeDate($request->query('date_to'));
        [$dateFrom, $dateTo] = $this->normalizeDateRange($dateFrom, $dateTo);
        $actorId = $this->sanitizeActorId($request->query('actor'));
        $actors = $this->availableActors();

        if (! Schema::hasTable('notification_activity_logs')) {
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
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'actorId' => $actorId,
                'actors' => $actors,
                'missingTable' => true,
            ]);
        }

        $logs = $this->filteredLogsQuery($dateFrom, $dateTo, $actorId)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return $this->renderIndexResponse($request, [
            'logs' => $logs,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'actorId' => $actorId,
            'actors' => $actors,
            'missingTable' => false,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $dateFrom = $this->sanitizeDate($request->query('date_from'));
        $dateTo = $this->sanitizeDate($request->query('date_to'));
        [$dateFrom, $dateTo] = $this->normalizeDateRange($dateFrom, $dateTo);
        $actorId = $this->sanitizeActorId($request->query('actor'));

        if (! Schema::hasTable('notification_activity_logs')) {
            return response()->streamDownload(function (): void {
                $output = fopen('php://output', 'w');

                fputcsv($output, [
                    'fecha_hora',
                    'gestionado_por',
                    'email_gestor',
                    'titulo',
                    'descripcion',
                    'enlace',
                    'roles_destino',
                    'numero_destinatarios',
                ], ';');

                fclose($output);
            }, 'logs-notificaciones-pendiente-migracion.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $logs = $this->filteredLogsQuery($dateFrom, $dateTo, $actorId)
            ->orderByDesc('created_at')
            ->get();

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "logs-notificaciones-{$timestamp}.csv";

        return response()->streamDownload(function () use ($logs): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'fecha_hora',
                'gestionado_por',
                'email_gestor',
                'titulo',
                'descripcion',
                'enlace',
                'roles_destino',
                'numero_destinatarios',
            ], ';');

            foreach ($logs as $log) {
                fputcsv($output, [
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $log->actor_name,
                    $log->actor_email,
                    $log->title,
                    $log->description,
                    $log->link_url,
                    $this->formatRoles($log->target_roles ?? []),
                    $log->recipient_count,
                ], ';');
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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

    private function filteredLogsQuery(?string $dateFrom, ?string $dateTo, ?int $actorId): Builder
    {
        return NotificationActivityLog::query()
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('created_at', '<=', $dateTo))
            ->when($actorId, fn (Builder $query) => $query->where('actor_user_id', $actorId));
    }

    private function formatRoles(array $roles): string
    {
        $labels = array_merge([
            '__all_users__' => 'Todos los usuarios',
        ], User::roleLabels());

        return collect($roles)
            ->map(fn ($role) => $labels[$role] ?? $role)
            ->implode(' | ');
    }

    private function renderIndexResponse(Request $request, array $data)
    {
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.notification-logs.partials.content', $data)->render(),
            ]);
        }

        return view('admin.notification-logs.index', $data);
    }
}
