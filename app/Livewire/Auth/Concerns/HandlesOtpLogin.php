<?php

namespace App\Livewire\Auth\Concerns;

use App\Models\User;
use App\Services\Auth\OtpService;
use App\Services\Auth\ShopLoginGuard;
use App\Services\Cart\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait HandlesOtpLogin
{
    public string $step = 'phone';

    public string $phone = '';

    public string $otp = '';

    public int $otpSentAt = 0;

    public function sendOtp(OtpService $otp): void
    {
        $this->validate([
            'phone' => ['required', 'regex:/^09\d{9}$/'],
        ], [
            'phone.regex' => 'شماره موبایل باید با 09 شروع شود و ۱۱ رقم باشد.',
        ]);

        app(ShopLoginGuard::class)->assertAllowed(
            User::query()->where('phone', $this->phone)->first(),
            'phone'
        );

        $key = 'otp-send:'.$this->phone;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'phone' => 'تلاش‌های زیاد. لطفاً بعداً دوباره امتحان کنید.',
            ]);
        }

        try {
            $otp->send($this->phone);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages([
                'phone' => $e->getMessage(),
                'otp' => $e->getMessage(),
            ]);
        }

        RateLimiter::hit($key, 300);
        $this->step = 'otp';
        $this->otpSentAt = time();
        session()->flash('login_success', 'کد تایید به شماره شما ارسال شد.');
    }

    public function verifyOtp(OtpService $otp, CartService $cart): void
    {
        $this->validate([
            'phone' => ['required', 'regex:/^09\d{9}$/'],
            'otp' => ['required', 'digits:'.config('shop.otp.length')],
        ]);

        $key = 'otp-verify:'.$this->phone;

        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'otp' => 'تلاش‌های زیاد. لطفاً کد جدید درخواست کنید.',
            ]);
        }

        if (! $otp->verify($this->phone, $this->otp)) {
            RateLimiter::hit($key, 120);
            throw ValidationException::withMessages([
                'otp' => 'کد تایید نامعتبر یا منقضی شده است.',
            ]);
        }

        RateLimiter::clear($key);

        $user = User::query()->where('phone', $this->phone)->first();

        if (! $user) {
            $user = User::query()->create([
                'phone' => $this->phone,
                'name' => 'کاربر '.substr($this->phone, -4),
                'status' => true,
                'password' => Hash::make(Str::random(32)),
            ]);
        }

        app(ShopLoginGuard::class)->assertAllowed($user, 'otp');

        $otp->markPhoneVerified($user);

        $user->forceFill([
            'last_login_at' => now(),
            'login_count' => $user->login_count + 1,
        ])->save();

        $this->afterSuccessfulLogin($user, $cart);
    }

    public function backToPhone(): void
    {
        $this->step = 'phone';
        $this->otp = '';
        $this->resetValidation();
    }

    protected function resetLoginForm(): void
    {
        $this->step = 'phone';
        $this->phone = '';
        $this->otp = '';
        $this->resetValidation();
    }

    protected function afterSuccessfulLogin(User $user, CartService $cart): void
    {
        Auth::login($user, remember: true);
        $cart->mergeGuestCartIntoUser($user);

        $this->redirectIntended(default: route('account.dashboard'), navigate: true);
    }
}
