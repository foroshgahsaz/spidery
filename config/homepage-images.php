<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Homepage display image presets
  |--------------------------------------------------------------------------
  |
  | These control thumbnail/display sizes per homepage section (not upload).
  | Admin can override via settings group "homepage_images".
  |
  */

  'sections' => [

    'hero' => [
      'label' => 'اسلایدر صفحه اصلی',
      'mode' => 'cover',
      'width' => 1920,
      'height' => 380,
      'quality' => 85,
      'format' => 'webp',
      'enabled' => true,
    ],

    'categories' => [
      'label' => 'دسته‌بندی‌های منتخب',
      'mode' => 'contain',
      'width' => 112,
      'height' => 112,
      'quality' => 85,
      'format' => 'webp',
      'enabled' => true,
    ],

    'deals' => [
      'label' => 'پیشنهاد شگفت‌انگیز',
      'mode' => 'contain',
      'width' => 400,
      'height' => 400,
      'quality' => 85,
      'format' => 'webp',
      'enabled' => true,
    ],

    'new_products' => [
      'label' => 'جدیدترین محصولات',
      'mode' => 'cover',
      'width' => 400,
      'height' => 500,
      'quality' => 85,
      'format' => 'webp',
      'enabled' => true,
    ],

    'best_sellers' => [
      'label' => 'پرفروش‌ترین‌ها',
      'mode' => 'contain',
      'width' => 264,
      'height' => 264,
      'quality' => 85,
      'format' => 'webp',
      'enabled' => true,
    ],

    'blog' => [
      'label' => 'مجله چاپینو',
      'mode' => 'cover',
      'width' => 400,
      'height' => 160,
      'quality' => 85,
      'format' => 'webp',
      'enabled' => true,
    ],

  ],

];
