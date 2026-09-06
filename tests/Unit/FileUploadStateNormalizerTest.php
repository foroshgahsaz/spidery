<?php

namespace Tests\Unit;

use App\Filament\Support\FileUploadStateNormalizer;
use Tests\TestCase;

class FileUploadStateNormalizerTest extends TestCase
{
    public function test_it_converts_string_paths_to_array_state(): void
    {
        $livewire = new class
        {
            public array $data = [
                'logo' => 'brands/example.webp',
            ];
        };

        FileUploadStateNormalizer::normalizeStatePath($livewire, 'data.logo');

        $this->assertIsArray($livewire->data['logo']);
        $this->assertSame('brands/example.webp', array_values($livewire->data['logo'])[0]);
    }

    public function test_it_converts_empty_string_to_empty_array(): void
    {
        $livewire = new class
        {
            public array $data = [
                'logo' => '',
            ];
        };

        FileUploadStateNormalizer::normalizeStatePath($livewire, 'data.logo');

        $this->assertSame([], $livewire->data['logo']);
    }
}
