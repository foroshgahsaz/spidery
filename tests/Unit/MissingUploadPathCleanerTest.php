<?php

namespace Tests\Unit;

use App\Filament\Support\MissingUploadPathCleaner;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MissingUploadPathCleanerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_clears_missing_image_paths_before_fill(): void
    {
        Storage::fake('public');

        $category = Category::query()->create([
            'name' => 'لپ‌تاپ',
            'slug' => 'laptop',
            'image' => 'categories/missing.webp',
            'is_active' => true,
            'position' => 0,
        ]);

        $data = MissingUploadPathCleaner::clearFromFormData($category->attributesToArray(), $category);

        $this->assertNull($data['image']);
    }

    public function test_it_keeps_existing_image_paths_before_fill(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('categories/existing.webp', 'image');

        $category = Category::query()->create([
            'name' => 'لپ‌تاپ',
            'slug' => 'laptop',
            'image' => 'categories/existing.webp',
            'is_active' => true,
            'position' => 0,
        ]);

        $data = MissingUploadPathCleaner::clearFromFormData($category->attributesToArray(), $category);

        $this->assertSame('categories/existing.webp', $data['image']);
    }
}
