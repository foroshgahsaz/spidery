<?php

namespace Tests\Unit;

use App\Services\Media\ImageOptimizer;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config([
            'image-optimizer.enabled' => true,
            'image-optimizer.driver' => 'gd',
        ]);
    }

    public function test_it_converts_large_jpeg_to_webp_and_resizes_for_products(): void
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('GD with WebP support is required.');
        }

        $sourcePath = $this->createTestJpeg(2000, 1500);
        $relativePath = 'products/test-upload.jpg';

        Storage::disk('public')->put($relativePath, file_get_contents($sourcePath));

        $optimizedPath = app(ImageOptimizer::class)->optimize('public', $relativePath, 'products');

        $this->assertSame('products/test-upload.webp', $optimizedPath);
        $this->assertTrue(Storage::disk('public')->exists($optimizedPath));
        $this->assertFalse(Storage::disk('public')->exists($relativePath));

        $dimensions = getimagesize(Storage::disk('public')->path($optimizedPath));

        $this->assertIsArray($dimensions);
        $this->assertSame('image/webp', $dimensions['mime']);
        $this->assertLessThanOrEqual(1600, $dimensions[0]);
        $this->assertLessThanOrEqual(1600, $dimensions[1]);
    }

    public function test_it_crops_avatars_to_square_webp(): void
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('GD with WebP support is required.');
        }

        $sourcePath = $this->createTestJpeg(1200, 800);
        $relativePath = 'avatars/user.jpg';

        Storage::disk('public')->put($relativePath, file_get_contents($sourcePath));

        $optimizedPath = app(ImageOptimizer::class)->optimize('public', $relativePath, 'avatars');

        $dimensions = getimagesize(Storage::disk('public')->path($optimizedPath));

        $this->assertSame('avatars/user.webp', $optimizedPath);
        $this->assertSame(400, $dimensions[0]);
        $this->assertSame(400, $dimensions[1]);
    }

    public function test_it_skips_svg_files(): void
    {
        $relativePath = 'brands/logo.svg';
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>';

        Storage::disk('public')->put($relativePath, $svg);

        $optimizedPath = app(ImageOptimizer::class)->optimize('public', $relativePath, 'brands');

        $this->assertSame($relativePath, $optimizedPath);
        $this->assertTrue(Storage::disk('public')->exists($relativePath));
    }

    public function test_it_writes_display_thumbnail_under_hidden_thumbs_directory(): void
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('GD with WebP support is required.');
        }

        $sourcePath = $this->createTestJpeg(800, 600);
        $relativePath = 'sliders/banner.jpg';
        Storage::disk('public')->put($relativePath, file_get_contents($sourcePath));

        $output = '.thumbs/hero/test-thumb.webp';
        $result = app(ImageOptimizer::class)->optimizeFromPreset('public', $relativePath, [
            'cover_width' => 200,
            'cover_height' => 80,
            'quality' => 80,
            'format' => 'webp',
        ], $output);

        $this->assertSame($output, $result);
        $this->assertTrue(Storage::disk('public')->exists($output));
        $this->assertTrue(is_file(Storage::disk('public')->path($output)));
    }

    public function test_it_returns_original_path_when_disabled(): void
    {
        config(['image-optimizer.enabled' => false]);

        $sourcePath = $this->createTestJpeg(800, 600);
        $relativePath = 'products/disabled.jpg';

        Storage::disk('public')->put($relativePath, file_get_contents($sourcePath));

        $optimizedPath = app(ImageOptimizer::class)->optimize('public', $relativePath, 'products');

        $this->assertSame($relativePath, $optimizedPath);
        $this->assertTrue(Storage::disk('public')->exists($relativePath));
    }

    protected function createTestJpeg(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 220, 40, 80);
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $color);

        $path = tempnam(sys_get_temp_dir(), 'img-opt-test-').'.jpg';
        imagejpeg($image, $path, 90);
        imagedestroy($image);

        return $path;
    }
}
