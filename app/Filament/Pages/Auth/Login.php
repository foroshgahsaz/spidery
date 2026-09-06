<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\Auth\AdminLoginGuard;
use App\Services\Auth\OtpService;
use App\Services\Settings\SettingsService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected static string $layout = 'filament.layouts.admin-login';

    protected static string $view = 'filament.pages.auth.login';

    public string $activeLoginTab = 'mobile';

    public string $otpStep = 'phone';

    public string $otpPhone = '';

    public string $otpCode = '';

    public int $otpSentAt = 0;

    public function sendAdminOtp(OtpService $otp): void
    {
        $this->activeLoginTab = 'mobile';

        $this->validate([
            'otpPhone' => ['required', 'regex:/^09\d{9}$/'],
        ], [
            'otpPhone.required' => 'لطفا شماره موبایل خود را وارد کنید.',
            'otpPhone.regex' => 'شماره موبایل باید با 09 شروع شود و ۱۱ رقم باشد.',
        ]);

        $user = User::query()->where('phone', $this->otpPhone)->first();
        app(AdminLoginGuard::class)->assertAllowed($user, 'otpPhone');

        $key = 'admin-otp-send:'.$this->otpPhone;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'otpPhone' => 'تلاش‌های زیاد. لطفاً بعداً دوباره امتحان کنید.',
            ]);
        }

        try {
            $otp->send($this->otpPhone);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages([
                'otpPhone' => $e->getMessage(),
                'otpCode' => $e->getMessage(),
            ]);
        }

        RateLimiter::hit($key, 300);
        $this->otpStep = 'otp';
        $this->otpCode = '';
        $this->otpSentAt = time();
    }

    public function verifyAdminOtp(OtpService $otp): ?LoginResponse
    {
        $this->activeLoginTab = 'mobile';

        $this->validate([
            'otpPhone' => ['required', 'regex:/^09\d{9}$/'],
            'otpCode' => ['required', 'digits:'.config('shop.otp.length')],
        ], [
            'otpCode.required' => 'لطفا کد تایید را وارد کنید.',
            'otpCode.digits' => 'کد تایید باید ۶ رقم باشد.',
        ]);

        $key = 'admin-otp-verify:'.$this->otpPhone;

        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'otpCode' => 'تلاش‌های زیاد. لطفاً کد جدید درخواست کنید.',
            ]);
        }

        if (! $otp->verify($this->otpPhone, $this->otpCode)) {
            RateLimiter::hit($key, 120);
            throw ValidationException::withMessages([
                'otpCode' => 'کد تایید نامعتبر یا منقضی شده است.',
            ]);
        }

        RateLimiter::clear($key);

        $user = User::query()->where('phone', $this->otpPhone)->first();
        app(AdminLoginGuard::class)->assertAllowed($user, 'otpCode');

        Filament::auth()->login($user, remember: true);

        if (
            ($user instanceof FilamentUser)
            && (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            throw ValidationException::withMessages([
                'otpCode' => 'امکان ورود مدیریت با این شماره وجود ندارد.',
            ]);
        }

        $otp->markPhoneVerified($user);

        $user->forceFill([
            'last_login_at' => now(),
            'login_count' => $user->login_count + 1,
        ])->save();

        session()->regenerate();

        return app(LoginResponse::class);
    }

    public function backToAdminPhone(): void
    {
        $this->otpStep = 'phone';
        $this->otpCode = '';
        $this->resetValidation();
    }

    public function otpResendSeconds(): int
    {
        return app(SettingsService::class)->otpResendSeconds();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('نام کاربری')
            ->placeholder('ایمیل یا شماره موبایل')
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes([
                'tabindex' => 1,
                'id' => 'usernameInput',
            ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('رمز عبور')
            ->placeholder('رمز عبور خود را وارد کنید')
            ->password()
            ->revealable()
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes([
                'tabindex' => 2,
                'id' => 'passwordInput',
            ]);
    }

    protected function getRememberFormComponent(): Component
    {
        return parent::getRememberFormComponent()
            ->extraAttributes(['class' => 'admin-login-remember']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $login = trim((string) ($data['email'] ?? ''));

        if (str_contains($login, '@')) {
            return [
                'email' => $login,
                'password' => $data['password'],
            ];
        }

        return [
            'phone' => $login,
            'password' => $data['password'],
        ];
    }

    public function authenticate(): ?LoginResponse
    {
        $this->activeLoginTab = 'username';

        return parent::authenticate();
    }

    protected function throwFailureValidationException(): never
    {
        $this->activeLoginTab = 'username';

        throw ValidationException::withMessages([
            'data.email' => 'نام کاربری یا رمز عبور اشتباه است.',
        ]);
    }

    public function getTitle(): string
    {
        return 'ورود';
    }

    public function getHeading(): string
    {
        return '';
    }
}
