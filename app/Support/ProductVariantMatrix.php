<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class ProductVariantMatrix
{
    public static function for(Product $product): array
    {
        $product->loadMissing([
            'attributes' => fn ($q) => $q->wherePivot('is_variation', true)->orderByPivot('position'),
            'attributes.values' => fn ($q) => $q->where('is_active', true)->orderBy('position'),
            'variants' => fn ($q) => $q->where('is_active', true)->with('attributeValues'),
        ]);

        $attributes = $product->attributes->map(function ($attribute) use ($product) {
            return [
                'id' => (int) $attribute->id,
                'name' => $attribute->name,
                'values' => self::valuesForAttribute($product, (int) $attribute->id)
                    ->map(fn ($value) => [
                        'id' => (int) $value->id,
                        'label' => $value->value,
                    ])->values()->all(),
            ];
        })->values()->all();

        $variants = $product->variants->map(function (ProductVariant $variant) use ($product) {
            $mainImage = $product->images->first()
                ? ShopMedia::url($product->images->first()->image)
                : null;

            return [
                'id' => (int) $variant->id,
                'name' => $variant->name,
                'price' => (int) $variant->effective_price,
                'comparePrice' => ($variant->sale_price && $variant->sale_price < $variant->price)
                    ? (int) $variant->price
                    : null,
                'stock' => (int) $variant->stock,
                'image' => $variant->image
                    ? ShopMedia::url($variant->image)
                    : $mainImage,
                'valueIds' => $variant->attributeValues->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
            ];
        })->values()->all();

        $default = $product->variants->first();

        return [
            'attributes' => $attributes,
            'variants' => $variants,
            'defaultVariantId' => $default ? (int) $default->id : null,
            'defaultSelections' => $default
                ? $default->attributeValues->mapWithKeys(fn ($value) => [(int) $value->attribute_id => (int) $value->id])->all()
                : [],
        ];
    }

    protected static function valuesForAttribute(Product $product, int $attributeId): Collection
    {
        $usedValueIds = $product->variants
            ->flatMap(fn (ProductVariant $variant) => $variant->attributeValues)
            ->where('attribute_id', $attributeId)
            ->pluck('id')
            ->unique();

        $attribute = $product->attributes->firstWhere('id', $attributeId);

        return $attribute
            ? $attribute->values->whereIn('id', $usedValueIds)->values()
            : collect();
    }
}
