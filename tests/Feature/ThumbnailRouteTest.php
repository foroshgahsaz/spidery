<?php

namespace Tests\Feature;

use App\Services\Media\DisplayImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThumbnailRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_thumbnail_route_serves_cached_image(): void
    {
        Storage::fake('public');

        $sourcePath = 'products/test.jpg';
        Storage::disk('public')->put($sourcePath, $this->minimalJpeg());

        $url = app(DisplayImageService::class)->url('deals', $sourcePath);

        $this->assertNotNull($url);
        $this->assertStringContainsString('/thumb/deals/', $url);

        $response = $this->get(parse_url($url, PHP_URL_PATH));

        $response->assertOk();
    }

    public function test_thumbnail_route_returns_404_for_unknown_section(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/test.jpg', $this->minimalJpeg());

        $this->get('/thumb/unknown/products/test.jpg')->assertNotFound();
    }

    protected function minimalJpeg(): string
    {
        return base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGfAP/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAQUCf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8Bf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8Bf//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEABj8Cf//Z');
    }
}
