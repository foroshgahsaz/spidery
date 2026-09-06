<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\FileUploadSanitizer;
use App\Filament\Support\NormalizesFileUploadFormState;
use App\Filament\Support\ShopMediaPicker;
use App\Services\Media\MediaRegistry;
use App\Services\Settings\SettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageGeneralSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use NormalizesFileUploadFormState {
        NormalizesFileUploadFormState::getFormUploadedFiles insteadof InteractsWithForms;
    }

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'تنظیمات عمومی';

    protected static ?string $title = 'تنظیمات عمومی سایت';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 0;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $site = $settings->site();

        $this->form->fill([
            'name' => $site['name'],
            'description' => $site['description'],
            'logo' => $site['logo'] ? [$site['logo']] : [],
            'favicon' => $site['favicon'] ? [$site['favicon']] : [],
            'phone' => $site['phone'],
            'email' => $site['email'],
            'address' => $site['address'],
            'instagram' => $site['instagram'],
            'telegram' => $site['telegram'],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات اصلی')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('نام سایت')
                            ->required()
                            ->maxLength(120),
                        Forms\Components\Textarea::make('description')
                            ->label('توضیح کوتاه')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('برای SEO و نمایش در فوتر'),
                        ShopMediaPicker::image('logo', 'settings', 'لوگو')->maxSize(2048),
                        ShopMediaPicker::image('favicon', 'settings', 'فاوآیکن')
                            ->maxSize(512)
                            ->helperText('فرمت PNG یا ICO، حداکثر ۵۱۲KB'),
                    ])->columns(2),
                Forms\Components\Section::make('اطلاعات تماس')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('تلفن')
                            ->tel()
                            ->placeholder('02112345678'),
                        Forms\Components\TextInput::make('email')
                            ->label('ایمیل')
                            ->email(),
                        Forms\Components\Textarea::make('address')
                            ->label('آدرس')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('شبکه‌های اجتماعی')
                    ->schema([
                        Forms\Components\TextInput::make('instagram')
                            ->label('اینستاگرام')
                            ->url()
                            ->placeholder('https://instagram.com/...'),
                        Forms\Components\TextInput::make('telegram')
                            ->label('تلگرام')
                            ->url()
                            ->placeholder('https://t.me/...'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(SettingsService $settings, MediaRegistry $media): void
    {
        FileUploadSanitizer::sanitize($this, $this->form);

        $data = $this->form->getState();

        $logo = collect($data['logo'] ?? [])->first(fn ($value) => is_string($value) && $value !== '') ?? '';
        $favicon = collect($data['favicon'] ?? [])->first(fn ($value) => is_string($value) && $value !== '') ?? '';

        $settings->setMany('site', [
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'logo' => $logo,
            'favicon' => $favicon,
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? '',
            'address' => $data['address'] ?? '',
            'instagram' => $data['instagram'] ?? '',
            'telegram' => $data['telegram'] ?? '',
        ]);

        foreach (array_filter(['logo' => $logo, 'favicon' => $favicon]) as $path) {
            $media->registerFromPath('public', $path);
        }

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات سایت با موفقیت ذخیره شد.')
            ->send();
    }
}
