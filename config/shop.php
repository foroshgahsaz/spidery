<?php

return [

    'cache' => [
        'categories_ttl' => env('SHOP_CACHE_CATEGORIES_TTL', 3600),
        'products_ttl' => env('SHOP_CACHE_PRODUCTS_TTL', 900),
        'home_ttl' => env('SHOP_CACHE_HOME_TTL', 600),
        'shipping_ttl' => env('SHOP_CACHE_SHIPPING_TTL', 3600),
    ],

    'otp' => [
        'length' => 6,
        'expires_minutes' => 5,
        'throttle_seconds' => 120,
    ],

    'cart' => [
        'guest_prefix' => 'cart:guest:',
        'guest_ttl' => 60 * 24 * 7,
    ],

    'checkout' => [
        'unpaid_ttl_minutes' => (int) env('SHOP_UNPAID_TTL_MINUTES', 60),
    ],

];
