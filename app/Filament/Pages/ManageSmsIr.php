<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Services\Settings\SettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageSmsIr extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'sms.ir';

    protected static ?string $slug = 'sms-ir';

    protected static ?string $title = 'تنظیمات پیامک sms.ir';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $smsIr = $settings->smsIr();

        $this->form->fill([
            'enabled' => $smsIr['enabled'],
            'api_key' => $smsIr['api_key'],
            'template_id' => $smsIr['template_id'] ?: null,
            'otp_parameter' => $smsIr['otp_parameter'],
            'line_number' => $smsIr['line_number'],
            'resend_minutes' => $smsIr['resend_minutes'],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ورود با کد یکبار مصرف (Verify)')
                    ->description('از متد REST ارسال Verify در sms.ir استفاده می‌شود. قالب را در پنل sms.ir بخش ارسال سریع بسازید؛ متغیر کد باید مثل #CODE# باشد.')
                    ->schema([
                        Forms\Components\Toggle::make('enabled')
                            ->label('فعال‌سازی sms.ir')
                            ->helperText('با فعال‌سازی، ورود OTP فروشگاه از این سامانه ارسال می‌شود.'),
                        Forms\Components\TextInput::make('api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->helperText('کلید را از بخش برنامه‌نویسان پنل sms.ir کپی کنید و با هدر X-API-KEY ارسال می‌شود.'),
                        Forms\Components\TextInput::make('template_id')
                            ->label('شناسه قالب Verify')
                            ->numeric()
                            ->placeholder('123456')
                            ->helperText('در محیط Sandbox قالب پیش‌فرض ۱۲۳۴۵۶ با متن «کد تایید شما: #CODE#» است.'),
                        Forms\Components\TextInput::make('otp_parameter')
                            ->label('نام پارامتر قالب')
                            ->placeholder('Code')
                            ->helperText('همان کلید داخل قالب، بدون #. پیش‌فرض: Code'),
                        Forms\Components\TextInput::make('resend_minutes')
                            ->label('زمان ارسال مجدد (دقیقه)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(15)
                            ->default(2)
                            ->helperText('شمارنده «ارسال مجدد کد» در ورود فروشگاه و ادمین. پیش‌فرض ۲ دقیقه.'),
                    ])->columns(2),
                Forms\Components\Section::make('پیامک سفارش (Bulk)')
                    ->schema([
                        Forms\Components\TextInput::make('line_number')
                            ->label('شماره خط اختصاصی')
                            ->placeholder('30004505000017')
                            ->helperText('فقط برای پیامک وضعیت سفارش لازم است. ورود OTP به خط نیاز ندارد.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(SettingsService $settings): void
    {
        $data = $this->form->getState();

        $settings->setMany('smsir', [
            'enabled' => $data['enabled'] ?? false,
            'api_key' => $data['api_key'] ?? '',
            'template_id' => $data['template_id'] ?? '',
            'otp_parameter' => $data['otp_parameter'] ?: 'Code',
            'line_number' => $data['line_number'] ?? '',
            'resend_minutes' => max(1, min(15, (int) ($data['resend_minutes'] ?? 2))),
        ]);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات sms.ir با موفقیت ذخیره شد.')
            ->send();
    }
}
