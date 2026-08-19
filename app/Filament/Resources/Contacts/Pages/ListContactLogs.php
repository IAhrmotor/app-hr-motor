<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use App\Models\ContentActivityLog;
use App\Models\User;
use Filament\Actions\Action;
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

class ListContactLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = ContactResource::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $breadcrumb = 'Logs de contactos';

    protected static ?string $title = 'Logs de contactos';

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
            ->query(fn (): Builder => ContentActivityLog::query()
                ->where('content_type', ContentActivityLog::CONTENT_TYPE_CONTACT))
            ->defaultSort('created_at', 'desc')
            ->filtersFormMaxHeight('60vh')
            ->toolbarActions([
                Action::make('downloadCsv')
                    ->label('Descargar CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (): string => route('admin.content-logs.export', array_filter([
                        'content_type' => ContentActivityLog::CONTENT_TYPE_CONTACT,
                        'action' => data_get($this->tableFilters, 'action.value'),
                        'actor' => data_get($this->tableFilters, 'actor.value'),
                        'date_from' => data_get($this->tableFilters, 'created_at.from'),
                        'date_to' => data_get($this->tableFilters, 'created_at.until'),
                    ], static fn (mixed $value): bool => filled($value)))),
            ])
            ->filters([
                Filter::make('action')
                    ->label('Acción')
                    ->form([
                        Select::make('value')
                            ->label('Acción')
                            ->options([
                                ContentActivityLog::ACTION_CREATED => 'Alta',
                                ContentActivityLog::ACTION_UPDATED => 'Edición',
                                ContentActivityLog::ACTION_DELETED => 'Eliminación',
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
                    ->state(fn (ContentActivityLog $record): string => $record->actor_name)
                    ->description(fn (ContentActivityLog $record): ?string => $record->actor_email)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search): void {
                            $query->where('actor_name', 'like', "%{$search}%")
                                ->orWhere('actor_email', 'like', "%{$search}%");
                        });
                    })
                    ->grow(false)
                    ->width('14rem'),
                TextColumn::make('target_name')
                    ->label('Contacto afectado')
                    ->state(fn (ContentActivityLog $record): string => $record->target_name)
                    ->description(fn (ContentActivityLog $record): ?string => $record->target_reference)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search): void {
                            $query->where('target_name', 'like', "%{$search}%")
                                ->orWhere('target_reference', 'like', "%{$search}%");
                        });
                    })
                    ->grow(false)
                    ->width('16rem'),
                TextColumn::make('changes')
                    ->label('Detalle')
                    ->state(function (ContentActivityLog $record): string {
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

    protected function formatChanges(ContentActivityLog $record): string
    {
        $changes = $record->changes ?? [];

        if ($changes === []) {
            return match ($record->action) {
                ContentActivityLog::ACTION_CREATED => 'Alta de contacto',
                ContentActivityLog::ACTION_UPDATED => 'Sin cambios adicionales registrados',
                ContentActivityLog::ACTION_DELETED => 'Eliminación de contacto',
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
                    $from ?? 'vacio',
                    $to ?? 'vacio',
                );
            })
            ->implode(' | ');
    }
}
