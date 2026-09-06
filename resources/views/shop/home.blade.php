@extends('layouts.shop')

@section('title', config('app.name') . ' | فروشگاه آنلاین')

@section('content')
    @if(session('success'))
        <div class="max-w-site mx-auto px-4 pt-3">
            <div class="bg-emerald-50 text-emerald-700 text-sm px-4 py-2 rounded-xl">{{ session('success') }}</div>
        </div>
    @endif

    {{-- HERO --}}
    <section class="main-slider-outer mt-2 md:mt-4">
        <div class="swiper mainSwiper relative" aria-label="اسلایدر بنر">
            <div class="swiper-wrapper">
                @forelse($sliders as $i => $slider)
                    <div class="swiper-slide">
                        @php
                            $url = str_starts_with($slider->image, 'http')
                                ? $slider->image
                                : (str_starts_with($slider->image, 'shop/')
                                    ? asset($slider->image)
                                    : \App\Support\ShopMedia::url($slider->image));
                        @endphp
                        @if($slider->link)
                            <a href="{{ $slider->link }}" class="hero-slide__link">
                                <img src="{{ $url }}" alt="{{ $slider->title ?? 'بنر' }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" draggable="false">
                            </a>
                        @else
                            <div class="hero-slide__link">
                                <img src="{{ $url }}" alt="{{ $slider->title ?? 'بنر' }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" draggable="false">
                            </div>
                        @endif
                    </div>
                @empty
                    @foreach(['slide-ai.svg', 'slide-discount.svg', 'slide-python.svg'] as $i => $hero)
                        <div class="swiper-slide">
                            <a href="{{ route('products.index') }}" class="hero-slide__link">
                                <img src="{{ asset('shop/images/hero/'.$hero) }}" alt="بنر" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" draggable="false">
                            </a>
                        </div>
                    @endforeach
                @endforelse
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </section>

    {{-- CATEGORIES --}}
    @if($categories->isNotEmpty())
        <section class="max-w-site mx-auto px-4 featured-categories-section">
            <div class="flex items-center justify-between section-header">
                <h2 class="section-title">دسته‌بندی‌های منتخب</h2>
                <a href="{{ route('products.index') }}" class="section-nav-link">مشاهده همه</a>
            </div>
            <div class="swiper categorySwiper">
                <div class="swiper-wrapper">
                    @foreach($categories as $category)
                        <div class="swiper-slide">
                            <a href="{{ route('categories.show', $category) }}" class="category-card">
                                <img src="{{ \App\Support\ShopFormatter::categoryImage($category->image) }}" alt="{{ $category->name }}" loading="lazy">
                                <span>{{ $category->name }}</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- DISCOUNTED / FEATURED DEALS --}}
    @if($discounted->isNotEmpty())
        <section class="featured-deals-section">
            <div class="featured-deals-section__container">
                <div class="featured-deals-box">
                    <div class="featured-deals-box__pattern" aria-hidden="true"></div>
                    <div class="featured-deals-slider product-slider-overlay">
                        <div class="featured-deals-slider__nav">
                            <button type="button" data-swiper-prev class="featured-deals-nav-btn" aria-label="قبلی">‹</button>
                            <button type="button" data-swiper-next class="featured-deals-nav-btn" aria-label="بعدی">›</button>
                        </div>
                        <div class="swiper featuredDealsSwiper">
                            <div class="swiper-wrapper">
                                <div class="swiper-slide swiper-slide--promo h-auto">
                                    <div class="featured-deals-promo">
                                        <p class="featured-deals-promo__heading">پیشنهاد<br>شگفت‌انگیز</p>
                                    </div>
                                </div>
                                @foreach($discounted as $product)
                                    <div class="swiper-slide swiper-slide--product h-auto">
                                        <x-shop.deal-card :product="$product" />
                                    </div>
                                @endforeach
                                <div class="swiper-slide swiper-slide--view-all h-auto">
                                    <a href="{{ route('products.index') }}" class="deal-view-all-card">
                                        <span class="deal-view-all-card__text">مشاهده همه</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- NEW PRODUCTS --}}
    @if($new->isNotEmpty())
        <section class="other-products-section">
            <div class="max-w-site mx-auto px-4">
                <div class="flex items-center justify-between section-header">
                    <h2 class="section-title">جدیدترین محصولات</h2>
                    <a href="{{ route('products.index') }}" class="section-nav-link">مشاهده همه</a>
                </div>
            </div>
            <div class="other-products-slider product-slider-overlay relative">
                <div class="swiper productSwiper">
                    <div class="swiper-wrapper">
                        @foreach($new as $product)
                            <div class="swiper-slide h-auto">
                                <x-shop.product-card :product="$product" variant="clothing" />
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- BEST SELLERS --}}
    @if($best_sellers->isNotEmpty())
        <section class="max-w-site mx-auto px-4 programming-section">
            <div class="flex items-center justify-between section-header">
                <h2 class="section-title">پرفروش‌ترین‌ها</h2>
            </div>
            <div class="product-slider-overlay relative">
                <div class="swiper programmingSwiper">
                    <div class="swiper-wrapper">
                        @foreach($best_sellers as $product)
                            <div class="swiper-slide h-auto">
                                <x-shop.product-card :product="$product" variant="scroll" />
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- BLOG --}}
    @if($posts->isNotEmpty())
        <section class="articles-section relative">
            <div id="articles-content" class="max-w-site mx-auto px-4 articles-section__inner">
                <div class="flex items-center justify-between section-header">
                    <h2 class="section-title">مجله چاپینو</h2>
                    <a href="{{ route('blog.index') }}" class="section-nav-link">مشاهده همه</a>
                </div>
                <div class="swiper blogSwiper mb-5">
                    <div class="swiper-wrapper">
                        @foreach($posts as $post)
                            <div class="swiper-slide h-auto">
                                <article class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100">
                                    <a href="{{ route('blog.show', $post) }}">
                                        <div class="relative">
                                            <img src="{{ $post->image ? \App\Support\ShopMedia::url($post->image) : asset('shop/images/blog/article-1.svg') }}" alt="{{ $post->title }}" class="w-full h-40 object-cover" loading="lazy">
                                        </div>
                                    </a>
                                    <div class="p-4">
                                        <a href="{{ route('blog.show', $post) }}">
                                            <h3 class="text-sm font-bold leading-7 mb-3 hover:text-brand-gold">{{ $post->title }}</h3>
                                        </a>
                                        <p class="text-[11px] text-gray-400">{{ optional($post->published_at)->format('Y/m/d') }}</p>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection
