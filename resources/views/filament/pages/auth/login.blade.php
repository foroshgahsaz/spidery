<div class="admin-login-root">
    <div class="grid-pattern"></div>

    <div class="login-container">
        <div class="left-panel">
            <div class="brand-content">
                <div class="logo-container">
                    <div class="logo">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h1 class="brand-name">{{ filament()->getBrandName() }}</h1>
                </div>
                <p class="brand-description">به سامانه خوش آمدید</p>
            </div>
        </div>

        <div class="right-panel">
            <div class="login-box">
                <a href="{{ route('home') }}" class="back-link">
                    <i class="fas fa-arrow-right"></i>
                    بازگشت به فروشگاه
                </a>

                @php($activeTab = $this->activeLoginTab)

                <div id="loginPage" data-active-tab="{{ $activeTab }}" @if($otpStep === 'otp') style="display: none;" @endif>
                    <h2 class="login-title">ورود</h2>
                    <p class="login-subtitle">برای ورود شماره موبایل یا نام کاربری خود را وارد کنید!</p>

                    <div class="auth-tabs">
                        <button type="button"
                                class="auth-tab @if($activeTab === 'mobile') active @endif"
                                data-auth-tab="mobile">
                            <i class="fas fa-mobile-alt ms-2"></i>
                            ورود با شماره موبایل
                        </button>
                        <button type="button"
                                class="auth-tab @if($activeTab === 'username') active @endif"
                                data-auth-tab="username">
                            <i class="fas fa-user ms-2"></i>
                            ورود با نام کاربری
                        </button>
                    </div>

                    <form id="mobileForm" wire:submit="sendAdminOtp" @if($activeTab === 'username') style="display: none;" @endif>
                        <div class="mb-3">
                            <label class="form-label" for="mobileInput">شماره موبایل<span class="required">*</span></label>
                            <input type="tel"
                                   class="form-control"
                                   placeholder="09123456789"
                                   id="mobileInput"
                                   wire:model="otpPhone"
                                   autocomplete="tel">
                            <div class="error-message @error('otpPhone') show @enderror" id="mobileError">
                                @error('otpPhone') {{ $message }} @enderror
                            </div>
                        </div>
                        <button type="submit" class="btn-login" wire:loading.attr="disabled" wire:target="sendAdminOtp">
                            <span wire:loading.remove wire:target="sendAdminOtp">دریافت رمز یکبار مصرف</span>
                            <span wire:loading wire:target="sendAdminOtp">در حال ارسال...</span>
                        </button>
                    </form>

                    <div id="usernameForm" @if($activeTab !== 'username') style="display: none;" @endif>
                        <x-filament-panels::form id="adminLoginForm" wire:submit="authenticate">
                            {{ $this->form }}

                            @error('data.email')
                                <div class="admin-login-form-error show">{{ $message }}</div>
                            @enderror

                            <button type="submit" class="btn-login filament-auth-submit">ورود</button>
                        </x-filament-panels::form>
                    </div>
                </div>

                <div id="otpPage"
                     data-resend-seconds="{{ $this->otpResendSeconds() }}"
                     wire:key="admin-otp-{{ $otpSentAt }}"
                     @if($otpStep !== 'otp') style="display: none;" @endif>
                    <h2 class="login-title">تایید شماره موبایل</h2>
                    <p class="login-subtitle">کد 6 رقمی ارسال شده به شماره <strong id="displayMobile">{{ $otpPhone }}</strong> را وارد کنید</p>

                    <form id="otpForm" wire:submit="verifyAdminOtp">
                        <input type="hidden" wire:model="otpCode" id="otpCodeHidden">
                        <div class="otp-container">
                            <input type="text" maxlength="1" class="otp-input" data-index="0" inputmode="numeric" autocomplete="one-time-code">
                            <input type="text" maxlength="1" class="otp-input" data-index="1" inputmode="numeric">
                            <input type="text" maxlength="1" class="otp-input" data-index="2" inputmode="numeric">
                            <input type="text" maxlength="1" class="otp-input" data-index="3" inputmode="numeric">
                            <input type="text" maxlength="1" class="otp-input" data-index="4" inputmode="numeric">
                            <input type="text" maxlength="1" class="otp-input" data-index="5" inputmode="numeric">
                        </div>
                        <div class="error-message text-center @error('otpCode') show @enderror" id="otpError">
                            @error('otpCode') {{ $message }} @enderror
                        </div>

                        <div class="otp-timer">
                            <span id="timer" class="otp-countdown">{{ sprintf('%02d:%02d', intdiv($this->otpResendSeconds(), 60), $this->otpResendSeconds() % 60) }}</span>
                            <br>
                            <button type="button"
                                    class="resend-link"
                                    id="resendLink"
                                    hidden
                                    disabled
                                    wire:click="sendAdminOtp"
                                    wire:loading.attr="disabled">
                                ارسال مجدد کد
                            </button>
                        </div>

                        <button type="submit" class="btn-login mt-4" wire:loading.attr="disabled" wire:target="verifyAdminOtp">
                            <span wire:loading.remove wire:target="verifyAdminOtp">تایید و ورود</span>
                            <span wire:loading wire:target="verifyAdminOtp">در حال بررسی...</span>
                        </button>
                    </form>

                    <div class="signup-link">
                        <button type="button" class="resend-link" id="editPhoneLink" wire:click="backToAdminPhone">
                            ویرایش شماره موبایل
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button type="button" class="dark-mode-toggle" onclick="toggleDarkMode()">
        <i class="fas fa-moon"></i>
    </button>
</div>
