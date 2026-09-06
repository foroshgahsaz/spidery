<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomeSliderResource\Pages;
use App\Filament\Support\AdminImageColumn;
use App\Filament\Support\ShopMediaPicker;
use App\Models\HomeSlider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HomeSliderResource extends Resource
{
    protected static ?string $model = HomeSlider::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'اسلایدر صفحه اصلی';

    protected static ?string $modelLabel = 'اسلاید';

    protected static ?string $pluralModelLabel = 'اسلایدرها';

    protected static ?string $navigationGroup = 'محتوا';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('اطلاعات اسلاید')->schema([
                Forms\Components\TextInput::make('title')
                    ->label('عنوان')
                    ->maxLength(255),
                ShopMediaPicker::image('image', 'sliders', 'تصویر')
                    ->required()
                    ->maxSize(4096),
                Forms\Components\TextInput::make('link')
                    ->label('لینک')
                    ->url()
                    ->placeholder('https://example.com/products'),
                Forms\Components\TextInput::make('position')
                    ->label('ترتیب نمایش')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                Forms\Components\Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                AdminImageColumn::make('image', 50),
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('link')
                    ->label('لینک')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('ترتیب')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
                Tables\Columns\TextColumn::make('archived_at')
                    ->label('آرشیو')
                    ->dateTime('Y/m/d H:i')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('بروزرسانی')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('فعال'),
                Tables\Filters\SelectFilter::make('archive_status')
                    ->label('وضعیت')
                    ->options([
                        'active' => 'فعال (غیر آرشیو)',
                        'archived' => 'آرشیو شده',
                        'all' => 'همه',
                    ])
                    ->default('active')
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value'] ?? 'active') {
                            'archived' => $query->whereNotNull('archived_at'),
                            'all' => $query,
                            default => $query->whereNull('archived_at'),
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش'),
                Tables\Actions\Action::make('archive')
                    ->label('آرشیو')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (HomeSlider $record) => $record->archived_at === null)
                    ->action(fn (HomeSlider $record) => $record->archive()),
                Tables\Actions\Action::make('restore')
                    ->label('بازگردانی')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->visible(fn (HomeSlider $record) => $record->archived_at !== null)
                    ->action(fn (HomeSlider $record) => $record->restoreFromArchive()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomeSliders::route('/'),
            'create' => Pages\CreateHomeSlider::route('/create'),
            'edit' => Pages\EditHomeSlider::route('/{record}/edit'),
        ];
    }
}
