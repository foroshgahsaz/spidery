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

    public function test_update_seo_persists_alt_and_title(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/seo.webp', 'binary');

        $registry = app(MediaRegistry::class);
        $registry->registerFromPath('public', 'avatars/seo.webp', 'seo.webp');
        $registry->updateSeo('public', 'avatars/seo.webp', 'Avatar alt', 'Avatar title');

        $media = MediaFile::query()->where('path', 'avatars/seo.webp')->first();

        $this->assertNotNull($media);
        $this->assertSame('Avatar alt', $media->alt_text);
        $this->assertSame('Avatar title', $media->title);
    }

    public function test_delete_path_removes_file_and_record(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/remove.webp', 'binary');

        $registry = app(MediaRegistry::class);
        $registry->registerFromPath('public', 'avatars/remove.webp', 'remove.webp');
        $registry->deletePath('avatars/remove.webp');

        $this->assertFalse(Storage::disk('public')->exists('avatars/remove.webp'));
        $this->assertDatabaseMissing('media_files', ['path' => 'avatars/remove.webp']);
    }

    public function test_delete_path_blocks_when_in_use(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('sliders/in-use.webp', 'binary');

        $registry = app(MediaRegistry::class);
        $registry->registerFromPath('public', 'sliders/in-use.webp', 'in-use.webp');

        HomeSlider::query()->create([
            'title' => 'Test',
            'image' => 'sliders/in-use.webp',
            'position' => 0,
            'is_active' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $registry->deletePath('sliders/in-use.webp');
    }
}
