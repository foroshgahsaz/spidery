<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Filament\Support\AdminTable;
use App\Filament\Support\RichContentEditor;
use App\Filament\Support\SeoFormSchema;
use App\Filament\Support\ShopMediaPicker;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'بلاگ';

    protected static ?string $navigationGroup = 'محتوا';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('محتوای مطلب')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('نویسنده')
                        ->relationship('author', 'name', fn ($query) => $query->where('is_author', true))
                        ->searchable()
                        ->preload(),
                    Forms\Components\TextInput::make('title')->label('عنوان')->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                    Forms\Components\TextInput::make('slug')->label('اسلاگ')->required()->unique(ignoreRecord: true),
                    Forms\Components\Textarea::make('excerpt')->label('خلاصه')->rows(3)->columnSpanFull(),
                    RichContentEditor::make('content', 'محتوا', 'posts'),
                    ShopMediaPicker::image('image', 'posts', 'تصویر'),
                    Forms\Components\Toggle::make('is_active')->label('فعال')->default(true),
                    Forms\Components\DateTimePicker::make('published_at')->label('تاریخ انتشار'),
                ])->columns(2),
            ...SeoFormSchema::contentSection(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('عنوان')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('author.name')->label('نویسنده')->placeholder('—'),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
                Tables\Columns\TextColumn::make('published_at')->label('انتشار')->dateTime('Y/m/d'),
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
