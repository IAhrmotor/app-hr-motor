<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Models\UserActivityLog;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListUserLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = UserResource::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $breadcrumb = 'Logs de usuarios';

    protected static ?string $title = 'Logs de usuarios';

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    public function hydrate(): void
    {
        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()?->role === User::ROLE_ADMIN, 403);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => UserActivityLog::query()
                ->where(function (Builder $query): void {
                    $query->where('action', '!=', UserActivityLog::ACTION_UPDATED)
                        ->orWhere(function (Builder $query): void {
                            $query->where('action', UserActivityLog::ACTION_UPDATED)
                                ->whereNotNull('changes');
                        });
                }))
            ->defaultSort('created_at', 'desc')
            ->filtersFormMaxHeight('60vh')
            ->filters([
                Filter::make('action')
                    ->label('Acción')
                    ->form([
                        Select::make('value')
                            ->label('Acción')
                            ->options([
                                UserActivityLog::ACTION_CREATED => 'Altas',
                                UserActivityLog::ACTION_UPDATED => 'Ediciones',
                                UserActivityLog::ACTION_DELETED => 'Eliminaciones',
                                UserActivityLog::ACTION_DISABLED => 'Desactivaciones',
                                UserActivityLog::ACTION_REACTIVATED => 'Reactivaciones',
                            ])
                            ->placeholder('Todas las acciones'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, string $action): Builder => $query->where('action', $action),
                        );
                    }),
                Filter::make('actor')
                    ->label('Gestor')
                    ->form([
                        Select::make('value')
                            ->label('Gestor')
                            ->options(fn (): array => User::query()
                                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_MANAGER])
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->placeholder('Todos los gestores / admin'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, string $actorId): Builder => $query->where('actor_user_id', $actorId),
                        );
                    }),
                Filter::make('created_at')
                    ->label('Fecha')
                    ->form([
                        DatePicker::make('from')
                            ->label('Desde'),
                        DatePicker::make('until')
                            ->label('Hasta')
                            ->minDate(fn (Get $get): ?string => $get('from') ?: null),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha y hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->grow(false)
                    ->width('12rem'),
                TextColumn::make('action_label')
                    ->label('Acción')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Alta' => 'success',
                        'Edición' => 'warning',
                        'Eliminación' => 'danger',
                        'Desactivación' => 'gray',
                        'Reactivación' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('action', $direction);
                    })
                    ->grow(false)
                    ->width('11rem'),
                TextColumn::make('result')
                    ->label('Resultado')
                    ->badge()
                    ->state(fn (UserActivityLog $record): string => $record->result ?: 'success')
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'warning' => 'warning',
                        'error' => 'danger',
                        default => 'gray',
                    })
                    ->description(function (UserActivityLog $record): ?string {
                        $parts = array_filter([
                            $record->reason ? 'Motivo: ' . $record->reason : null,
                            $record->ip_address ? 'IP: ' . $record->ip_address : null,
                            $record->user_agent ? 'UA: ' . $record->user_agent : null,
                        ]);

                        return $parts === [] ? null : implode(' · ', $parts);
                    })
                    ->grow(false)
                    ->width('14rem'),
                TextColumn::make('actor_name')
                    ->label('Gestionado por')
                    ->state(fn (UserActivityLog $record): string => $record->actor_name)
                    ->description(fn (UserActivityLog $record): ?string => $record->actor_email)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search): void {
                            $query->where('actor_name', 'like', "%{$search}%")
                                ->orWhere('actor_email', 'like', "%{$search}%");
                        });
                    })
                    ->grow(true),
                TextColumn::make('target_name')
                    ->label('Usuario afectado')
                    ->state(fn (UserActivityLog $record): string => $record->target_name)
                    ->description(function (UserActivityLog $record): ?string {
                        $parts = array_filter([
                            $record->target_email,
                            $record->target_role ? 'Rol: ' . $record->target_role : null,
                            $record->target_dealership ? 'Delegación: ' . $record->target_dealership : null,
                        ]);

                        return $parts === [] ? null : implode(' · ', $parts);
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search): void {
                            $query->where('target_name', 'like', "%{$search}%")
                                ->orWhere('target_email', 'like', "%{$search}%")
                                ->orWhere('target_role', 'like', "%{$search}%")
                                ->orWhere('target_dealership', 'like', "%{$search}%");
                        });
                    })
                    ->grow(true),
                TextColumn::make('changes')
                    ->label('Detalle')
                    ->state(function (UserActivityLog $record): string {
                        return $this->formatChanges($record);
                    })
                    ->wrap()
                    ->grow(true),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    protected function formatChanges(UserActivityLog $record): string
    {
        $changes = $record->changes ?? [];

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
