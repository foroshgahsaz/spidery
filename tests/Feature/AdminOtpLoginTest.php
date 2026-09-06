<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login as AdminLogin;
use App\Livewire\Auth\Login as ShopLogin;
use App\Models\User;
use App\Services\Auth\AdminLoginGuard;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOtpLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_otp(): void
    {
        $admin = User::factory()->admin()->create([
            'phone' => '09304428006',
        ]);

        Livewire::test(AdminLogin::class)
            ->set('otpPhone', $admin->phone)
            ->call('sendAdminOtp')
            ->assertSet('otpStep', 'otp')
            ->assertHasNoErrors();

        $code = Cache::get('otp:phone:'.$admin->phone);
        $this->assertNotEmpty($code);

        Livewire::test(AdminLogin::class)
            ->set('otpPhone', $admin->phone)
            ->set('otpStep', 'otp')
            ->set('otpCode', $code)
            ->call('verifyAdminOtp')
            ->assertRedirect();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_customer_cannot_request_admin_otp(): void
    {
        $customer = User::factory()->create([
            'phone' => '09120000001',
        ]);

        Livewire::test(AdminLogin::class)
            ->set('otpPhone', $customer->phone)
            ->call('sendAdminOtp')
            ->assertHasErrors(['otpPhone']);

        $this->assertFalse(Cache::has('otp:phone:'.$customer->phone));
    }

    public function test_unknown_phone_cannot_request_admin_otp(): void
    {
        Livewire::test(AdminLogin::class)
            ->set('otpPhone', '09129999999')
            ->call('sendAdminOtp')
            ->assertHasErrors(['otpPhone']);
    }

    public function test_admin_guard_rejects_customers(): void
    {
        $customer = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(AdminLoginGuard::class)->assertAllowed($customer);
    }

    public function test_shop_login_shows_resend_timer(): void
    {
        Livewire::test(ShopLogin::class)
            ->set('phone', '09121112233')
            ->call('sendOtp')
            ->assertSet('step', 'otp')
            ->assertSee('ارسال مجدد تا')
            ->assertSee('remaining: 120');
    }

    public function test_admin_otp_screen_lets_user_edit_phone_and_hides_resend(): void
    {
        $admin = User::factory()->admin()->create([
            'phone' => '09304428006',
        ]);

        $html = Livewire::test(AdminLogin::class)
            ->set('otpPhone', $admin->phone)
            ->call('sendAdminOtp')
            ->assertSee('ویرایش شماره موبایل')
            ->assertDontSee('بازگشت به صفحه ورود')
            ->html();

        $this->assertStringContainsString('id="resendLink"', $html);
        $this->assertMatchesRegularExpression('/id="resendLink"[^>]*(hidden|disabled)/', $html);
    }

    public function test_admin_cannot_resend_otp_before_throttle_expires(): void
    {
        $admin = User::factory()->admin()->create([
            'phone' => '09304428006',
        ]);

        Livewire::test(AdminLogin::class)
            ->set('otpPhone', $admin->phone)
            ->call('sendAdminOtp')
            ->assertSet('otpStep', 'otp')
            ->call('sendAdminOtp')
            ->assertHasErrors(['otpPhone']);
    }

    public function test_admin_login_page_no_longer_says_sms_is_unavailable(): void
    {
        $html = Livewire::test(AdminLogin::class)->html();

        $this->assertStringNotContainsString('ورود با پیامک هنوز فعال نشده', $html);
        $this->assertStringContainsString('wire:submit="sendAdminOtp"', $html);
        $this->assertStringContainsString('data-resend-seconds="120"', $html);
        $this->assertStringNotContainsString('بازگشت به صفحه ورود', $html);
    }

    public function test_resend_minutes_setting_defaults_to_two(): void
    {
        $settings = app(SettingsService::class);

        $this->assertSame(2, $settings->otpResendMinutes());
        $this->assertSame(120, $settings->otpResendSeconds());

        $settings->set('smsir', 'resend_minutes', '3');

        $this->assertSame(3, $settings->otpResendMinutes());
        $this->assertSame(180, $settings->otpResendSeconds());
    }
}
