@props(['product', 'variant' => 'clothing', 'section' => null])

@php
    use App\Support\ShopFormatter;
    $discount = ShopFormatter::discountPercent($product);
    $image = $section
        ? ShopFormatter::productImageForSection($product, $section)
        : ShopFormatter::productImage($product);
@endphp

<a href="{{ route('products.show', $product) }}" class="product-card product-card-link {{ $variant === 'clothing' ? 'clothing-card' : 'product-scroll-card' }} h-full w-full">
    <div class="product-card__media @if($variant !== 'clothing') p-4 @endif">
        <img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy" @if($variant !== 'clothing') class="h-28 object-contain mx-auto" @endif>
    </div>
    @if($variant === 'clothing')
        <div class="clothing-card__body">
            <div class="product-card__brand">
                <span class="product-card__brand-icon" aria-hidden="true"></span>
                <span>{{ $product->category?->name ?? 'چاپینو' }}</span>
            </div>
            <h4 class="clothing-card__title">{{ $product->name }}</h4>
            <div class="product-card__price-row">
                @if($discount)<span class="product-card__discount">{{ $discount }}٪</span>@endif
                <div class="product-card__price-group">
                    <span class="product-card__price-sale">{{ number_format($product->effective_price) }} <span>تومان</span></span>
                    @if($discount)
                        <span class="product-card__price-old">{{ number_format($product->price) }} <span>تومان</span></span>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="p-3 flex flex-col gap-2 flex-1">
            <h4 class="text-xs font-bold line-clamp-2 leading-6">{{ $product->name }}</h4>
            <div class="flex items-center justify-end mt-auto">
                <div class="product-card__price">
                    @if($discount)
                        <p class="product-card__price-old">{{ number_format($product->price) }} <span>تومان</span></p>
                    @endif
                    <p class="product-card__price-sale">{{ number_format($product->effective_price) }} <span>تومان</span></p>
                </div>
            </div>
        </div>
    @endif
</a>
