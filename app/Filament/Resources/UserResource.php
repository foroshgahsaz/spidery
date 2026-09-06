<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Support\AdminImageColumn;
use App\Filament\Support\AdminTable;
use App\Filament\Support\ShopMediaPicker;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'کاربران';

    protected static ?string $navigationGroup = 'کاربران';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('اطلاعات کاربر')
                ->schema([
                    Forms\Components\Select::make('user_kind')
                        ->label('نوع کاربر')
                        ->options([
                            'customer' => 'مشتری',
                            'staff' => 'غیر مشتری (مدیر، نویسنده و ...)',
                        ])
                        ->default('customer')
                        ->required()
                        ->live()
                        ->dehydrated(false),
                    Forms\Components\Fieldset::make('نقش‌های غیر مشتری')
                        ->schema([
                            Forms\Components\Toggle::make('is_admin')
                                ->label('مدیر — دسترسی پنل ادمین'),
                            Forms\Components\Toggle::make('is_author')
                                ->label('نویسنده — انتشار در بلاگ'),
                        ])
                        ->columns(2)
                        ->visible(fn (Get $get): bool => $get('user_kind') === 'staff'),
                    Forms\Components\TextInput::make('name')->label('نام')->required(),
                    Forms\Components\TextInput::make('phone')->label('موبایل')->required()->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('email')->label('ایمیل')->email()->unique(ignoreRecord: true),
                    ShopMediaPicker::image('avatar', 'avatars', 'تصویر پروفایل'),
                    Forms\Components\TextInput::make('password')
                        ->label('رمز عبور')
                        ->password()
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create'),
                    Forms\Components\Toggle::make('status')->label('فعال')->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->searchPlaceholder('جستجوی کاربر')
            ->columns([
                AdminImageColumn::make('avatar', 40)
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn (User $record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&background=7239ea&color=fff&size=128'),
                Tables\Columns\TextColumn::make('name')
                    ->label('کاربر')
                    ->searchable(['name', 'email', 'phone'])
                    ->description(fn (User $record) => $record->email ?: $record->phone)
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('customer_type')
                    ->label('نوع')
                    ->state(fn (User $record) => $record->customerTypeLabel())
                    ->description(fn (User $record) => $record->isCustomer() ? null : $record->staffRoleLabel())
                    ->badge()
                    ->color(fn (User $record) => $record->roleColor()),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('آخرین ورود')
                    ->since()
                    ->placeholder('هرگز'),
                Tables\Columns\TextColumn::make('phone_verified_at')
                    ->label('احراز موبایل')
                    ->formatStateUsing(fn ($state) => $state ? 'فعال' : 'غیرفعال')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ عضویت')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_kind')
                    ->label('نوع کاربر')
                    ->options([
                        'customer' => 'مشتری',
                        'staff' => 'غیر مشتری',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'customer' => $query->where('is_admin', false)->where('is_author', false),
                            'staff' => $query->where(fn ($q) => $q->where('is_admin', true)->orWhere('is_author', true)),
                            default => $query,
                        };
                    }),
                Tables\Filters\TernaryFilter::make('status')->label('فعال'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش')->iconButton(),
                Tables\Actions\DeleteAction::make()->label('حذف')->iconButton()
                    ->successNotificationTitle('حذف شد'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف انتخاب‌شده‌ها')
                        ->successNotificationTitle('حذف شد'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
