<?php

namespace App\Filament\Resources\Zones\Pages;

use App\Filament\Resources\Zones\ZoneResource;
use App\Models\User;
use App\Models\ZoneActivityLog;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListZoneLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = ZoneResource::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $breadcrumb = 'Logs de zonas';

    protected static ?string $title = 'Logs de zonas';

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
            ->query(fn (): Builder => ZoneActivityLog::query())
            ->defaultSort('created_at', 'desc')
            ->filtersFormMaxHeight('60vh')
            ->toolbarActions([
                Action::make('downloadCsv')
                    ->label('Descargar CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (): string => $this->getCsvExportUrl()),
            ])
            ->filters([
                Filter::make('action')
                    ->label('Acción')
                    ->form([
                        Select::make('value')
                            ->label('Acción')
                            ->options([
                                ZoneActivityLog::ACTION_CREATED => 'Alta',
                                ZoneActivityLog::ACTION_UPDATED => 'Edición',
                                ZoneActivityLog::ACTION_DELETED => 'Eliminación',
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
                    ->label('Gestionado por')
                    ->form([
                        Select::make('value')
                            ->label('Gestionado por')
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
                        default => 'gray',
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('action', $direction);
                    })
                    ->grow(false)
                    ->width('11rem'),
                TextColumn::make('actor_name')
                    ->label('Gestionado por')
                    ->state(fn (ZoneActivityLog $record): string => $record->actor_name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('actor_name', 'like', "%{$search}%");
                    })
                    ->grow(false)
                    ->width('14rem'),
                TextColumn::make('target_name')
                    ->label('Zona afectada')
                    ->state(fn (ZoneActivityLog $record): string => $record->target_name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('target_name', 'like', "%{$search}%");
                    })
                    ->grow(false)
                    ->width('14rem'),
                TextColumn::make('changes')
                    ->label('Detalle')
                    ->state(function (ZoneActivityLog $record): string {
                        return $this->formatChanges($record);
                    })
                    ->wrap()
                    ->grow(true)
                    ->width('28rem'),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    protected function getCsvExportUrl(): string
    {
        return route('admin.zone-logs.export', array_filter([
            'action' => data_get($this->tableFilters, 'action.value'),
            'actor' => data_get($this->tableFilters, 'actor.value'),
            'date_from' => data_get($this->tableFilters, 'created_at.from'),
            'date_to' => data_get($this->tableFilters, 'created_at.until'),
        ], static fn (mixed $value): bool => filled($value)));
    }

    protected function formatChanges(ZoneActivityLog $record): string
    {
        $changes = $record->changes ?? [];

        if ($changes === []) {
            return match ($record->action) {
                ZoneActivityLog::ACTION_CREATED => 'Alta de zona',
                ZoneActivityLog::ACTION_UPDATED => 'Sin cambios adicionales registrados',
                ZoneActivityLog::ACTION_DELETED => 'Eliminación de zona',
                default => 'Sin detalles registrados',
            };
        }

        return collect($changes)
            ->map(function (array $change, string $field): string {
                $from = $change['from'] ?? null;
                $to = $change['to'] ?? null;

                return sprintf(
                    '%s: %s -> %s',
                    $field,
                    blank($from) ? 'Vacío' : $from,
                    blank($to) ? 'Vacío' : $to,
                );
            })
            ->implode(' | ');
    }
}
