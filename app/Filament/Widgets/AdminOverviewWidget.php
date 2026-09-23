<?php

namespace App\Filament\Widgets;

use App\Models\CompanyChatGroup;
use App\Models\Dealership;
use App\Models\ItTicket;
use App\Models\User;
use Closure;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminOverviewWidget extends BaseWidget
{
    protected static ?int $sort = -2;

    protected int | string | array $columnSpan = 'full';

    protected int | array | null $columns = [
        '@xl' => 4,
        '@lg' => 2,
        '@sm' => 1,
    ];

    protected ?string $pollingInterval = null;

    protected string $view = 'filament.widgets.admin-overview-widget';

    protected function getStats(): array
    {
        return [
            Stat::make('Usuarios activos', $this->safeCount(
                'active_users',
                fn (): int => User::query()
                    ->where('is_active', true)
                    ->whereNull('disabled_at')
                    ->count(),
            ))
                ->icon('heroicon-o-users'),
            Stat::make('Grupos activos', $this->safeCount(
                'active_groups',
                fn (): int => CompanyChatGroup::query()->count(),
            ))
                ->icon('heroicon-o-user-group'),
            Stat::make('Delegaciones activas', $this->safeCount(
                'active_dealerships',
                fn (): int => Dealership::query()->count(),
            ))
                ->icon('heroicon-o-building-office-2'),
            Stat::make('Incidencias resueltas', $this->safeCount(
                'resolved_tickets',
                fn (): int => ItTicket::query()
                    ->whereIn('status', ['closed', 'clausurado'])
                    ->count(),
            ))
                ->icon('heroicon-o-check-circle'),
        ];
    }

    private function safeCount(string $metric, Closure $query): int | string
    {
        try {
            return $query();
        } catch (Throwable $exception) {
            report($exception);
            Log::error('No se pudo cargar una métrica del dashboard de Filament.', [
                'metric' => $metric,
                'exception' => $exception,
            ]);

            return '—';
        }
    }
}
