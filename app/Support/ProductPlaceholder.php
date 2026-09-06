<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ProductPlaceholder
{
    public static function ensure(string $relativePath, string $label = ''): void
    {
        $disk = Storage::disk('public');

        if ($disk->exists($relativePath)) {
            try {
                if ($disk->size($relativePath) >= 512) {
                    return;
                }
            } catch (\Throwable) {
                // Continue and replace invalid placeholder.
            }

            $disk->delete($relativePath);
        }

        $disk->makeDirectory(dirname($relativePath));

        $template = self::findValidTemplate();

        if ($template) {
            $disk->put($relativePath, file_get_contents($template));

            return;
        }

        $fullPath = $disk->path($relativePath);

        if (function_exists('imagecreatetruecolor')) {
            self::writeWithGd($fullPath, $label);

            return;
        }

        $disk->put($relativePath, self::minimalJpeg());
    }

    protected static function findValidTemplate(): ?string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists('products')) {
            return null;
        }

        foreach ($disk->files('products') as $file) {
            if (! preg_match('/\.(jpe?g|webp|png)$/i', $file)) {
                continue;
            }

            try {
                if ($disk->size($file) > 5000) {
                    return $disk->path($file);
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    protected static function writeWithGd(string $fullPath, string $label): void
    {
        $width = 900;
        $height = 900;
        $image = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($image, 245, 248, 250);
        $accent = imagecolorallocate($image, 114, 57, 234);
        $textColor = imagecolorallocate($image, 62, 66, 84);

        imagefilledrectangle($image, 0, 0, $width, $height, $background);
        imagerectangle($image, 40, 40, $width - 41, $height - 41, $accent);

        if ($label !== '') {
            $truncated = mb_strlen($label) > 28 ? mb_substr($label, 0, 28).'…' : $label;
            imagestring($image, 5, 80, (int) ($height / 2) - 10, $truncated, $textColor);
        }

        imagejpeg($image, $fullPath, 88);
        imagedestroy($image);
    }

    protected static function minimalJpeg(): string
    {
        return base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP/bAEMBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAAKAAoDASIAAhEBAxEB/8QAFgABAQEAAAAAAAAAAAAAAAAAAAYH/8QAIRAAAgICAgIDAAAAAAAAAAAAAAECAwQRAAUSITET/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=',
            true
        ) ?: '';
    }
}
