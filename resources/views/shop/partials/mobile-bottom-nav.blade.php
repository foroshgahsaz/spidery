@php
    $isHome = request()->routeIs('home');
    $isCart = request()->routeIs('cart', 'checkout');
    $isAccount = request()->routeIs('account.*', 'login', 'register');
@endphp

<nav class="mobile-bottom-nav md:hidden" aria-label="منوی پایین موبایل">
    <div class="mobile-bottom-nav__inner">
        <a href="{{ route('home') }}"
           class="mobile-bottom-nav__item {{ $isHome ? 'is-active' : '' }}"
           @if ($isHome) aria-current="page" @endif>
            <span class="mobile-bottom-nav__icon" aria-hidden="true">
                @if ($isHome)
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8h5z" />
                    </svg>
                @else
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9.75 12 3l9 6.75V19.5a1.5 1.5 0 0 1-1.5 1.5H15v-6.75h-4.5V21H4.5A1.5 1.5 0 0 1 3 19.5V9.75Z" />
                    </svg>
                @endif
            </span>
            <span class="mobile-bottom-nav__label">خانه</span>
        </a>

        <button type="button"
                class="mobile-bottom-nav__item"
                onclick="toggleSearchModal(true)"
                aria-label="جستجو">
            <span class="mobile-bottom-nav__icon" aria-hidden="true">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="7" />
                    <path stroke-linecap="round" d="m20 20-3.5-3.5" />
                </svg>
            </span>
            <span class="mobile-bottom-nav__label">جستجو</span>
        </button>

        @livewire('cart.cart-counter', ['variant' => 'bottom-nav'], key('cart-bottom-nav'))

        @auth
            <a href="{{ route('account.dashboard') }}"
               class="mobile-bottom-nav__item {{ $isAccount ? 'is-active' : '' }}"
               @if ($isAccount) aria-current="page" @endif>
                <span class="mobile-bottom-nav__icon" aria-hidden="true">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                </span>
                <span class="mobile-bottom-nav__label">حساب کاربری</span>
            </a>
        @else
            <a href="{{ route('login') }}"
               class="mobile-bottom-nav__item {{ $isAccount ? 'is-active' : '' }}"
               @if ($isAccount) aria-current="page" @endif>
                <span class="mobile-bottom-nav__icon" aria-hidden="true">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                </span>
                <span class="mobile-bottom-nav__label">حساب کاربری</span>
            </a>
        @endauth
    </div>
</nav>
