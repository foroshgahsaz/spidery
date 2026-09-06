<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Services\Media\HomepageImageService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageHomepageImages extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'تامبنیل صفحه اصلی';

    protected static ?string $slug = 'homepage-images';

    protected static ?string $title = 'تامبنیل بخش‌های صفحه اصلی';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(HomepageImageService $homepageImages): void
    {
        $this->form->fill([
            'sections' => $homepageImages->forAdminForm(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تامبنیل نمایش صفحه اصلی')
                    ->description('ابعاد و کیفیت تصاویر نمایشی هر بخش صفحه اصلی. فایل اصلی در سرور باقی می‌ماند و نسخه کوچک‌شده هنگام بازدید ساخته و کش می‌شود.')
                    ->schema([
                        Forms\Components\Repeater::make('sections')
                            ->label('بخش‌های صفحه اصلی')
                            ->schema([
                                Forms\Components\Hidden::make('key'),
                                Forms\Components\TextInput::make('label')
                                    ->label('بخش')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\Toggle::make('enabled')
                                    ->label('فعال')
                                    ->inline(false),
                                Forms\Components\Select::make('mode')
                                    ->label('حالت برش')
                                    ->options([
                                        'fit' => 'جا شدن در کادر (حفظ نسبت)',
                                        'contain' => 'نمایش کامل در کادر',
                                        'cover' => 'پر کردن کادر (برش)',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('width')
                                    ->label('عرض (px)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(16)
                                    ->maxValue(4000),
                                Forms\Components\TextInput::make('height')
                                    ->label('ارتفاع (px)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(16)
                                    ->maxValue(4000),
                                Forms\Components\TextInput::make('quality')
                                    ->label('کیفیت')
                                    ->numeric()
                                    ->required()
                                    ->minValue(40)
                                    ->maxValue(100),
                                Forms\Components\Select::make('format')
                                    ->label('فرمت خروجی')
                                    ->options([
                                        'webp' => 'WebP',
                                        'jpg' => 'JPEG',
                                        'png' => 'PNG',
                                    ])
                                    ->required(),
                            ])
                            ->columns(3)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(HomepageImageService $homepageImages): void
    {
        $data = $this->form->getState();
        $homepageImages->saveFromAdminForm($data['sections'] ?? []);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات تامبنیل صفحه اصلی ذخیره شد. تصاویر جدید هنگام بازدید کاربران ساخته می‌شوند.')
            ->send();
    }
}
