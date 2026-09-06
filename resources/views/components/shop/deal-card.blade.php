@props(['product', 'section' => null])

@php
    use App\Support\ShopFormatter;
    $discount = ShopFormatter::discountPercent($product);
    $image = $section
        ? ShopFormatter::productImageForSection($product, $section)
        : ShopFormatter::productImage($product);
@endphp

<article class="deal-card">
    @if($product->is_featured)
        <span class="deal-card__original-badge">ویژه</span>
    @endif
    <a href="{{ route('products.show', $product) }}" class="deal-card__link">
        <div class="deal-card__media">
            <img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy" width="200" height="200">
        </div>
        <h4 class="deal-card__title">{{ $product->name }}</h4>
    </a>
    <div class="deal-card__pricing">
        @if($discount)
            <div class="deal-card__price-top">
                <span class="deal-card__discount">{{ $discount }}٪</span>
                <span class="deal-card__price-old">{{ number_format($product->price) }}</span>
            </div>
        @endif
        <p class="deal-card__price-sale">{{ number_format($product->effective_price) }} <span>تومان</span></p>
    </div>
    <form action="{{ route('cart.add') }}" method="POST">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="hidden" name="quantity" value="1">
        <button type="submit" class="deal-card__cart-btn" aria-label="افزودن به سبد">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                <path d="M3 6h18" />
            </svg>
            افزودن به سبد
        </button>
    </form>
</article>
