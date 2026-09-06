<?php

namespace Tests\Unit;

use App\Services\Media\HomepageImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageImageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_defaults_when_no_settings_saved(): void
    {
        $service = app(HomepageImageService::class);

        $preset = $service->forSection('deals');

        $this->assertIsArray($preset);
        $this->assertSame('پیشنهاد شگفت‌انگیز', $preset['label'] ?? null);
        $this->assertSame(400, $preset['width'] ?? null);
        $this->assertTrue($preset['enabled'] ?? false);
    }

    public function test_it_persists_custom_deals_dimensions(): void
    {
        $service = app(HomepageImageService::class);
        $rows = $service->forAdminForm();

        foreach ($rows as &$row) {
            if (($row['key'] ?? '') === 'deals') {
                $row['width'] = 320;
                $row['height'] = 320;
                $row['enabled'] = false;
            }
        }
        unset($row);

        $service->saveFromAdminForm($rows);

        $preset = app(HomepageImageService::class)->forSection('deals');

        $this->assertSame(320, $preset['width'] ?? null);
        $this->assertSame(320, $preset['height'] ?? null);
        $this->assertFalse($preset['enabled'] ?? true);
    }

    public function test_optimizer_preset_uses_cover_dimensions_for_hero(): void
    {
        $optimizerPreset = app(HomepageImageService::class)->optimizerPreset('hero');

        $this->assertSame(1920, $optimizerPreset['cover_width'] ?? null);
        $this->assertSame(380, $optimizerPreset['cover_height'] ?? null);
        $this->assertArrayNotHasKey('max_width', $optimizerPreset);
    }
}
