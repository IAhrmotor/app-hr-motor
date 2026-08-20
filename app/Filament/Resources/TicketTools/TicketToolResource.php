<?php

namespace App\Filament\Resources\TicketTools;

use App\Filament\Resources\TicketTools\Pages\CreateTicketTool;
use App\Filament\Resources\TicketTools\Pages\EditTicketTool;
use App\Filament\Resources\TicketTools\Pages\ListTicketToolLogs;
use App\Filament\Resources\TicketTools\Pages\ListTicketTools;
use App\Models\TicketTool;
use App\Models\TicketToolActivityLog;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class TicketToolResource extends Resource
{
    protected static ?string $model = TicketTool::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Tipos de incidencia';

    protected static ?string $modelLabel = 'tipo de incidencia';

    protected static ?string $pluralModelLabel = 'tipos de incidencia';

    protected static ?string $slug = 'tipos-de-incidencia';

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return app_user_has_admin_permission(auth()->user(), 'ticket-tools.manage');
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->ordered();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(60),
            ColorPicker::make('color')
                ->label('Color')
                ->hex()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tipo de incidencia')
                    ->prefix(function (TicketTool $record): HtmlString {
                        return new HtmlString(sprintf(
                            '<span aria-hidden="true" style="display:inline-block;width:14px;height:14px;margin-right:12px;border-radius:9999px;background-color:%s;box-shadow:0 1px 2px rgba(0,0,0,.18);vertical-align:middle;"></span>',
                            e($record->color),
                        ));
                    })
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable()
                    ->grow(false)
                    ->width('22rem'),
                TextColumn::make('color')
                    ->label('Código de color')
                    ->searchable()
                    ->sortable()
                    ->grow(false)
                    ->width('12rem')
                    ->formatStateUsing(fn (?string $state): string => $state ? strtoupper($state) : 'Sin color'),
            ])
            ->defaultSort('name')
            ->striped()
            ->toolbarActions([
                Action::make('viewLogs')
                    ->label('Ver logs')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('gray')
                    ->url(static::getUrl('logs'))
                    ->visible(fn (): bool => auth()->user()?->role === User::ROLE_ADMIN),
                CreateAction::make()
                    ->label('Crear tipo')
                    ->url(static::getUrl('create'))
                    ->modal(false),
            ])
            ->actions([
                EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square'),
                DeleteAction::make()
                    ->label('Borrar')
                    ->icon('heroicon-o-trash')
                    ->modalHeading('Borrar tipo de incidencia')
                    ->modalDescription('¿Seguro que quieres borrar este tipo de incidencia? Esta acción no se puede deshacer.')
                    ->using(function (TicketTool $record): bool {
                        $actor = auth()->user();

                        if (! $actor instanceof User) {
                            return false;
                        }

                        static::recordActivity(
                            actor: $actor,
                            action: TicketToolActivityLog::ACTION_DELETED,
                            tool: $record,
                            changes: [
                                'name' => ['from' => $record->name, 'to' => null],
                                'color' => ['from' => $record->color, 'to' => null],
                            ],
                        );

                        return (bool) $record->delete();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTicketTools::route('/'),
            'logs' => ListTicketToolLogs::route('/logs'),
            'create' => CreateTicketTool::route('/create'),
            'edit' => EditTicketTool::route('/{record}/edit'),
        ];
    }

    public static function recordActivity(User $actor, TicketTool $tool, string $action, array $changes): void
    {
        TicketToolActivityLog::query()->create([
            'action' => $action,
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'target_ticket_tool_id' => $tool->id,
            'target_name' => $tool->name,
            'target_color' => $tool->color,
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }
}
