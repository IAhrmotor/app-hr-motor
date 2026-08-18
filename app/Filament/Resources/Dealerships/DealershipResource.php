<?php

namespace App\Filament\Resources\Dealerships;

use App\Filament\Resources\Dealerships\Pages\EditDealership;
use App\Filament\Resources\Dealerships\Pages\CreateDealership;
use App\Filament\Resources\Dealerships\Pages\ListDealerships;
use App\Models\Dealership;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DealershipResource extends Resource
{
    protected static ?string $model = Dealership::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Delegaciones';

    protected static ?string $modelLabel = 'delegación';

    protected static ?string $pluralModelLabel = 'delegaciones';

    protected static ?string $slug = 'delegaciones';

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('users');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            TextInput::make('salesforce_id')
                ->label('ID Salesforce')
                ->required()
                ->maxLength(255),
            TextInput::make('phone')
                ->label('Teléfono')
                ->tel()
                ->maxLength(255),
            TextInput::make('google_maps_url')
                ->label('URL Google Maps')
                ->url()
                ->maxLength(2048),
            TextInput::make('reviews_url')
                ->label('URL Reseñas')
                ->url()
                ->maxLength(2048),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('')
                    ->defaultImageUrl(asset('images/users/hrmotor-default-user-avatar.png'))
                    ->circular()
                    ->grow(false)
                    ->imageSize(44)
                    ->width('4rem'),
                TextColumn::make('name')
                    ->label('Delegación')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->grow(false)
                    ->width('18rem'),
                TextColumn::make('salesforce_id')
                    ->label('ID Salesforce')
                    ->searchable()
                    ->sortable()
                    ->grow(false)
                    ->width('18rem')
                    ->placeholder('Sin configurar'),
                TextColumn::make('users_count')
                    ->label('Equipo')
                    ->state(function (Dealership $record): string {
                        $count = (int) ($record->users_count ?? 0);

                        return $count . ' ' . ($count === 1 ? 'usuario' : 'usuarios');
                    })
                    ->sortable()
                    ->grow(false)
                    ->width('12rem'),
            ])
            ->defaultSort('name')
            ->striped()
            ->toolbarActions([
                CreateAction::make()
                    ->label('Crear delegación')
                    ->url(static::getUrl('create'))
                    ->modal(false)
            ])
            ->actions([
                EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square'),
                DeleteAction::make()
                    ->label('Borrar')
                    ->icon('heroicon-o-trash')
                    ->disabled(fn (Dealership $record): bool => $record->users()->exists())
                    ->tooltip(fn (Dealership $record): ?string => $record->users()->exists()
                        ? 'No puedes eliminar una delegación con usuarios asignados.'
                        : 'Eliminar delegación')
                    ->modalHeading('Borrar delegación')
                    ->modalDescription('¿Seguro que quieres borrar esta delegación? Esta acción no se puede deshacer.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDealerships::route('/'),
            'create' => Pages\CreateDealership::route('/create'),
            'edit' => Pages\EditDealership::route('/{record}/edit'),
        ];
    }
}
