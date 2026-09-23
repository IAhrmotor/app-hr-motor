<?php

namespace App\Filament\Resources\ChatGroups\Pages;

use App\Filament\Resources\ChatGroups\ChatGroupResource;
use App\Models\CompanyChatGroupActivityLog;
use App\Models\User;
use App\Services\CompanyChatGroupActivityLogFormatter;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListChatGroupLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = ChatGroupResource::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $breadcrumb = 'Logs de grupos';

    protected static ?string $title = 'Logs de grupos';

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
            ->query(fn (): Builder => CompanyChatGroupActivityLog::query())
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
                                CompanyChatGroupActivityLog::ACTION_CREATED => 'Creación',
                                CompanyChatGroupActivityLog::ACTION_UPDATED => 'Edición',
                                CompanyChatGroupActivityLog::ACTION_DELETED => 'Eliminación',
                            ])
                            ->placeholder('Todas las acciones'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, string $action): Builder => $query->where('action', $action),
                    )),
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
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, string $actorId): Builder => $query->where('actor_user_id', $actorId),
                    )),
                Filter::make('created_at')
                    ->label('Fecha')
                    ->form([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta')->minDate(fn (Get $get): ?string => $get('from') ?: null),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->columns([
                TextColumn::make('created_at')->label('Fecha y hora')->dateTime('d/m/Y H:i:s')->sortable()->grow(false)->width('12rem'),
                TextColumn::make('action_label')
                    ->label('Acción')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Creación' => 'success',
                        'Edición' => 'warning',
                        'Eliminación' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('action', $direction))
                    ->grow(false)
                    ->width('11rem'),
                TextColumn::make('result')
                    ->label('Resultado')
                    ->badge()
                    ->state(fn (CompanyChatGroupActivityLog $record): string => $record->result_label)
                    ->color(fn (string $state): string => match ($state) {
                        'Correcto' => 'success',
                        'Aviso' => 'warning',
                        'Error' => 'danger',
                        default => 'gray',
                    })
                    ->grow(false)
                    ->width('10rem'),
                TextColumn::make('actor_name')
                    ->label('Gestionado por')
                    ->state(fn (CompanyChatGroupActivityLog $record): string => $record->actor_name)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('actor_name', 'like', "%{$search}%"))
                    ->grow(false)
                    ->width('14rem'),
                TextColumn::make('target_name')
                    ->label('Grupo afectado')
                    ->state(fn (CompanyChatGroupActivityLog $record): string => $record->target_name)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('target_name', 'like', "%{$search}%"))
                    ->grow(false)
                    ->width('14rem'),
                TextColumn::make('changes')
                    ->label('Detalle')
                    ->state(fn (CompanyChatGroupActivityLog $record): string => $this->formatChanges($record))
                    ->extraAttributes(['style' => 'white-space: pre-line'])
                    ->wrap()
                    ->grow(true)
                    ->width('32rem'),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    protected function getCsvExportUrl(): string
    {
        return route('admin.chat-group-logs.export', array_filter([
            'action' => data_get($this->tableFilters, 'action.value'),
            'actor' => data_get($this->tableFilters, 'actor.value'),
            'date_from' => data_get($this->tableFilters, 'created_at.from'),
            'date_to' => data_get($this->tableFilters, 'created_at.until'),
        ], static fn (mixed $value): bool => filled($value)));
    }

    protected function formatChanges(CompanyChatGroupActivityLog $record): string
    {
        return app(CompanyChatGroupActivityLogFormatter::class)->format($record);
    }
}
