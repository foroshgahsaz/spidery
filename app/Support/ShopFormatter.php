<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShopFormatter
{
    public static function money(int $amount): string
    {
        return number_format($amount).' تومان';
    }

    public static function discountPercent(Product $product): ?int
    {
        if (! $product->hasDiscount() || $product->price <= 0) {
            return null;
        }

        return (int) round((($product->price - $product->sale_price) / $product->price) * 100);
    }

    public static function productImage(?Product $product, string $fallback = 'shop/images/products/clothing.svg'): string
    {
        if (! $product) {
            return asset($fallback);
        }

        $image = $product->relationLoaded('images')
            ? ($product->images->firstWhere('is_primary', true) ?? $product->images->first())
            : null;

        if (! $image && $product->images()->exists()) {
            $image = $product->images()->where('is_primary', true)->first()
                ?? $product->images()->orderBy('position')->first();
        }

        if ($image?->image) {
            return self::storageImageUrl($image->image, $fallback);
        }

        return asset($fallback);
    }

    public static function storageImageUrl(?string $path, string $fallback = 'shop/images/products/clothing.svg'): string
    {
        return self::optionalStorageImageUrl($path) ?? asset($fallback);
    }

    public static function optionalStorageImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        $relative = ltrim($path, '/');
        $disk = Storage::disk('public');

        if (! $disk->exists($relative)) {
            return null;
        }

        try {
            if ($disk->size($relative) < 512) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        return ShopMedia::url($relative);
    }

    public static function categoryImage(?string $path): string
    {
        if (! $path) {
            return asset('shop/images/categories/code.svg');
        }

        return ShopMedia::url($path) ?? asset('shop/images/categories/code.svg');
    }
}
