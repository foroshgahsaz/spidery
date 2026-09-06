<?php

namespace Tests\Unit;

use App\Filament\Support\FileUploadSanitizer;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Tests\TestCase;

class FileUploadSanitizerTest extends TestCase
{
    public function test_it_removes_invalid_temp_uploads_and_restores_fallback(): void
    {
        $invalid = TemporaryUploadedFile::createFromLivewire('livewire-tmp');

        $sanitized = FileUploadSanitizer::sanitizeState([
            (string) Str::uuid() => $invalid,
        ], 'products/existing.jpg');

        $this->assertCount(1, $sanitized);
        $this->assertSame('products/existing.jpg', array_values($sanitized)[0]);
    }

    public function test_it_keeps_valid_string_paths(): void
    {
        $sanitized = FileUploadSanitizer::sanitizeState([
            'abc' => 'products/existing.jpg',
        ]);

        $this->assertSame(['abc' => 'products/existing.jpg'], $sanitized);
    }

    public function test_it_throws_when_temp_upload_is_invalid_and_no_fallback_exists(): void
    {
        $invalid = TemporaryUploadedFile::createFromLivewire('livewire-tmp');

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        FileUploadSanitizer::sanitizeState([
            (string) Str::uuid() => $invalid,
        ], null, 'mountedTableActionsData.0.image');
    }
}
