<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Support\AdminImageColumn;
use App\Filament\Support\ShopMediaPicker;
use Filament\Tables;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'تصاویر محصول';

    protected static ?string $modelLabel = 'تصویر';

    protected static ?string $pluralModelLabel = 'تصاویر';

    public function form(Form $form): Form
    {
        return $form->schema([
            ShopMediaPicker::image('image', 'products', 'تصویر')
                ->required(fn (string $operation): bool => $operation === 'create'),
            Forms\Components\TextInput::make('price')
                ->label('قیمت این تصویر (تومان)')
                ->numeric()
                ->prefix('تومان')
                ->helperText('برای تصویر اصلی و هر تصویر گالری می‌توانید قیمت جداگانه تعیین کنید. خالی = استفاده از قیمت محصول'),
            Forms\Components\Toggle::make('is_primary')
                ->label('تصویر اصلی')
                ->helperText('فقط یک تصویر می‌تواند اصلی باشد'),
            Forms\Components\TextInput::make('position')
                ->label('ترتیب')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            AdminImageColumn::make('image', 60)->label('پیش‌نمایش'),
            Tables\Columns\IconColumn::make('is_primary')
                ->label('اصلی')
                ->boolean(),
            Tables\Columns\TextColumn::make('price')
                ->label('قیمت تصویر')
                ->formatStateUsing(fn (?int $state) => $state ? number_format($state).' تومان' : '—')
                ->placeholder('قیمت محصول'),
            Tables\Columns\TextColumn::make('position')
                ->label('ترتیب'),
        ])->headerActions([
            Tables\Actions\CreateAction::make()
                ->label('افزودن تصویر')
                ->modalHeading('افزودن تصویر'),
        ])->actions([
            Tables\Actions\EditAction::make()
                ->label('ویرایش')
                ->modalHeading('ویرایش تصویر'),
            Tables\Actions\DeleteAction::make()->label('حذف'),
        ]);
    }
}
