<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteSettings = app(\App\Services\Settings\SettingsService::class)->site();
        $needsSwiper = request()->routeIs('home', 'products.show');
        $needsListingAssets = request()->routeIs('products.*', 'categories.*', 'brands.*');
    @endphp
    <title>@yield('title', site_name())</title>
    @hasSection('meta')
        @yield('meta')
    @else
        <x-seo-meta :seo="\App\Support\SeoPresenter::for(null, [
            'title' => site_name(),
            'description' => $siteSettings['description'] ?? 'فروشگاه آنلاین',
        ])" />
    @endif
    @stack('meta')
    @if (! empty($siteSettings['favicon']))
        <link rel="icon" href="{{ \App\Support\ShopMedia::url($siteSettings['favicon']) }}">
    @endif
    <link rel="preload" href="{{ asset('fonts/yekan/fonts.css') }}" as="style">
    <link rel="stylesheet" href="{{ asset('fonts/yekan/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('shop/css/tailwind.css') }}">
    @if ($needsSwiper)
        <link rel="stylesheet" href="{{ asset('shop/vendor/swiper/swiper-bundle.min.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('shop/css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('shop/css/rich-content.css') }}">
    @if ($needsListingAssets)
        <link rel="stylesheet" href="{{ asset('shop/css/listing.css') }}">
    @endif
    @auth
        @if(auth()->user()->isAdmin())
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
        @endif
    @endauth
    <style>[x-cloak]{display:none!important}</style>
    @stack('styles')
    @livewireStyles
</head>
<body class="bg-white min-h-screen font-yekan text-gray-800 has-mobile-bottom-nav @if(auth()->check() && auth()->user()->isAdmin()) has-admin-bar @endif @yield('body_class')">

    @if(auth()->check() && auth()->user()->isAdmin())
        @include('shop.partials.admin-bar')
    @endif

    @include('shop.partials.cart-sidebar-shell')

    <div id="shopAppShell" class="shop-app-shell">

    @include('shop.partials.header')

    <main>
        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endif
    </main>

    @include('shop.partials.footer')
    </div>

    @include('shop.partials.mobile-bottom-nav')

    @include('shop.partials.overlays')
    @include('shop.partials.mobile-menu')

    @guest
        @include('shop.partials.login-modal-shell')
    @endguest

    @if ($needsSwiper)
        <script src="{{ asset('shop/vendor/swiper/swiper-bundle.min.js') }}" defer></script>
    @endif
    <script src="{{ asset('shop/js/main.js') }}" defer></script>
    @if ($needsListingAssets)
        <script src="{{ asset('shop/js/listing-filters.js') }}" defer></script>
    @endif
    @stack('scripts')
    @include('partials.livewire-scripts')
</body>
</html>
