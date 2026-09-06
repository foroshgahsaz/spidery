// ============= Validation & Error Handling =============
const ErrorMessages = {
    mobile: {
        required: 'لطفا شماره موبایل خود را وارد کنید',
        invalid: 'شماره موبایل باید 11 رقم و با 09 شروع شود',
        format: 'فقط اعداد مجاز هستند',
    },
    username: {
        required: 'لطفا نام کاربری خود را وارد کنید',
        minLength: 'نام کاربری باید حداقل 3 کاراکتر باشد',
        maxLength: 'نام کاربری نباید بیشتر از 20 کاراکتر باشد',
    },
    password: {
        required: 'لطفا رمز عبور خود را وارد کنید',
        minLength: 'رمز عبور باید حداقل 6 کاراکتر باشد',
    },
    otp: {
        required: 'لطفا کد تایید را وارد کنید',
        incomplete: 'لطفا کد 6 رقمی را کامل وارد کنید',
        invalid: 'کد وارد شده معتبر نیست',
        unavailable: 'ورود با پیامک هنوز فعال نشده. از تب «ورود با نام کاربری» استفاده کنید.',
    },
};

function showError(fieldId, message) {
    const errorElement = document.getElementById(fieldId + 'Error');
    const inputElement = document.getElementById(fieldId + 'Input') || document.querySelector(`#${fieldId}`);

    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.add('show');
    }

    if (inputElement) {
        inputElement.classList.add('error');
    }
}

function clearError(fieldId) {
    const errorElement = document.getElementById(fieldId + 'Error');
    const inputElement = document.getElementById(fieldId + 'Input') || document.querySelector(`#${fieldId}`);

    if (errorElement) {
        errorElement.textContent = '';
        errorElement.classList.remove('show');
    }

    if (inputElement) {
        inputElement.classList.remove('error');
    }
}

function validateMobile(mobile) {
    if (!mobile || mobile.trim() === '') {
        return { valid: false, message: ErrorMessages.mobile.required };
    }

    if (!/^\d+$/.test(mobile)) {
        return { valid: false, message: ErrorMessages.mobile.format };
    }

    if (mobile.length !== 11 || !mobile.startsWith('09')) {
        return { valid: false, message: ErrorMessages.mobile.invalid };
    }

    return { valid: true };
}

function validateOTP(otp) {
    if (!otp || otp.trim() === '') {
        return { valid: false, message: ErrorMessages.otp.required };
    }

    if (otp.length !== 6) {
        return { valid: false, message: ErrorMessages.otp.incomplete };
    }

    if (!/^\d{6}$/.test(otp)) {
        return { valid: false, message: ErrorMessages.otp.invalid };
    }

    return { valid: true };
}

let currentTab = 'mobile';
let timerInterval;
let timerStartedFor = null;

function switchTab(tab) {
    currentTab = tab;

    document.querySelectorAll('.auth-tab').forEach((button) => {
        button.classList.toggle('active', button.dataset.authTab === tab);
    });

    const mobileForm = document.getElementById('mobileForm');
    const usernameForm = document.getElementById('usernameForm');
    const loginPage = document.getElementById('loginPage');

    if (loginPage) {
        loginPage.dataset.activeTab = tab;
    }

    clearError('mobile');
    clearError('otp');

    if (tab === 'mobile') {
        mobileForm.style.display = 'block';
        usernameForm.style.display = 'none';
        sessionStorage.removeItem('adminLoginTab');
    } else {
        mobileForm.style.display = 'none';
        usernameForm.style.display = 'block';
        sessionStorage.setItem('adminLoginTab', 'username');
    }
}

function hasUsernameFormErrors() {
    const usernameForm = document.getElementById('usernameForm');
    if (!usernameForm) {
        return false;
    }

    return Boolean(
        usernameForm.querySelector('.fi-fo-field-wrp-error-message')
        || usernameForm.querySelector('.admin-login-form-error.show')
        || usernameForm.querySelector('[aria-invalid="true"]'),
    );
}

function restoreLoginTab() {
    const loginPage = document.getElementById('loginPage');
    if (!loginPage) {
        return;
    }

    const serverTab = loginPage.dataset.activeTab;
    const savedTab = serverTab === 'username'
        ? 'username'
        : (sessionStorage.getItem('adminLoginTab') === 'username' || hasUsernameFormErrors())
            ? 'username'
            : serverTab || 'mobile';

    if (savedTab === 'username') {
        switchTab('username');
    }
}

function togglePassword() {
    const passwordInput = document.getElementById('passwordInput');
    const passwordIcon = document.getElementById('passwordIcon');

    if (!passwordInput || !passwordIcon) {
        return;
    }

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.classList.remove('fa-eye');
        passwordIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        passwordIcon.classList.remove('fa-eye-slash');
        passwordIcon.classList.add('fa-eye');
    }
}

function backToLogin() {
    clearInterval(timerInterval);
    document.getElementById('loginPage').style.display = 'block';
    document.getElementById('otpPage').style.display = 'none';
    clearError('otp');
    document.querySelectorAll('.otp-input').forEach((input) => {
        input.value = '';
        input.classList.remove('error');
    });
}

