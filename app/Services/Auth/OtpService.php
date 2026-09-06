<?php

namespace App\Services\Auth;

use App\Contracts\SmsSender;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OtpService
{
    public function __construct(
        protected SmsSender $sms,
        protected SettingsService $settings
    ) {}

    public function send(string $phone): void
    {
        $code = $this->generate($phone);
        $this->sms->sendOtp($phone, $code);
    }

    public function generate(string $phone): string
    {
        $throttleKey = "otp:throttle:{$phone}";

        if (Cache::has($throttleKey)) {
            throw new \RuntimeException('ارسال مجدد هنوز فعال نیست. تا پایان شمارنده صبر کنید.');
        }

        $code = str_pad((string) random_int(0, 999999), config('shop.otp.length'), '0', STR_PAD_LEFT);

        Cache::put(
            $this->cacheKey($phone),
            $code,
            now()->addMinutes(config('shop.otp.expires_minutes'))
        );

        Cache::put($throttleKey, true, now()->addSeconds($this->settings->otpResendSeconds()));

        return $code;
    }

    public function verify(string $phone, string $code): bool
    {
        $cached = Cache::get($this->cacheKey($phone));

        if ($cached === null || ! hash_equals((string) $cached, $code)) {
            return false;
        }

        Cache::forget($this->cacheKey($phone));

        return true;
    }

    public function markPhoneVerified(User $user): void
    {
        $user->forceFill(['phone_verified_at' => now()])->save();
    }

    protected function cacheKey(string $phone): string
    {
        return 'otp:phone:'.Str::slug($phone);
    }
}
