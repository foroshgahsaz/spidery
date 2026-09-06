<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use App\Services\Settings\SettingsService;

class SmsSenderFactory
{
    public function __construct(
        protected SettingsService $settings
    ) {}

    public function make(): SmsSender
    {
        $smsIr = $this->settings->smsIr();

        if (($smsIr['enabled'] ?? false) && filled($smsIr['api_key'])) {
            return app(SmsIrSmsSender::class);
        }

        $kavenegar = $this->settings->kavenegar();

        if (($kavenegar['enabled'] ?? false) && filled($kavenegar['api_key'])) {
            return app(KavenegarSmsSender::class);
        }

        return match (config('sms.driver')) {
            'smsir', 'sms.ir' => app(SmsIrSmsSender::class),
            'kavenegar' => app(KavenegarSmsSender::class),
            default => app(LogSmsSender::class),
        };
    }
}
