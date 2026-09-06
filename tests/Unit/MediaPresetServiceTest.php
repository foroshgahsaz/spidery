<?php

namespace Tests\Unit;

use App\Services\Media\MediaPresetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaPresetServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_defaults_when_no_settings_saved(): void
    {
        $service = app(MediaPresetService::class);

        $preset = $service->forDirectory('sliders');

        $this->assertIsArray($preset);
        $this->assertSame(1920, $preset['max_width'] ?? null);
        $this->assertSame('webp', $preset['format'] ?? null);
    }

    public function test_it_persists_custom_slider_dimensions(): void
    {
        $service = app(MediaPresetService::class);
        $rows = $service->forAdminForm();

        foreach ($rows as &$row) {
            if (($row['key'] ?? '') === 'slider') {
                $row['max_width'] = 1600;
                $row['max_height'] = 900;
            }
        }
        unset($row);

        $service->saveFromAdminForm($rows);

        $preset = app(MediaPresetService::class)->forDirectory('sliders');

        $this->assertSame(1600, $preset['max_width'] ?? null);
        $this->assertSame(900, $preset['max_height'] ?? null);
    }
}
