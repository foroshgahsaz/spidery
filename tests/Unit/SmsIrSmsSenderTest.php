<?php

namespace Tests\Unit;

use App\Contracts\SmsSender;
use App\Services\Settings\SettingsService;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsIrSmsSender;
use App\Services\Sms\SmsSenderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsIrSmsSenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_otp_via_smsir_verify_endpoint(): void
    {
        Http::fake([
            SmsIrSmsSender::VERIFY_URL => Http::response([
                'status' => 1,
                'message' => 'موفق',
                'data' => ['messageId' => 89545112, 'cost' => 1.0],
            ], 200),
        ]);

        $this->enableSmsIr();

        app(SmsIrSmsSender::class)->sendOtp('09123334444', '123456');

        Http::assertSent(function ($request) {
            return $request->url() === SmsIrSmsSender::VERIFY_URL
                && $request->hasHeader('X-API-KEY', 'test-smsir-key')
                && $request['mobile'] === '9123334444'
                && $request['templateId'] === 123456
                && $request['parameters'][0]['name'] === 'Code'
                && $request['parameters'][0]['value'] === '123456';
        });
    }

    public function test_it_sends_transactional_sms_via_bulk_endpoint(): void
    {
        Http::fake([
            SmsIrSmsSender::BULK_URL => Http::response([
                'status' => 1,
                'message' => 'موفق',
                'data' => ['packId' => 'abc', 'messageIds' => [1], 'cost' => 2.0],
            ], 200),
        ]);

        $this->enableSmsIr();

        app(SmsIrSmsSender::class)->sendTransactional('09123334444', 'سفارش ثبت شد');

        Http::assertSent(function ($request) {
            return $request->url() === SmsIrSmsSender::BULK_URL
                && $request['lineNumber'] === 30004505000017
                && $request['mobiles'] === ['9123334444']
                && $request['messageText'] === 'سفارش ثبت شد';
        });
    }

    public function test_it_throws_when_smsir_returns_logical_error(): void
    {
        Http::fake([
            SmsIrSmsSender::VERIFY_URL => Http::response([
                'status' => 113,
                'message' => 'قالب یافت نشد',
            ], 400),
        ]);

        $this->enableSmsIr();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('sms.ir: قالب یافت نشد');

        app(SmsIrSmsSender::class)->sendOtp('09123334444', '123456');
    }

    public function test_factory_prefers_enabled_smsir_over_log_driver(): void
    {
        $this->enableSmsIr();

        $sender = app(SmsSenderFactory::class)->make();

        $this->assertInstanceOf(SmsIrSmsSender::class, $sender);
        $this->assertInstanceOf(SmsIrSmsSender::class, app(SmsSender::class));
    }

    public function test_factory_falls_back_to_log_when_nothing_is_enabled(): void
    {
        config(['sms.driver' => 'log']);

        $this->assertInstanceOf(LogSmsSender::class, app(SmsSenderFactory::class)->make());
    }

    public function test_normalize_mobile_strips_iran_prefixes(): void
    {
        $sender = app(SmsIrSmsSender::class);

        $this->assertSame('9123334444', $sender->normalizeMobile('09123334444'));
        $this->assertSame('9123334444', $sender->normalizeMobile('+989123334444'));
        $this->assertSame('9123334444', $sender->normalizeMobile('00989123334444'));
    }

    protected function enableSmsIr(): void
    {
        app(SettingsService::class)->setMany('smsir', [
            'enabled' => true,
            'api_key' => 'test-smsir-key',
            'template_id' => '123456',
            'otp_parameter' => 'Code',
            'line_number' => '30004505000017',
        ]);
    }
}
