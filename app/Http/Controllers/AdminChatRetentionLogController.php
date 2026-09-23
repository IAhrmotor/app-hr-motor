<?php

namespace App\Http\Controllers;

use App\Models\CompanyChatRetentionLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminChatRetentionLogController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $dateFrom = $this->sanitizeDate($request->query('date_from'));
        $dateTo = $this->sanitizeDate($request->query('date_to'));
        [$dateFrom, $dateTo] = $this->normalizeDateRange($dateFrom, $dateTo);
        $userId = $this->sanitizeUserId($request->query('user'));

        if (! Schema::hasTable('company_chat_retention_logs')) {
            return response()->streamDownload(function (): void {
                $output = fopen('php://output', 'w');

                fputcsv($output, [
                    'fecha_hora',
                    'estado',
                    'mensajes_eliminados',
                    'usuarios_afectados',
                    'errores',
                    'origen',
                    'corte',
                ], ';');

                fclose($output);
            }, 'borrado-chats-pendiente-migracion.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $logs = $this->filteredLogsQuery($dateFrom, $dateTo, $userId)
            ->orderByDesc('executed_at')
            ->get();

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "borrado-chats-{$timestamp}.csv";

        return response()->streamDownload(function () use ($logs): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'fecha_hora',
                'estado',
                'mensajes_eliminados',
                'usuarios_afectados',
                'errores',
                'origen',
                'corte',
            ], ';');

            foreach ($logs as $log) {
                fputcsv($output, [
                    $log->executed_at?->format('Y-m-d H:i:s'),
                    $log->status_label,
                    $log->deleted_count,
                    $this->formatAffectedUsers($log->affected_users ?? []),
                    $log->error_count,
                    $log->source,
                    $log->cutoff?->format('Y-m-d H:i:s'),
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

    private function sanitizeUserId(mixed $userId): ?int
    {
        if (! is_numeric($userId)) {
            return null;
        }

        $userId = (int) $userId;

        return $userId > 0 ? $userId : null;
    }

    private function availableUsers(): Collection
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function filteredLogsQuery(?string $dateFrom, ?string $dateTo, ?int $userId): Builder
    {
        return CompanyChatRetentionLog::query()
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('executed_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('executed_at', '<=', $dateTo))
            ->when($userId, fn (Builder $query) => $query->whereJsonContains('affected_user_ids', $userId));
    }

    private function formatAffectedUsers(array $users): string
    {
        return collect($users)
            ->map(function (mixed $user): string {
                if (is_array($user)) {
                    $name = (string) ($user['name'] ?? 'Usuario');
                    $count = (int) ($user['count'] ?? 0);

                    return $count > 0 ? sprintf('%s (%d)', $name, $count) : $name;
                }

                return (string) $user;
            })
            ->implode(' | ');
    }

}
