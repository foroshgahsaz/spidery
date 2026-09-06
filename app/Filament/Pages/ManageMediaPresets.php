<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Services\Media\MediaPresetService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageMediaPresets extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'تنظیمات تصاویر';

    protected static ?string $slug = 'media-presets';

    protected static ?string $title = 'تنظیمات سایز تصاویر';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(MediaPresetService $presets): void
    {
        $this->form->fill([
            'presets' => $presets->forAdminForm(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('پیش‌تنظیم‌های بهینه‌سازی')
                    ->description('هر نوع محتوا پس از آپلود به WebP (یا فرمت انتخابی) تبدیل و بر اساس ابعاد زیر resize می‌شود.')
                    ->schema([
                        Forms\Components\Repeater::make('presets')
                            ->label('انواع محتوا')
                            ->schema([
                                Forms\Components\Hidden::make('key'),
                                Forms\Components\TextInput::make('label')
                                    ->label('نوع محتوا')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\Select::make('mode')
                                    ->label('حالت برش')
                                    ->options([
                                        'fit' => 'جا شدن در کادر (حفظ نسبت)',
                                        'cover' => 'پر کردن کادر (برش)',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('max_width')
                                    ->label('عرض (px)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(50)
                                    ->maxValue(4000),
                                Forms\Components\TextInput::make('max_height')
                                    ->label('ارتفاع (px)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(50)
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

    public function save(MediaPresetService $presets): void
    {
        $data = $this->form->getState();
        $presets->saveFromAdminForm($data['presets'] ?? []);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات تصاویر با موفقیت ذخیره شد. آپلودهای جدید با این ابعاد پردازش می‌شوند.')
            ->send();
    }
}
