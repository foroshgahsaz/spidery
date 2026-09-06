<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Filament\Support\AdminImageColumn;
use App\Filament\Support\ShopMediaPicker;
use App\Models\AttributeValue;
use App\Models\ProductVariant;
use App\Services\Product\VariantGenerator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'واریانت‌ها';

    protected static ?string $modelLabel = 'واریانت';

    protected static ?string $pluralModelLabel = 'واریانت‌ها';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->label('عنوان واریانت')
                    ->placeholder('مثلاً: آبی / L')
                    ->maxLength(255),
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->maxLength(255)
                    ->unique(table: ProductVariant::class, ignoreRecord: true),
                Forms\Components\TextInput::make('price')
                    ->label('قیمت (تومان)')
                    ->numeric()
                    ->required()
                    ->prefix('تومان'),
                Forms\Components\TextInput::make('sale_price')
                    ->label('قیمت فروش ویژه')
                    ->numeric()
                    ->prefix('تومان'),
                Forms\Components\TextInput::make('stock')
                    ->label('موجودی')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                Forms\Components\TextInput::make('weight')
                    ->label('وزن (گرم)')
                    ->numeric()
                    ->suffix('گرم'),
                ShopMediaPicker::image('image', 'products/variants', 'تصویر واریانت')->columnSpanFull(),
                Forms\Components\Select::make('attributeValues')
                    ->label('مقادیر ویژگی')
                    ->relationship('attributeValues', 'value')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(fn (AttributeValue $record) => ($record->attribute?->name ?? '').': '.$record->value)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                AdminImageColumn::make('image', 48)->label('تصویر')->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('عنوان')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('attributeValues.value')
                    ->label('ویژگی‌ها')
                    ->badge()
                    ->separator(' ')
                    ->limitList(3),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('قیمت')
                    ->formatStateUsing(fn (?int $state) => $state ? number_format($state).' تومان' : '—'),
                Tables\Columns\TextColumn::make('sale_price')
                    ->label('فروش ویژه')
                    ->formatStateUsing(fn (?int $state) => $state ? number_format($state).' تومان' : '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('موجودی')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
            ])
            ->defaultSort('id')
            ->headerActions([
                Tables\Actions\Action::make('generate')
                    ->label('ساخت خودکار واریانت‌ها')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('ساخت واریانت از ویژگی‌ها')
                    ->modalDescription('بر اساس ویژگی‌هایی که «استفاده برای واریانت» دارند، تمام ترکیب‌ها ساخته می‌شوند (مثل ووکامرس).')
                    ->action(function (VariantGenerator $generator) {
                        $product = $this->getOwnerRecord();
                        $count = $generator->generate($product);

                        Notification::make()
                            ->title($count > 0 ? "{$count} واریانت جدید ساخته شد" : 'واریانت جدیدی ساخته نشد')
                            ->body($count === 0 ? 'ابتدا ویژگی‌های واریانت را به محصول اضافه کنید.' : null)
                            ->success()
                            ->icon('heroicon-o-check-circle')
                            ->send();
                    }),
                Tables\Actions\CreateAction::make()
                    ->label('افزودن واریانت')
                    ->modalHeading('افزودن واریانت')
                    ->mutateFormDataUsing(function (array $data): array {
                        if (empty($data['price'])) {
                            $data['price'] = $this->getOwnerRecord()->effective_price;
                        }

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('ویرایش')
                    ->modalHeading('ویرایش واریانت'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف انتخاب‌شده‌ها'),
                ]),
            ]);
    }
}
