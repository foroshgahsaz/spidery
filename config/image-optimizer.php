<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Optimizer
    |--------------------------------------------------------------------------
    |
    | Filament uploads are optimized after they are stored on the public disk.
    | If optimization fails for any reason, the original file is kept so uploads
    | never break because of this layer.
    |
    */

    'enabled' => env('IMAGE_OPTIMIZER_ENABLED', true),

    'driver' => env('IMAGE_OPTIMIZER_DRIVER', 'gd'),

    'presets' => [

        'product' => [
            'max_width' => 1600,
            'max_height' => 1600,
            'quality' => 85,
            'format' => 'webp',
        ],

        'logo' => [
            'max_width' => 512,
            'max_height' => 512,
            'quality' => 90,
            'format' => 'webp',
        ],

        'slider' => [
            'max_width' => 1920,
            'max_height' => 1080,
            'quality' => 85,
            'format' => 'webp',
        ],

        'category' => [
            'max_width' => 1200,
            'max_height' => 1200,
            'quality' => 85,
            'format' => 'webp',
        ],

        'post' => [
            'max_width' => 1600,
            'max_height' => 1600,
            'quality' => 85,
            'format' => 'webp',
        ],

        'seo' => [
            'max_width' => 1200,
            'max_height' => 630,
            'quality' => 85,
            'format' => 'webp',
        ],

        'avatar' => [
            'cover_width' => 400,
            'cover_height' => 400,
            'quality' => 85,
            'format' => 'webp',
        ],

        'settings' => [
            'max_width' => 1024,
            'max_height' => 1024,
            'quality' => 90,
            'format' => 'webp',
        ],

        'default' => [
            'max_width' => 1600,
            'max_height' => 1600,
            'quality' => 85,
            'format' => 'webp',
        ],

    ],

    'directory_presets' => [
        'products' => 'product',
        'products/variants' => 'product',
        'brands' => 'logo',
        'sliders' => 'slider',
        'categories' => 'category',
        'posts' => 'post',
        'seo' => 'seo',
        'avatars' => 'avatar',
        'settings' => 'settings',
    ],

];
