<?php

namespace Tests\Unit;

use App\Filament\Forms\Components\MediaPicker;
use App\Filament\Support\ShopMediaPicker;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteNameHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_name_reads_from_settings(): void
    {
        app(SettingsService::class)->set('site', 'name', 'فروشگاه تست');

        $this->assertSame('فروشگاه تست', site_name());
    }

    public function test_shop_media_picker_returns_file_upload(): void
    {
        $field = ShopMediaPicker::image('image', 'products', 'تصویر');

        $this->assertInstanceOf(\Filament\Forms\Components\FileUpload::class, $field);
        $this->assertSame('products', $field->getDirectory());
        $this->assertSame('filament.forms.components.shop-image-upload', $field->getView());
    }

    public function test_shop_image_upload_view_exposes_native_file_input(): void
    {
        $html = file_get_contents(resource_path('views/filament/forms/components/shop-image-upload.blade.php'));

        $this->assertIsString($html);
        $this->assertStringContainsString('type="file"', $html);
        $this->assertStringContainsString('انتخاب فایل', $html);
        $this->assertStringContainsString('shop-image-upload__input', $html);
        $this->assertStringContainsString('$wire.upload', $html);
    }

    public function test_media_picker_lists_files_from_disk_when_registry_empty(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/test-image.jpg', 'image-bytes');

        $files = MediaPicker::make('image')->directory('products')->getLibraryFiles();

        $this->assertCount(1, $files);
        $this->assertSame('products/test-image.jpg', $files->first()->path);
    }
}
