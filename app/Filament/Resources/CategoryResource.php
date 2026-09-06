<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Support\AdminTable;
use App\Filament\Support\SeoFormSchema;
use App\Filament\Support\ShopMediaPicker;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'دسته‌بندی‌ها';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'دسته‌بندی';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('اطلاعات دسته‌بندی')
                ->schema([
                    Forms\Components\Select::make('parent_id')
                        ->label('دسته والد')
                        ->relationship('parent', 'name')
                        ->searchable()
                        ->preload()
                        ->placeholder('بدون والد (دسته اصلی)'),
                    Forms\Components\TextInput::make('name')
                        ->label('نام')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                    Forms\Components\TextInput::make('slug')
                        ->label('اسلاگ')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->label('توضیحات')
                        ->rows(4)
                        ->columnSpanFull(),
                    ShopMediaPicker::image('image', 'categories', 'تصویر')->columnSpanFull(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('فعال')
                        ->default(true),
                    Forms\Components\TextInput::make('position')
                        ->label('ترتیب')
                        ->numeric()
                        ->default(0),
                    Forms\Components\Toggle::make('show_in_mega')
                        ->label('نمایش در مگامنو')
                        ->default(true),
                    Forms\Components\TextInput::make('mega_column')
                        ->label('ستون مگامنو (۱ تا ۳)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(3),
                ])
                ->columns(2),

            ...SeoFormSchema::contentSection(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('نام')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('parent.name')->label('والد')->placeholder('—'),
                Tables\Columns\IconColumn::make('show_in_mega')->label('مگامenu')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
                Tables\Columns\TextColumn::make('products_count')->counts('products')->label('محصولات')->sortable(),
                Tables\Columns\TextColumn::make('position')->label('ترتیب')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش')->iconButton(),
                Tables\Actions\DeleteAction::make()->label('حذف')->iconButton()
                    ->successNotificationTitle('حذف شد'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
