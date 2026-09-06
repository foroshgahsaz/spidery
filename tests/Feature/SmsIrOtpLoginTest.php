<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Services\Auth\OtpService;
use App\Services\Settings\SettingsService;
use App\Services\Sms\SmsIrSmsSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SmsIrOtpLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_login_sends_code_through_smsir_verify(): void
    {
        Http::fake([
            SmsIrSmsSender::VERIFY_URL => Http::response([
                'status' => 1,
                'message' => 'موفق',
                'data' => ['messageId' => 1, 'cost' => 1],
            ], 200),
        ]);

        app(SettingsService::class)->setMany('smsir', [
            'enabled' => true,
            'api_key' => 'test-smsir-key',
            'template_id' => '123456',
            'otp_parameter' => 'Code',
        ]);

        $phone = '09125556666';

        Livewire::test(Login::class)
            ->set('phone', $phone)
            ->call('sendOtp')
            ->assertSet('step', 'otp')
            ->assertHasNoErrors();

        $code = Cache::get('otp:phone:'.$phone);

        $this->assertNotEmpty($code);

        Http::assertSent(function ($request) use ($code) {
            return $request->url() === SmsIrSmsSender::VERIFY_URL
                && $request['mobile'] === '9125556666'
                && $request['parameters'][0]['value'] === $code;
        });

        Livewire::test(Login::class)
            ->set('phone', $phone)
            ->set('step', 'otp')
            ->set('otp', $code)
            ->call('verifyOtp')
            ->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_otp_service_uses_bound_smsir_sender(): void
    {
        Http::fake([
            SmsIrSmsSender::VERIFY_URL => Http::response([
                'status' => 1,
                'message' => 'موفق',
                'data' => ['messageId' => 1, 'cost' => 1],
            ], 200),
        ]);

        app(SettingsService::class)->setMany('smsir', [
            'enabled' => true,
            'api_key' => 'test-smsir-key',
            'template_id' => '654321',
            'otp_parameter' => 'CODE',
        ]);

        app(OtpService::class)->send('09120000000');

        Http::assertSent(function ($request) {
            return $request['templateId'] === 654321
                && $request['parameters'][0]['name'] === 'CODE'
                && $request['mobile'] === '9120000000';
        });
    }
}