function formatOtpClock(totalSeconds) {
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

function hideAdminResend() {
    const resendLink = document.getElementById('resendLink');
    if (!resendLink) {
        return;
    }

    resendLink.hidden = true;
    resendLink.disabled = true;
    resendLink.classList.add('disabled');
}

function showAdminResend() {
    const resendLink = document.getElementById('resendLink');
    if (!resendLink) {
        return;
    }

    resendLink.hidden = false;
    resendLink.disabled = false;
    resendLink.classList.remove('disabled');
}

function startTimer() {
    const otpPage = document.getElementById('otpPage');
    const timerElement = document.getElementById('timer');

    if (!otpPage || !timerElement || otpPage.style.display === 'none') {
        return;
    }

    const timerKey = otpPage.getAttribute('wire:key') || otpPage.dataset.resendSeconds;
    if (timerStartedFor === timerKey) {
        return;
    }

    const timeLeftStart = parseInt(otpPage.dataset.resendSeconds || '120', 10);
    let timeLeft = Number.isFinite(timeLeftStart) && timeLeftStart > 0 ? timeLeftStart : 120;

    timerStartedFor = timerKey;
    clearInterval(timerInterval);
    hideAdminResend();
    timerElement.hidden = false;
    timerElement.textContent = formatOtpClock(timeLeft);

    timerInterval = setInterval(() => {
        timeLeft -= 1;

        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            timerElement.hidden = true;
            showAdminResend();
            return;
        }

        timerElement.textContent = formatOtpClock(timeLeft);
    }, 1000);
}

function toggleDarkMode() {
    document.body.classList.toggle('admin-login-dark');
}

document.addEventListener('DOMContentLoaded', () => {
    restoreLoginTab();

    document.querySelectorAll('[data-auth-tab]').forEach((button) => {
        button.addEventListener('click', () => switchTab(button.dataset.authTab));
    });

    document.getElementById('usernameForm')?.addEventListener('submit', () => {
        sessionStorage.setItem('adminLoginTab', 'username');
    }, true);

    const mobileInput = document.getElementById('mobileInput');
    mobileInput?.addEventListener('input', () => clearError('mobile'));

    bindOtpInputs();
    bindOtpFormSubmit();

    if (window.Livewire) {
        bindLoginLivewireHooks();
    } else {
        document.addEventListener('livewire:init', bindLoginLivewireHooks, { once: true });
    }
});

function syncAdminOtpCode() {
    const otpInputs = document.querySelectorAll('.otp-input');
    let code = '';
    otpInputs.forEach((input) => {
        code += input.value;
    });

    const hidden = document.getElementById('otpCodeHidden');
    if (hidden && hidden.value !== code) {
        hidden.value = code;
        hidden.dispatchEvent(new Event('input', { bubbles: true }));
    }

    const root = document.querySelector('.admin-login-root');
    const wireId = root?.closest('[wire\\:id]')?.getAttribute('wire:id')
        || document.querySelector('[wire\\:id]')?.getAttribute('wire:id');

    if (wireId && window.Livewire) {
        const component = Livewire.find(wireId);
        if (component) {
            component.set('otpCode', code);
        }
    }
}

function bindOtpInputs() {
    const otpInputs = document.querySelectorAll('.otp-input');

    otpInputs.forEach((input, index) => {
        if (input.dataset.bound === '1') {
            return;
        }
        input.dataset.bound = '1';

        input.addEventListener('input', function () {
            clearError('otp');
            otpInputs.forEach((item) => item.classList.remove('error'));
            this.value = this.value.replace(/[^0-9]/g, '');

            if (this.value.length === 1 && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }

            syncAdminOtpCode();
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace' && this.value === '' && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
    });
}

function bindOtpFormSubmit() {
    const form = document.getElementById('otpForm');
    if (!form || form.dataset.bound === '1') {
        return;
    }

    form.dataset.bound = '1';
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        event.stopImmediatePropagation();

        const otpInputs = document.querySelectorAll('.otp-input');
        let code = '';
        otpInputs.forEach((input) => {
            code += input.value;
        });

        const validation = validateOTP(code);
        if (!validation.valid) {
            showError('otp', validation.message);
            otpInputs.forEach((input) => input.classList.add('error'));
            return;
        }

        const root = document.querySelector('[wire\\:id]');
        const wireId = root?.getAttribute('wire:id');
        if (!wireId || !window.Livewire) {
            return;
        }

        const component = Livewire.find(wireId);
        await component.set('otpCode', code);
        await component.call('verifyAdminOtp');
    }, true);
}

function bindLoginLivewireHooks() {
    const restore = () => {
        requestAnimationFrame(() => {
            restoreLoginTab();
            bindOtpInputs();
            bindOtpFormSubmit();
            startTimer();
        });
    };

    Livewire.hook('message.processed', restore);
    Livewire.hook('morph.updated', restore);
}

window.switchTab = switchTab;
window.togglePassword = togglePassword;
window.backToLogin = backToLogin;
window.toggleDarkMode = toggleDarkMode;
