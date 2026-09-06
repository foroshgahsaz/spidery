<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeSlider;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;

return [

    'folders' => [
        'sliders' => 'اسلایدر',
        'brands' => 'برند',
        'categories' => 'دسته‌بندی',
        'posts' => 'مقاله',
        'products' => 'محصول',
        'products/variants' => 'تنوع محصول',
        'seo' => 'سئو',
        'avatars' => 'آواتار',
        'settings' => 'تنظیمات',
        'pages' => 'صفحه',
        'uploads' => 'سایر',
    ],

    'models' => [
        HomeSlider::class => ['image'],
        Brand::class => ['logo', 'og_image'],
        Category::class => ['image', 'og_image'],
        Post::class => ['image', 'og_image'],
        Page::class => ['og_image'],
        Product::class => ['og_image'],
        ProductImage::class => ['image'],
        ProductVariant::class => ['image'],
        User::class => ['avatar'],
    ],

];
