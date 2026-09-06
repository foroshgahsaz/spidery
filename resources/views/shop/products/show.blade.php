@extends('layouts.shop')

@php
    use App\Support\ShopFormatter;
    $discount = ShopFormatter::discountPercent($product);
    $images = $product->images;
    $mainImage = ShopFormatter::productImage($product);
    $reviewCount = (int) ($product->approved_reviews_count ?? 0);
    $avgRating = round((float) ($product->approved_reviews_avg ?? 0), 1);
    $questionCount = (int) ($product->approved_questions_count ?? 0);
@endphp

@section('title', ($product->meta_title ?: $product->name) . ' | ' . site_name())
@section('body_class', 'product-page')

@section('meta')
    <x-seo-meta :seo="\App\Support\SeoPresenter::for($product, ['type' => 'product'])" />
    @php
        $seoImage = \App\Support\SeoPresenter::for($product)->image();
        $effectivePrice = $product->effective_price ?? $product->price;
    @endphp
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'description' => strip_tags($product->short_description ?? $product->description ?? ''),
        'sku' => $product->sku,
        'image' => $seoImage,
        'offers' => [
            '@type' => 'Offer',
            'priceCurrency' => 'IRR',
            'price' => $effectivePrice * 10,
            'availability' => $product->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => route('products.show', $product),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('shop/css/product.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('shop/js/product.js') }}" defer></script>
@endpush

@section('content')
<main class="max-w-site mx-auto px-4 md:px-6 py-4 md:py-8">

    <nav class="product-breadcrumb mb-4 md:mb-6" aria-label="مسیر">
        <a href="{{ route('home') }}">فروشگاه اینترنتی چاپینو</a>
        <span class="mx-1.5">/</span>
        @if($product->category)
            <a href="{{ route('categories.show', $product->category) }}">{{ $product->category->name }}</a>
            <span class="mx-1.5">/</span>
        @endif
        <span class="text-gray-800">{{ $product->name }}</span>
    </nav>

    <section class="product-top-grid gap-6 lg:gap-8 mb-4 lg:mb-12">
        @if($product->variants->isNotEmpty())
            <div class="product-variant-overlay" id="productVariantOverlay" aria-hidden="true">
                <div class="product-variant-overlay__panel">
                    <span class="product-variant-overlay__spinner" aria-hidden="true"></span>
                    <p class="product-variant-overlay__text">در حال بروزرسانی محصول...</p>
                </div>
            </div>
        @endif
        <div class="product-top-grid__gallery">
            <div class="product-gallery">
                <div class="product-gallery__layout">
                    <div class="product-gallery__main">
                        <div class="product-zoom">
                            <div class="product-zoom__stage" data-open-lightbox role="button" tabindex="0" aria-label="بزرگنمایی و مشاهده گالری">
                                <img id="productMainImage" src="{{ $mainImage }}" data-zoom="{{ $mainImage }}" data-initial-src="{{ $mainImage }}" alt="{{ $product->name }}">
                                <div class="product-zoom__lens"></div>
                            </div>
                            <div class="product-zoom__result" aria-hidden="true"></div>
                        </div>
                    </div>
                    @if($images->isNotEmpty())
                        <div class="swiper productGalleryThumbs product-gallery-thumbs">
                            <div class="swiper-wrapper">
                                @foreach($images as $index => $image)
                                    @php $url = ShopFormatter::storageImageUrl($image->image); @endphp
                                    <div class="swiper-slide @if($index === 0) swiper-slide-thumb-active @endif" data-gallery-index="{{ $index }}">
                                        <img src="{{ $url }}" data-zoom="{{ $url }}" alt="نمای {{ $index + 1 }}" loading="lazy">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="product-top-grid__info">
            <div class="product-info">
                <h1 class="product-info__title">{{ $product->name }}</h1>
                @if($product->short_description)
                    <p class="product-info__subtitle">{{ $product->short_description }}</p>
                @endif
                @if($product->category)
                    <a href="{{ route('categories.show', $product->category) }}" class="product-info__category">{{ $product->category->name }}</a>
                @endif
                @if($product->brand)
                    <a href="{{ route('brands.show', $product->brand) }}" class="product-info__category ms-2">{{ $product->brand->name }}</a>
                @endif
                <hr class="product-info__divider">

                <div class="product-info__meta">
                    <div class="product-info__meta-rating">
                        @if($reviewCount)
                            <span class="product-info__rating-badge">★ {{ number_format($avgRating, 1) }}</span>
                            <a href="#product-details" class="product-info__reviews" data-open-product-tab="reviews">{{ $reviewCount }} دیدگاه</a>
                        @endif
                        <span class="hidden lg:inline text-gray-500">{{ number_format($product->views) }} بازدید</span>
                    </div>
                    @if($product->sku)
                        <span class="product-info__sku">شناسه: {{ $product->sku }}</span>
                    @endif
                </div>

                @if($product->variants->isNotEmpty())
                    @livewire('product.variant-selector', ['product' => $product], key('variant-selector-'.$product->id))
                @endif

                <div class="lg:hidden mt-4">
                    <div class="product-buy-box p-4">
                        <div class="product-buy-box__seller">
                            <div><span class="text-gray-500">فروشنده:</span> <strong class="text-gray-800 mr-1">چاپینو</strong></div>
                            <span class="text-brand-green text-[11px] font-bold bg-emerald-50 px-2 py-1 rounded-lg">عملکرد عالی</span>
                        </div>
                        @livewire('product.add-to-cart', ['product' => $product], key('product-add-mobile-'.$product->id))
                <div class="mt-3">
                    @livewire('product.toggle-wishlist', ['product' => $product], key('wishlist-mobile-'.$product->id))
                </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="product-top-grid__buy hidden lg:block">
            <div class="product-buy-box p-4 md:p-5">
                <div class="product-buy-box__seller">
                    <div><span class="text-gray-500">فروشنده:</span> <strong class="text-gray-800 mr-1">چاپینو</strong></div>
                    <span class="text-brand-green text-[11px] font-bold bg-emerald-50 px-2 py-1 rounded-lg">عملکرد عالی</span>
                </div>
                @livewire('product.add-to-cart', ['product' => $product], key('product-add-'.$product->id))
                <div class="mt-3">
                    @livewire('product.toggle-wishlist', ['product' => $product], key('wishlist-'.$product->id))
                </div>
            </div>
        </div>
    </section>

    <section class="product-trust-row mb-8 md:mb-12">
        <div class="product-trust-item"><span>ارسال سریع</span></div>
        <div class="product-trust-item"><span>۷ روز بازگشت</span></div>
        <div class="product-trust-item"><span>ضمانت اصالت</span></div>
    </section>

    @include('shop.partials.product-tabs', [
        'product' => $product,
        'reviewCount' => $reviewCount,
        'questionCount' => $questionCount,
    ])

    @if($relatedProducts->isNotEmpty())
        <section class="mb-10 md:mb-14 related-products-slider">
            <div class="flex items-center justify-between mb-4">
                <h2 class="section-title">محصولات مشابه</h2>
            </div>
            <div class="swiper relatedProductsSwiper">
                <div class="swiper-wrapper">
                    @foreach($relatedProducts as $related)
                        <div class="swiper-slide">
                            <x-shop.product-card :product="$related" variant="scroll" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('shop.partials.product-lightbox', [
        'product' => $product,
        'images' => $images,
        'mainImage' => $mainImage,
    ])
</main>
@endsection
