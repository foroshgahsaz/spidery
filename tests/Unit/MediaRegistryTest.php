<?php

namespace Tests\Unit;

use App\Models\HomeSlider;
use App\Models\MediaFile;
use App\Services\Media\MediaRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_file_and_syncs_model_usage_on_save(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('sliders/test.webp', 'binary');

        app(MediaRegistry::class)->registerFromPath('public', 'sliders/test.webp', 'slide.webp');

        $slider = HomeSlider::query()->create([
            'title' => 'Test',
            'image' => 'sliders/test.webp',
            'position' => 0,
            'is_active' => true,
        ]);

        $media = MediaFile::query()->where('path', 'sliders/test.webp')->first();

        $this->assertNotNull($media);
        $this->assertDatabaseHas('media_usages', [
            'media_file_id' => $media->id,
            'usable_type' => HomeSlider::class,
            'usable_id' => $slider->id,
            'field' => 'image',
        ]);
    }

    public function test_detach_removes_usages_but_keeps_media_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('sliders/x.webp', 'binary');

        app(MediaRegistry::class)->registerFromPath('public', 'sliders/x.webp');

        $slider = HomeSlider::query()->create([
            'title' => 'Test',
            'image' => 'sliders/x.webp',
            'position' => 0,
            'is_active' => true,
        ]);

        $mediaId = MediaFile::query()->where('path', 'sliders/x.webp')->value('id');

        app(MediaRegistry::class)->detachModel($slider);

        $this->assertDatabaseMissing('media_usages', [
            'usable_type' => HomeSlider::class,
            'usable_id' => $slider->id,
        ]);

        $this->assertDatabaseHas('media_files', ['id' => $mediaId]);
    }
}
