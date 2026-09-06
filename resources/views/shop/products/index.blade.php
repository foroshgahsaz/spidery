@extends('layouts.shop')

@section('title', ($pageTitle ?? 'لیست محصولات') . ' | ' . site_name())
@section('body_class', 'listing-page')

@section('content')
@php
    $formAction = match (true) {
        isset($category) => route('categories.show', $category),
        isset($brand) => route('brands.show', $brand),
        default => route('products.index'),
    };

    $hasActiveFilters = request()->hasAny(['search', 'category_id', 'brand_id', 'min_price', 'max_price'])
        || request('sort', 'created_at') !== 'created_at';
@endphp

<div class="max-w-site mx-auto px-4 md:px-6 py-6 md:py-10">
    <nav class="listing-breadcrumb mb-4" aria-label="مسیر">
        <a href="{{ route('home') }}">فروشگاه</a>
        <span>/</span>
        @if(isset($category))
            <span>{{ $category->name }}</span>
        @elseif(isset($brand))
            <span>{{ $brand->name }}</span>
        @elseif($searchQuery)
            <span>جستجو</span>
        @else
            <span>لیست کالاها</span>
        @endif
    </nav>

    @if($searchQuery)
        <div class="listing-search-hero mb-6">
            <div class="listing-search-hero__icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                </svg>
            </div>
            <div>
                <p class="listing-search-hero__label">نتایج جستجو برای</p>
                <h1 class="listing-search-hero__query">«{{ $searchQuery }}»</h1>
                <p class="listing-search-hero__count">{{ number_format($products->total()) }} محصول یافت شد</p>
            </div>
        </div>
    @else
        <div class="mb-6">
            <h1 class="section-title">{{ $pageTitle ?? 'لیست کالاها' }}</h1>
            @if(isset($category) && $category->description)
                <p class="text-sm text-gray-500 mt-2">{{ $category->description }}</p>
            @endif
            @if(isset($brand) && $brand->description)
                <p class="text-sm text-gray-500 mt-2">{{ $brand->description }}</p>
            @endif
        </div>
    @endif

    <div class="listing-layout">
        @include('shop.partials.product-filters')

        <div class="listing-layout__content">
            <div class="listing-toolbar">
                <div class="listing-toolbar__start">
                    <button type="button" class="listing-filter-toggle lg:hidden" data-open-filters>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M4 6h16M6 12h12M8 18h8"/>
                        </svg>
                        فیلترها
                        @if($hasActiveFilters)
                            <span class="listing-filter-toggle__badge"></span>
                        @endif
                    </button>
                    @unless($searchQuery)
                        <p class="listing-toolbar__count hidden sm:block">{{ number_format($products->total()) }} محصول</p>
                    @endunless
                </div>

                <form action="{{ $formAction }}" method="GET" class="listing-sort-form">
                    @foreach(request()->except(['sort', 'direction', 'page']) as $key => $value)
                        @if(is_string($value) && $value !== '')
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label class="sr-only" for="listingSort">مرتب‌سازی</label>
                    <select id="listingSort" name="sort" class="listing-sort-select" onchange="this.form.querySelector('#listingDirection').value = this.value === 'price' ? 'asc' : 'desc'; this.form.submit();">
                        <option value="created_at" @selected(request('sort', 'created_at') === 'created_at')>جدیدترین</option>
                        <option value="price" @selected(request('sort') === 'price')>ارزان‌ترین</option>
                        <option value="views" @selected(request('sort') === 'views')>پربازدید</option>
                        <option value="name" @selected(request('sort') === 'name')>نام</option>
                    </select>
                    <input type="hidden" name="direction" id="listingDirection" value="{{ request('sort') === 'price' ? request('direction', 'asc') : request('direction', 'desc') }}">
                </form>
            </div>

            @if($hasActiveFilters)
                <div class="listing-active-filters">
                    @if($searchQuery)
                        <a href="{{ route('products.index', request()->except(['search', 'page'])) }}" class="listing-active-filters__chip">
                            جستجو: {{ $searchQuery }} ×
                        </a>
                    @endif
                    @if(request('category_id') && !isset($category))
                        @php $activeCat = $categories->firstWhere('id', (int) request('category_id')); @endphp
                        @if($activeCat)
                            <a href="{{ route('products.index', request()->except(['category_id', 'page'])) }}" class="listing-active-filters__chip">
                                {{ $activeCat->name }} ×
                            </a>
                        @endif
                    @endif
                    @if(request('brand_id') && !isset($brand))
                        @php $activeBrand = $brands->firstWhere('id', (int) request('brand_id')); @endphp
                        @if($activeBrand)
                            <a href="{{ route('products.index', request()->except(['brand_id', 'page'])) }}" class="listing-active-filters__chip">
                                {{ $activeBrand->name }} ×
                            </a>
                        @endif
                    @endif
                    @if(request('min_price') || request('max_price'))
                        <a href="{{ $formAction }}?{{ http_build_query(request()->except(['min_price', 'max_price', 'page'])) }}" class="listing-active-filters__chip">
                            قیمت: {{ number_format((int) request('min_price', 0)) }} – {{ number_format((int) request('max_price', $priceBounds['max'])) }} ×
                        </a>
                    @endif
                </div>
            @endif

            @if($products->isEmpty())
                <div class="listing-empty">
                    <div class="listing-empty__icon">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                        </svg>
                    </div>
                    @if($searchQuery)
                        <h2 class="listing-empty__title">نتیجه‌ای برای «{{ $searchQuery }}» پیدا نشد</h2>
                        <p class="listing-empty__text">عبارت دیگری امتحان کنید یا فیلترها را تغییر دهید.</p>
                    @else
                        <h2 class="listing-empty__title">محصولی با این فیلترها یافت نشد</h2>
                        <p class="listing-empty__text">فیلترها را تغییر دهید یا همه محصولات را ببینید.</p>
                    @endif
                    <div class="listing-empty__suggestions">
                        @foreach($categories->take(4) as $cat)
                            <a href="{{ route('categories.show', $cat) }}" class="listing-empty__suggestion">{{ $cat->name }}</a>
                        @endforeach
                    </div>
                    <a href="{{ route('products.index') }}" class="listing-empty__cta">مشاهده همه محصولات</a>
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-5">
                    @foreach($products as $product)
                        <x-shop.product-card :product="$product" variant="clothing" />
                    @endforeach
                </div>
                <div class="mt-8">{{ $products->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
