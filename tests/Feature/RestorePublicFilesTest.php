<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestorePublicFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_restores_missing_db_path_from_legacy_storage(): void
    {
        $publicRoot = storage_path('framework/testing/disks/restore-public');
        $legacyRoot = storage_path('app/public');

        @mkdir($publicRoot.'/products', 0777, true);
        @mkdir($legacyRoot.'/products', 0777, true);

        config(['filesystems.disks.public.root' => $publicRoot]);

        $legacyFile = $legacyRoot.'/products/legacy.webp';
        file_put_contents($legacyFile, 'image');

        $category = Category::query()->create([
            'name' => 'Cat',
            'slug' => 'cat',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Test',
            'slug' => 'test-product',
            'price' => 1000,
            'stock' => 1,
            'is_active' => true,
        ]);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'image' => 'products/legacy.webp',
            'position' => 0,
        ]);

        $this->artisan('shop:restore-public-files')
            ->assertSuccessful();

        $this->assertFileExists($publicRoot.'/products/legacy.webp');

        @unlink($legacyFile);
    }
}
