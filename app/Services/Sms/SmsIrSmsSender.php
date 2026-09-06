<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SmsIrSmsSender implements SmsSender
{
    public const VERIFY_URL = 'https://api.sms.ir/v1/send/verify';

    public const BULK_URL = 'https://api.sms.ir/v1/send/bulk';

    public function __construct(
        protected SettingsService $settings
    ) {}

    public function sendOtp(string $phone, string $code): void
    {
        $config = $this->settings->smsIr();
        $apiKey = (string) ($config['api_key'] ?? '');
        $templateId = (int) ($config['template_id'] ?? 0);
        $parameter = (string) ($config['otp_parameter'] ?: 'Code');

        if ($apiKey === '') {
            throw new RuntimeException('کلید API سامانه sms.ir تنظیم نشده است.');
        }

        if ($templateId < 1) {
            throw new RuntimeException('شناسه قالب Verify در پنل sms.ir تنظیم نشده است.');
        }

        $response = Http::timeout(15)
            ->acceptJson()
            ->withHeaders($this->headers($apiKey))
            ->post(self::VERIFY_URL, [
                'mobile' => $this->normalizeMobile($phone),
                'templateId' => $templateId,
                'parameters' => [
                    [
                        'name' => $parameter,
                        'value' => $code,
                    ],
                ],
            ]);

        $this->throwIfFailed($response);
    }

    public function sendTransactional(string $phone, string $message): void
    {
        $config = $this->settings->smsIr();
        $apiKey = (string) ($config['api_key'] ?? '');
        $lineNumber = (string) ($config['line_number'] ?? '');

        if ($apiKey === '') {
            throw new RuntimeException('کلید API سامانه sms.ir تنظیم نشده است.');
        }

        if ($lineNumber === '') {
            throw new RuntimeException('شماره خط اختصاصی sms.ir برای پیامک‌های سفارش تنظیم نشده است.');
        }

        $response = Http::timeout(15)
            ->acceptJson()
            ->withHeaders($this->headers($apiKey))
            ->post(self::BULK_URL, [
                'lineNumber' => (int) $lineNumber,
                'messageText' => $message,
                'mobiles' => [$this->normalizeMobile($phone)],
                'sendDateTime' => null,
            ]);

        $this->throwIfFailed($response);
    }

    public function normalizeMobile(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? $phone;

        if (str_starts_with($digits, '0098')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) >= 12) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }

    /** @return array<string, string> */
    protected function headers(string $apiKey): array
    {
        return [
            'X-API-KEY' => $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    protected function throwIfFailed(Response $response): void
    {
        if ($response->status() === 401) {
            throw new RuntimeException('کلید API سامانه sms.ir نامعتبر است.');
        }

        if ($response->status() === 429) {
            throw new RuntimeException('تعداد درخواست پیامک بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.');
        }

        $status = (int) $response->json('status');

        if ($response->successful() && $status === 1) {
            return;
        }

        $message = (string) ($response->json('message') ?: 'ارسال پیامک با خطا مواجه شد.');

        throw new RuntimeException('sms.ir: '.$message);
    }
}
