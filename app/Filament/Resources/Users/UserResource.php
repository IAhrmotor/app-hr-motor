<?php

namespace App\Filament\Resources\Users;

use App\Models\User;
use App\Services\UserDeactivationService;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'usuarios';

    protected static ?string $slug = 'usuarios';

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('assignedDealership');
    }

    public static function form(Schema $schema): Schema
    {
        $itScheduleFields = [
            'it_monday_start',
            'it_monday_end',
            'it_tuesday_start',
            'it_tuesday_end',
            'it_wednesday_start',
            'it_wednesday_end',
            'it_thursday_start',
            'it_thursday_end',
            'it_friday_start',
            'it_friday_end',
        ];

        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('Correo')
                ->email()
                ->required()
                ->maxLength(255),
            DatePicker::make('company_entry_date')
                ->label('Día que entró en la empresa')
                ->required(),
            TextInput::make('job_position')
                ->label('Puesto')
                ->required()
                ->maxLength(255),
            TextInput::make('phone')
                ->label('Teléfono')
                ->tel()
                ->maxLength(255),
            TextInput::make('enreach_extension')
                ->label('Extensión Enreach')
                ->maxLength(255),
            Select::make('role')
                ->label('Rol base')
                ->options(fn (): array => auth()->user()?->role === User::ROLE_ADMIN
                    ? User::baseRoleLabels()
                    : [User::ROLE_USER => User::baseRoleLabels()[User::ROLE_USER]])
                ->required()
                ->searchable(),
            Select::make('extra_role')
                ->label('Rol extra')
                ->options(User::extraRoleLabels())
                ->placeholder('Sin rol extra')
                ->searchable()
                ->live()
                ->afterStateUpdated(function (Set $set, ?string $state) use ($itScheduleFields): void {
                    if (! in_array($state, [User::ROLE_COMMERCIAL, User::ROLE_STORE_MANAGER, User::ROLE_AREA_MANAGER, User::ROLE_HR_NEWCARS], true)) {
                        $set('salesforce_user_id', null);
                    }

                    if ($state !== User::ROLE_INFORMATION_TECHNOLOGY) {
                        foreach ($itScheduleFields as $field) {
                            $set($field, null);
                        }
                    }
                }),
            TextInput::make('salesforce_user_id')
                ->label('ID Salesforce')
                ->maxLength(255)
                ->visible(fn (Get $get): bool => in_array($get('extra_role'), [User::ROLE_COMMERCIAL, User::ROLE_STORE_MANAGER, User::ROLE_AREA_MANAGER, User::ROLE_HR_NEWCARS], true))
                ->required(fn (Get $get): bool => in_array($get('extra_role'), [User::ROLE_COMMERCIAL, User::ROLE_STORE_MANAGER, User::ROLE_AREA_MANAGER, User::ROLE_HR_NEWCARS], true))
                ->dehydrated(fn (Get $get): bool => in_array($get('extra_role'), [User::ROLE_COMMERCIAL, User::ROLE_STORE_MANAGER, User::ROLE_AREA_MANAGER, User::ROLE_HR_NEWCARS], true)),
            Section::make('Horario IT')
                ->description('Los sábados y domingos no llevan horario.')
                ->visible(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                ->columnSpanFull()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TimePicker::make('it_monday_start')
                                ->label('Lunes inicio')
                                ->seconds(false)
                                ->required(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->dehydrated(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->columnSpan(1),
                            TimePicker::make('it_monday_end')
                                ->label('Lunes fin')
                                ->seconds(false)
                                ->required(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->dehydrated(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->columnSpan(1),
                        ])
                        ->columnSpanFull(),
                    Grid::make(2)
                        ->schema([
                            TimePicker::make('it_tuesday_start')
                                ->label('Martes inicio')
                                ->seconds(false)
                                ->required(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->dehydrated(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->columnSpan(1),
                            TimePicker::make('it_tuesday_end')
                                ->label('Martes fin')
                                ->seconds(false)
                                ->required(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->dehydrated(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->columnSpan(1),
                        ])
                        ->columnSpanFull(),
                    Grid::make(2)
                        ->schema([
                            TimePicker::make('it_wednesday_start')
                                ->label('Miércoles inicio')
                                ->seconds(false)
                                ->required(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->dehydrated(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->columnSpan(1),
                            TimePicker::make('it_wednesday_end')
                                ->label('Miércoles fin')
                                ->seconds(false)
                                ->required(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->dehydrated(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->columnSpan(1),
                        ])
                        ->columnSpanFull(),
                    Grid::make(2)
                        ->schema([
                            TimePicker::make('it_thursday_start')
                                ->label('Jueves inicio')
                                ->seconds(false)
                                ->required(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->dehydrated(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->columnSpan(1),
                            TimePicker::make('it_thursday_end')
                                ->label('Jueves fin')
                                ->seconds(false)
                                ->required(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->dehydrated(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->columnSpan(1),
                        ])
                        ->columnSpanFull(),
                    Grid::make(2)
                        ->schema([
                            TimePicker::make('it_friday_start')
                                ->label('Viernes inicio')
                                ->seconds(false)
                                ->required(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->dehydrated(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->columnSpan(1),
                            TimePicker::make('it_friday_end')
                                ->label('Viernes fin')
                                ->seconds(false)
                                ->required(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->dehydrated(fn (Get $get): bool => $get('extra_role') === User::ROLE_INFORMATION_TECHNOLOGY)
                                ->columnSpan(1),
                        ])
                        ->columnSpanFull(),
                ]),
            Select::make('dealership_id')
                ->label('Delegación')
                ->relationship('assignedDealership', 'name')
                ->searchable()
                ->preload()
                ->placeholder('No aplica'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular()
                    ->grow(false)
                    ->imageSize(44)
                    ->width('4rem'),
                TextColumn::make('name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->grow(false)
                    ->width('16rem'),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable()
                    ->grow(false)
                    ->width('22rem'),
                TextColumn::make('role_label')
                    ->label('Rol')
                    ->badge()
                    ->color('primary')
                    ->grow(false)
                    ->width('12rem')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('role', $direction)->orderBy('extra_role', $direction);
                    }),
                TextColumn::make('resolved_dealership_name')
                    ->label('Delegacion')
                    ->placeholder('No aplica')
                    ->grow(false)
                    ->width('16rem')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('dealership_id', $direction)->orderBy('dealership', $direction);
                    }),
                TextColumn::make('status')
                    ->label('Estado')
                    ->state(function (User $record): string {
                        if ($record->isDisabled()) {
                            return 'Desactivado';
                        }

                        if ($record->is_active) {
                            return 'Activo';
                        }

                        if ($record->isInvitationExpired()) {
                            return 'Caducado';
                        }

                        return 'Pendiente';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Activo' => 'success',
                        'Pendiente' => 'warning',
                        'Caducado' => 'danger',
                        'Desactivado' => 'gray',
                        default => 'gray',
                    })
                    ->grow(false)
                    ->width('10rem'),
            ])
            ->defaultSort('name')
            ->striped()
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activo',
                        'pendiente' => 'Pendiente',
                        'caducado' => 'Caducado',
                        'desactivado' => 'Desactivado',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'activo' => $query->where('is_active', true)->whereNull('disabled_at'),
                            'pendiente' => $query
                                ->where('is_active', false)
                                ->whereNull('disabled_at')
                                ->where('must_change_password', true)
                                ->where(function (Builder $query): void {
                                    $query->whereNull('invitation_sent_at')
                                        ->orWhere('invitation_sent_at', '>=', now()->subMinutes((int) config('auth.passwords.users.expire', 60)));
                                }),
                            'caducado' => $query
                                ->where('is_active', false)
                                ->whereNull('disabled_at')
                                ->where('must_change_password', true)
                                ->whereNotNull('invitation_sent_at')
                                ->where('invitation_sent_at', '<', now()->subMinutes((int) config('auth.passwords.users.expire', 60))),
                            'desactivado' => $query->whereNotNull('disabled_at'),
                            default => $query,
                        };
                    }),
            ])
            ->toolbarActions([
                Action::make('viewLogs')
                    ->label('Ver logs')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('gray')
                    ->url(static::getUrl('logs'))
                    ->visible(fn (): bool => auth()->user()?->role === User::ROLE_ADMIN),
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make()
                    ->visible(function (User $record): bool {
                        $authUser = auth()->user();

                        if (! $authUser) {
                            return false;
                        }

                        if ($authUser->role === User::ROLE_ADMIN) {
                            return true;
                        }

                        return $authUser->role === User::ROLE_MANAGER
                            && $authUser->id !== $record->id
                            && $record->role === User::ROLE_USER;
                }),
                DeleteAction::make()
                    ->label('Borrar')
                    ->modalIconColor('primary')
                    ->modalSubmitAction(fn (Action $action) => $action->color('primary'))
                    ->visible(function (User $record): bool {
                        $authUser = auth()->user();

                        return $authUser instanceof User
                            && $authUser->role === User::ROLE_ADMIN
                            && $authUser->id !== $record->id;
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Borrar usuario')
                    ->modalDescription('¿Estás seguro de que quieres borrar este usuario? Esta acción no se puede deshacer.')
                    ->using(function (User $record): bool {
                        $actor = auth()->user();

                        if (! $actor instanceof User) {
                            return false;
                        }

                        app(\App\Services\UserActivityLogWriter::class)->record(
                            actor: $actor,
                            targetUser: $record,
                            action: \App\Models\UserActivityLog::ACTION_DELETED,
                        );

                        return (bool) $record->delete();
                    }),
                Action::make('disable')
                    ->label('Desactivar')
                    ->icon('heroicon-o-user-minus')
                    ->color('gray')
                    ->visible(function (User $record): bool {
                        $authUser = auth()->user();

                        return $authUser instanceof User
                            && app(UserDeactivationService::class)->canDeactivate($authUser, $record);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Desactivar usuario')
                    ->modalDescription('¿Estás seguro de que quieres desactivar este usuario? Se cerrarán sus sesiones y quedará marcado como desactivado.')
                    ->action(function (User $record): void {
                        $authUser = auth()->user();

                        if (! $authUser instanceof User) {
                            return;
                        }

                        try {
                            app(UserDeactivationService::class)->deactivate($authUser, $record);
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title($e->getMessage())
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title('Usuario desactivado correctamente.')
                            ->body('El usuario ya no podrá acceder al backoffice.')
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'logs' => Pages\ListUserLogs::route('/logs'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
