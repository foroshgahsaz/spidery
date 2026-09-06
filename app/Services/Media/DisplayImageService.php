<?php

namespace App\Services\Media;

use App\Support\MediaPath;
use App\Support\ShopMedia;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DisplayImageService
{
    public function __construct(
        protected HomepageImageService $homepageImages,
        protected ImageOptimizer $optimizer,
    ) {}

    public function url(string $section, ?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        if (Str::startsWith($path, 'shop/')) {
            return asset($path);
        }

        $path = MediaPath::normalize($path) ?? ltrim($path, '/');

        if ($path === '') {
            return null;
        }

        $preset = $this->homepageImages->forSection($section);

        if ($preset === null || ! ($preset['enabled'] ?? true)) {
            return ShopMedia::url($path);
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return route('shop.thumbnail', [
            'section' => $section,
            'path' => $path,
        ]);
    }

    public function render(string $section, string $path): ?\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $path = MediaPath::normalize($path) ?? ltrim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        $preset = $this->homepageImages->optimizerPreset($section);
        $cachePath = $this->cachePath($section, $path, $preset);

        if (! $disk->exists($cachePath)) {
            $this->generateCache($disk, $path, $cachePath, $preset);
        }

        if (! $disk->exists($cachePath)) {
            return response()->file($disk->path($path));
        }

        return response()->file($disk->path($cachePath), [
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    /** @param  array<string, mixed>  $preset */
    protected function cachePath(string $section, string $path, array $preset): string
    {
        $hash = hash('sha256', $section.'|'.$path.'|'.json_encode($preset));
        $format = (string) ($preset['format'] ?? 'webp');

        return 'thumbs/'.$section.'/'.substr($hash, 0, 40).'.'.$format;
    }

    /** @param  array<string, mixed>  $preset */
    protected function generateCache(Filesystem $disk, string $sourcePath, string $cachePath, array $preset): void
    {
        $optimized = $this->optimizer->optimizeFromPreset('public', $sourcePath, $preset, $cachePath);

        if ($optimized !== null) {
            return;
        }

        $disk->makeDirectory(dirname($cachePath));
        $disk->copy($sourcePath, $cachePath);
    }
}
