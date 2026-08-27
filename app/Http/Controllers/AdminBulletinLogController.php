<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Bulletins\BulletinPostResource;
use App\Models\BulletinActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminBulletinLogController extends Controller
{
    public function index()
    {
        return redirect(BulletinPostResource::getUrl('logs'));
    }

    public function export(Request $request): StreamedResponse
    {
        $action = $this->sanitizeAction($request->query('action'));
        $dateFrom = $this->sanitizeDate($request->query('date_from'));
        $dateTo = $this->sanitizeDate($request->query('date_to'));
        [$dateFrom, $dateTo] = $this->normalizeDateRange($dateFrom, $dateTo);
        $actorId = $this->sanitizeActorId($request->query('actor'));

        if (! Schema::hasTable('bulletin_activity_logs')) {
            return response()->streamDownload(function (): void {
                $output = fopen('php://output', 'w');

                fputcsv($output, [
                    'fecha_hora',
                    'accion',
                    'gestionado_por',
                    'email_gestor',
                    'elemento',
                    'referencia',
                    'cambios',
                ], ';');

                fclose($output);
            }, 'logs-tablon-pendiente-migracion.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $logs = BulletinActivityLog::query()
            ->when($action, fn ($query) => $query->where('action', $action))
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->when($actorId, fn ($query) => $query->where('actor_user_id', $actorId))
            ->orderByDesc('created_at')
            ->get();

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = 'logs-tablon';

        if ($action) {
            $filename .= "-{$action}";
        }

        $filename .= "-{$timestamp}.csv";

        return response()->streamDownload(function () use ($logs): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'fecha_hora',
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

    private function sanitizeAction(?string $action): ?string
    {
        $allowedActions = [
            BulletinActivityLog::ACTION_CREATED,
            BulletinActivityLog::ACTION_UPDATED,
            BulletinActivityLog::ACTION_DELETED,
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
}
