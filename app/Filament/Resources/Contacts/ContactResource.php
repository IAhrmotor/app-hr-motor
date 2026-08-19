<?php

namespace App\Filament\Resources\Contacts;

use App\Filament\Resources\Contacts\Pages\CreateContact;
use App\Filament\Resources\Contacts\Pages\EditContact;
use App\Filament\Resources\Contacts\Pages\ListContactLogs;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Models\Contact;
use App\Models\ContentActivityLog;
use App\Models\User;
use App\Services\ContentActivityLogger;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationLabel = 'Contactos';

    protected static ?string $modelLabel = 'contacto';

    protected static ?string $pluralModelLabel = 'contactos';

    protected static ?string $slug = 'contactos';

    protected static string|\UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return app_user_has_admin_permission(auth()->user(), 'contacts.manage');
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
        return parent::getEloquentQuery();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('Correo')
                ->email()
                ->maxLength(255),
            TextInput::make('phone')
                ->label('Teléfono')
                ->tel()
                ->maxLength(32),
            TextInput::make('enreach_extension')
                ->label('Extensión Enreach')
                ->maxLength(20),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->grow(false)
                    ->width('18rem'),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable()
                    ->grow(false)
                    ->width('20rem')
                    ->placeholder('No disponible'),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->sortable()
                    ->grow(false)
                    ->width('12rem')
                    ->placeholder('No disponible'),
                TextColumn::make('enreach_extension')
                    ->label('Extensión Enreach')
                    ->searchable()
                    ->sortable()
                    ->grow(false)
                    ->width('14rem')
                    ->placeholder('No disponible'),
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
                    ->label('Crear contacto')
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
                    ->modalHeading('Borrar contacto')
                    ->modalDescription('¿Seguro que quieres borrar este contacto? Esta acción no se puede deshacer.')
                    ->using(function (Contact $record): bool {
                        $actor = auth()->user();

                        if (! $actor instanceof User) {
                            return false;
                        }

                        app(ContentActivityLogger::class)->record(
                            actor: $actor,
                            contentType: ContentActivityLog::CONTENT_TYPE_CONTACT,
                            action: ContentActivityLog::ACTION_DELETED,
                            targetName: $record->name,
                            targetReference: $record->enreach_extension ?: $record->phone,
                            changes: [
                                'name' => ['from' => $record->name, 'to' => null],
                                'email' => ['from' => $record->email, 'to' => null],
                                'phone' => ['from' => $record->phone, 'to' => null],
                                'enreach_extension' => ['from' => $record->enreach_extension, 'to' => null],
                            ],
                        );

                        return (bool) $record->delete();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContacts::route('/'),
            'logs' => ListContactLogs::route('/logs'),
            'create' => CreateContact::route('/create'),
            'edit' => EditContact::route('/{record}/edit'),
        ];
    }
}
