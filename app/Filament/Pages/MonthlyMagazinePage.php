<?php

namespace App\Filament\Pages;

use App\Models\MonthlyMagazineActivityLog;
use App\Models\MonthlyMagazineSetting;
use App\Models\User;
use App\Services\MonthlyMagazineActivityLogWriter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MonthlyMagazinePage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Revista';

    protected static ?string $title = 'Revista';

    protected static ?string $slug = 'revista';

    protected static string|\UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 2;

    protected static ?string $breadcrumb = 'Revista';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return app_user_has_admin_permission(auth()->user(), 'magazine.manage');
    }

    public function mount(): void
    {
        $this->form->fill($this->getFormState());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('tag_label')
                ->label('Etiqueta visible')
                ->required()
                ->maxLength(80),
            TextInput::make('file_name')
                ->label('Nombre del archivo')
                ->required()
                ->maxLength(120)
                ->helperText('Se usará para generar el nombre final del PDF.'),
            FileUpload::make('magazine_file')
                ->label('PDF')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(51200)
                ->previewable()
                ->openable()
                ->downloadable()
                ->storeFileNamesIn('magazine_original_filename')
                ->getUploadedFileUsing(function (string $file): ?array {
                    $publicPath = public_path($file);

                    if (! File::exists($publicPath)) {
                        return null;
                    }

                    return [
                        'name' => basename($file),
                        'size' => File::size($publicPath),
                        'type' => File::mimeType($publicPath) ?: 'application/pdf',
                        'url' => asset($file),
                    ];
                })
                ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, Get $get): string {
                    $directory = public_path('revista');

                    if (! File::exists($directory)) {
                        File::makeDirectory($directory, 0755, true);
                    }

                    $slug = Str::slug((string) $get('file_name')) ?: 'revista';
                    $filename = $slug . '.pdf';
                    $absolutePath = $directory . DIRECTORY_SEPARATOR . $filename;

                    if (File::exists($absolutePath)) {
                        File::delete($absolutePath);
                    }

                    File::copy($file->getRealPath(), $absolutePath);

                    return 'revista/' . $filename;
                }),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make($this->getFormActions())
                        ->alignment($this->getFormActionsAlignment())
                        ->fullWidth(true)
                        ->key('form-actions'),
                ]),
        ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('viewLogs')
                ->label('Ver logs')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->url(MonthlyMagazineLogsPage::getUrl())
                ->visible(fn (): bool => auth()->user()?->role === User::ROLE_ADMIN),
            Action::make('save')
                ->label('Guardar revista')
                ->icon('heroicon-o-check')
                ->submit('save'),
            Action::make('delete')
                ->label('Eliminar revista')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => MonthlyMagazineSetting::query()->exists())
                ->action(fn () => $this->delete()),
        ];
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::Start;
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $magazine = MonthlyMagazineSetting::query()->first() ?? new MonthlyMagazineSetting();
        $isCreate = ! $magazine->exists;

        $previousTagLabel = $magazine->tag_label ?? MonthlyMagazineSetting::DEFAULT_TAG_LABEL;
        $previousPdfPath = $magazine->pdf_path ?? MonthlyMagazineSetting::DEFAULT_PDF_PATH;
        $previousOriginalFilename = $magazine->original_filename;

        $targetPdfPath = 'revista/' . ((Str::slug((string) ($data['file_name'] ?? '')) ?: 'revista') . '.pdf');
        $targetAbsolutePath = public_path($targetPdfPath);
        $previousAbsolutePath = public_path($previousPdfPath);
        $originalFilename = $data['magazine_original_filename'] ?? $previousOriginalFilename ?? basename($targetPdfPath);

        if ($targetPdfPath !== $previousPdfPath) {
            if (! File::exists($targetAbsolutePath) && File::exists($previousAbsolutePath)) {
                File::copy($previousAbsolutePath, $targetAbsolutePath);
            }

            if (
                ($previousPdfPath !== MonthlyMagazineSetting::DEFAULT_PDF_PATH) &&
                File::exists($previousAbsolutePath) &&
                ($previousAbsolutePath !== $targetAbsolutePath)
            ) {
                File::delete($previousAbsolutePath);
            }
        }

        DB::transaction(function () use ($magazine, $data, $targetPdfPath, $originalFilename, $previousTagLabel, $previousPdfPath, $previousOriginalFilename, $isCreate): void {
            $magazine->fill([
                'tag_label' => $data['tag_label'],
                'pdf_path' => $targetPdfPath,
                'original_filename' => $originalFilename,
                'updated_by_user_id' => auth()->id(),
            ]);

            $magazine->save();

            app(MonthlyMagazineActivityLogWriter::class)->record(
                actor: auth()->user(),
                action: $isCreate ? MonthlyMagazineActivityLog::ACTION_CREATED : MonthlyMagazineActivityLog::ACTION_UPDATED,
                targetName: $data['tag_label'],
                targetReference: $targetPdfPath,
                changes: [
                    'tag_label' => ['from' => $previousTagLabel, 'to' => $data['tag_label']],
                    'pdf_path' => ['from' => $previousPdfPath, 'to' => $targetPdfPath],
                    'original_filename' => ['from' => $previousOriginalFilename, 'to' => $originalFilename],
                ],
            );
        });

        Notification::make()
            ->title('La revista se ha actualizado correctamente.')
            ->success()
            ->send();
    }

    public function delete(): void
    {
        $magazine = MonthlyMagazineSetting::query()->first();

        if (! $magazine) {
            Notification::make()
                ->title('No hay ninguna revista para eliminar.')
                ->warning()
                ->send();

            return;
        }

        $previousTagLabel = $magazine->tag_label ?? MonthlyMagazineSetting::DEFAULT_TAG_LABEL;
        $previousPdfPath = $magazine->pdf_path ?? MonthlyMagazineSetting::DEFAULT_PDF_PATH;
        $previousOriginalFilename = $magazine->original_filename;

        DB::transaction(function () use ($magazine, $previousTagLabel, $previousPdfPath, $previousOriginalFilename): void {
            app(MonthlyMagazineActivityLogWriter::class)->record(
                actor: auth()->user(),
                action: MonthlyMagazineActivityLog::ACTION_DELETED,
                targetName: $previousTagLabel,
                targetReference: $previousPdfPath,
                changes: [
                    'tag_label' => ['from' => $previousTagLabel, 'to' => null],
                    'pdf_path' => ['from' => $previousPdfPath, 'to' => null],
                    'original_filename' => ['from' => $previousOriginalFilename, 'to' => null],
                ],
            );

            if ($previousPdfPath !== MonthlyMagazineSetting::DEFAULT_PDF_PATH) {
                $absolutePath = public_path($previousPdfPath);

                if (File::exists($absolutePath)) {
                    File::delete($absolutePath);
                }
            }

            $magazine->delete();
        });

        $this->form->fill($this->getFormState());

        Notification::make()
            ->title('La revista se ha eliminado correctamente.')
            ->success()
            ->send();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getFormState(): array
    {
        $magazine = MonthlyMagazineSetting::current();

        $currentPdfPath = $magazine->pdf_path ?? MonthlyMagazineSetting::DEFAULT_PDF_PATH;
        $fileName = basename($currentPdfPath);

        return [
            'tag_label' => $magazine->tag_label ?? MonthlyMagazineSetting::DEFAULT_TAG_LABEL,
            'file_name' => Str::of($fileName)->beforeLast('.')->replace('-', ' ')->trim()->toString() ?: 'revista ' . now()->year,
            'magazine_file' => $currentPdfPath,
            'magazine_original_filename' => $magazine->original_filename ?? $fileName,
        ];
    }
}
