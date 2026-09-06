<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RepairProductImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_relinks_broken_rows_to_orphan_disk_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/orphan-a.webp', 'a');
        Storage::disk('public')->put('products/orphan-b.webp', 'b');

        $category = Category::query()->create([
            'name' => 'Test',
            'slug' => 'test',
            'is_active' => true,
            'position' => 0,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Test product',
            'slug' => 'test-product',
            'price' => 1000,
            'stock' => 1,
            'is_active' => true,
        ]);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'image' => 'products/missing-a.webp',
            'position' => 0,
        ]);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'image' => 'products/missing-b.webp',
            'position' => 1,
        ]);

        $this->artisan('shop:repair-product-images')->assertSuccessful();

        $this->assertTrue(Storage::disk('public')->exists('products/orphan-a.webp'));
        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'image' => 'products/orphan-a.webp',
        ]);
        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'image' => 'products/orphan-b.webp',
        ]);
    }
}
