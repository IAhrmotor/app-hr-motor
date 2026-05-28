<?php

namespace App\Http\Controllers;

use App\Models\PolicyAcceptance;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPolicyAcceptanceLogController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $this->sanitizeDate($request->query('date_from'));
        $dateTo = $this->sanitizeDate($request->query('date_to'));
        [$dateFrom, $dateTo] = $this->normalizeDateRange($dateFrom, $dateTo);
        $userId = $this->sanitizeUserId($request->query('user'));
        $users = $this->availableUsers();

        if (! Schema::hasTable('policy_acceptances')) {
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
                'userId' => $userId,
                'users' => $users,
                'missingTable' => true,
            ]);
        }

        $logs = $this->filteredLogsQuery($dateFrom, $dateTo, $userId)
            ->orderByDesc('accepted_at')
            ->paginate(20)
            ->withQueryString();

        return $this->renderIndexResponse($request, [
            'logs' => $logs,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'userId' => $userId,
            'users' => $users,
            'missingTable' => false,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $dateFrom = $this->sanitizeDate($request->query('date_from'));
        $dateTo = $this->sanitizeDate($request->query('date_to'));
        [$dateFrom, $dateTo] = $this->normalizeDateRange($dateFrom, $dateTo);
        $userId = $this->sanitizeUserId($request->query('user'));

        if (! Schema::hasTable('policy_acceptances')) {
            return response()->streamDownload(function (): void {
                $output = fopen('php://output', 'w');

                fputcsv($output, [
                    'fecha_hora',
                    'usuario',
                    'email_usuario',
                    'version_politica',
                    'ip',
                    'user_agent',
                    'source',
                ], ';');

                fclose($output);
            }, 'politica-aceptaciones-pendiente-migracion.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $logs = $this->filteredLogsQuery($dateFrom, $dateTo, $userId)
            ->orderByDesc('accepted_at')
            ->get();

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "politica-aceptaciones-{$timestamp}.csv";

        return response()->streamDownload(function () use ($logs): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'fecha_hora',
                'usuario',
                'email_usuario',
                'version_politica',
                'ip',
                'user_agent',
                'source',
            ], ';');

            foreach ($logs as $log) {
                fputcsv($output, [
                    $log->accepted_at?->format('Y-m-d H:i:s'),
                    $log->user?->name ?? $log->user_email,
                    $log->user_email,
                    $log->policy_version,
                    $log->ip_address,
                    $log->user_agent,
                    $log->source,
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
        return PolicyAcceptance::query()
            ->with('user')
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('accepted_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('accepted_at', '<=', $dateTo))
            ->when($userId, fn (Builder $query) => $query->where('user_id', $userId));
    }

    private function renderIndexResponse(Request $request, array $data)
    {
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.policy-acceptance-logs.partials.content', $data)->render(),
            ]);
        }

        return view('admin.policy-acceptance-logs.index', $data);
    }
}
