<?php

namespace App\Filament\Resources\ChatGroups;

use App\Filament\Resources\ChatGroups\Pages\CreateChatGroup;
use App\Filament\Resources\ChatGroups\Pages\EditChatGroup;
use App\Filament\Resources\ChatGroups\Pages\ListChatGroupLogs;
use App\Filament\Resources\ChatGroups\Pages\ListChatGroups;
use App\Models\CompanyChatGroup;
use App\Models\User;
use App\Services\CompanyChatGroupManagementService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChatGroupResource extends Resource
{
    protected static ?string $model = CompanyChatGroup::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Grupos';

    protected static ?string $modelLabel = 'grupo';

    protected static ?string $pluralModelLabel = 'grupos';

    protected static ?string $slug = 'grupos';

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return app_user_has_admin_permission(auth()->user(), 'chat-groups.manage');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('participants');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Select::make('participant_search')
                ->label('Buscar usuario por nombre y apellidos')
                ->searchable()
                ->options(fn (Get $get): array => static::availableUserOptions((array) $get('participant_ids')))
                ->getSearchResultsUsing(function (string $search, Get $get): array {
                    return User::query()
                        ->where('is_active', true)
                        ->whereNotIn('id', array_map('intval', (array) $get('participant_ids')))
                        ->where(fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                        ->orderBy('name')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [$user->id => static::userLabel($user)])
                        ->all();
                })
                ->live()
                ->dehydrated(false)
                ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                    if (blank($state)) {
                        return;
                    }

                    $selectedIds = array_map('intval', (array) $get('participant_ids'));
                    $selectedIds[] = (int) $state;
                    $set('participant_ids', array_values(array_unique($selectedIds)));
                    $set('participant_search', null);
                }),
            ViewField::make('participant_ids')
                ->hiddenLabel()
                ->view('filament.forms.components.chat-group-participants')
                ->rules(['required', 'array', 'min:2']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Grupo')->searchable()->sortable()->weight(FontWeight::Bold),
                TextColumn::make('participants_count')->label('Miembros')->badge()->color('primary')->sortable(),
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
                CreateAction::make()->label('Crear grupo')->url(static::getUrl('create'))->modal(false),
            ])
            ->actions([
                EditAction::make()->label('Editar'),
                DeleteAction::make()
                    ->label('Borrar')
                    ->modalHeading('Borrar grupo')
                    ->modalDescription('¿Seguro que quieres borrar este grupo? Esta acción no se puede deshacer.')
                    ->using(function (CompanyChatGroup $record): bool {
                        $actor = auth()->user();
                        abort_unless($actor instanceof User && static::canAccess(), 403);
                        app(CompanyChatGroupManagementService::class)->delete($record, $actor);

                        return true;
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChatGroups::route('/'),
            'logs' => ListChatGroupLogs::route('/logs'),
            'create' => CreateChatGroup::route('/create'),
            'edit' => EditChatGroup::route('/{record}/edit'),
        ];
    }

    public static function userLabel(User $user): string
    {
        return (string) $user->name;
    }

    public static function availableUserOptions(array $selectedIds): array
    {
        return User::query()
            ->where('is_active', true)
            ->whereNotIn('id', array_map('intval', $selectedIds))
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => static::userLabel($user)])
            ->all();
    }
}
