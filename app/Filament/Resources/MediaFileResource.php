<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaFileResource\Pages;
use App\Models\MediaFile;
use App\Services\Media\MediaRegistry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class MediaFileResource extends Resource
{
    protected static ?string $model = MediaFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'مدیریت فایل';

    protected static ?string $modelLabel = 'فایل';

    protected static ?string $pluralModelLabel = 'مدیریت فایل';

    protected static ?string $navigationGroup = 'مدیریت فایل';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        $folders = config('media-library.folders', []);

        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('پیش‌نمایش')
                    ->disk('public')
                    ->visibility('public')
                    ->height(56)
                    ->width(56)
                    ->square()
                    ->defaultImageUrl(asset('shop/images/products/clothing.svg')),
                Tables\Columns\TextColumn::make('original_name')
                    ->label('نام')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('folder')
                    ->label('پوشه')
                    ->formatStateUsing(fn (string $state): string => $folders[$state] ?? $state)
                    ->badge(),
                Tables\Columns\TextColumn::make('path')
                    ->label('مسیر')
                    ->searchable()
                    ->copyable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('dimensions')
                    ->label('ابعاد')
                    ->state(fn (MediaFile $record): string => $record->dimensionsLabel()),
                Tables\Columns\TextColumn::make('size')
                    ->label('حجم')
                    ->formatStateUsing(fn (MediaFile $record): string => $record->humanSize()),
                Tables\Columns\TextColumn::make('usages_count')
                    ->counts('usages')
                    ->label('استفاده'),
                Tables\Columns\IconColumn::make('exists_on_disk')
                    ->label('روی دیسک')
                    ->boolean()
                    ->getStateUsing(fn (MediaFile $record): bool => $record->existsOnDisk()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('folder')
                    ->label('پوشه')
                    ->options($folders),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('جزئیات'),
                Tables\Actions\Action::make('open')
                    ->label('باز کردن')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (MediaFile $record): ?string => $record->url())
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->before(function (MediaFile $record): void {
                        if ($record->usages()->exists()) {
                            throw new \RuntimeException('این فایل هنوز در محتوا استفاده می‌شود و قابل حذف نیست.');
                        }
                    })
                    ->action(function (MediaFile $record): void {
                        if (Storage::disk($record->disk)->exists($record->path)) {
                            Storage::disk($record->disk)->delete($record->path);
                        }

                        $record->delete();
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('sync_disk')
                    ->label('همگام‌سازی از دیسک')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalHeading('همگام‌سازی فایل‌های /data')
                    ->modalDescription('فایل‌های موجود روی دیسک در کتابخانه رسانه ثبت می‌شوند.')
                    ->action(function (MediaRegistry $registry): void {
                        $disk = Storage::disk('public');
                        $imported = 0;

                        foreach (array_keys(config('media-library.folders', [])) as $folder) {
                            if (! $disk->exists($folder)) {
                                continue;
                            }

                            foreach ($disk->allFiles($folder) as $path) {
                                $registry->registerFromPath('public', $path);
                                $imported++;
                            }
                        }

                        Notification::make()
                            ->title('همگام‌سازی انجام شد')
                            ->body("{$imported} فایل ثبت شد.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('upload')
                    ->label('آپلود فایل')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\Select::make('folder')
                            ->label('پوشه')
                            ->options($folders)
                            ->default('uploads')
                            ->required(),
                        Forms\Components\FileUpload::make('file')
                            ->label('تصویر')
                            ->image()
                            ->disk('public')
                            ->directory(fn (Forms\Get $get): string => (string) $get('folder'))
                            ->required(),
                    ])
                    ->action(function (array $data, MediaRegistry $registry): void {
                        $path = is_array($data['file'] ?? null)
                            ? ($data['file'][0] ?? null)
                            : ($data['file'] ?? null);

                        if (is_string($path) && $path !== '') {
                            $registry->registerFromPath('public', $path);
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMediaFiles::route('/'),
            'view' => Pages\ViewMediaFile::route('/{record}'),
        ];
    }
}
