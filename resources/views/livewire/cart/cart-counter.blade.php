@if ($variant === 'bottom-nav')
    @php
        $isCart = request()->routeIs('cart', 'checkout');
    @endphp
    <a href="{{ route('cart') }}"
       class="mobile-bottom-nav__item {{ $isCart ? 'is-active' : '' }}"
       @if ($isCart) aria-current="page" @endif
       aria-label="سبد خرید">
        <span class="mobile-bottom-nav__icon mobile-bottom-nav__icon--cart" aria-hidden="true">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25h9.75m-9.75 0a2.25 2.25 0 0 0-2.25 2.25v.75a2.25 2.25 0 0 0 2.25 2.25h9.75a2.25 2.25 0 0 0 2.25-2.25v-.75a2.25 2.25 0 0 0-2.25-2.25m-9.75 0V9.75A2.25 2.25 0 0 1 9.75 7.5h4.5A2.25 2.25 0 0 1 16.5 9.75v4.5" />
            </svg>
            @if ($count > 0)
                <span class="mobile-bottom-nav__badge">{{ $count }}</span>
            @endif
        </span>
        <span class="mobile-bottom-nav__label">سبد خرید</span>
    </a>
@else
    <button type="button"
            data-open-cart
            class="header-icon-btn relative"
            aria-label="سبد خرید">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
            <path d="M3 6h18" />
            <path d="M16 10a4 4 0 0 1-8 0" />
        </svg>
        @if ($count > 0)
            <span class="cart-badge">{{ $count }}</span>
        @endif
    </button>
@endif
