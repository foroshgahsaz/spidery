<?php

namespace App\Services\Media;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Throwable;

class ImageOptimizer
{
    private ?ImageManager $manager = null;

    public function isEnabled(): bool
    {
        return (bool) config('image-optimizer.enabled', true);
    }

    public function optimize(string $disk, string $relativePath, string $directory): string
    {
        if (! $this->isEnabled()) {
            return $relativePath;
        }

        $manager = $this->manager();

        if ($manager === null) {
            Log::warning('Image optimization skipped: no GD/Imagick driver available.');

            return $relativePath;
        }

        $storage = Storage::disk($disk);
        $absolutePath = $storage->path($relativePath);

        if (! is_file($absolutePath)) {
            Log::warning('Image optimization skipped: file not found on disk.', [
                'path' => $relativePath,
                'absolute' => $absolutePath,
                'disk_root' => config("filesystems.disks.{$disk}.root"),
            ]);

            return $relativePath;
        }

        if (! $this->shouldOptimize($absolutePath)) {
            Log::warning('Image optimization skipped: unsupported file type.', [
                'path' => $relativePath,
                'mime' => $this->resolveMimeType($absolutePath),
                'extension' => pathinfo($absolutePath, PATHINFO_EXTENSION),
            ]);

            return $relativePath;
        }

        $preset = $this->presetForDirectory($directory);

        if ($preset === null) {
            return $relativePath;
        }

        try {
            return $this->process($storage, $relativePath, $absolutePath, $preset, $manager);
        } catch (Throwable $exception) {
            Log::warning('Image optimization failed; keeping original upload.', [
                'path' => $relativePath,
                'directory' => $directory,
                'error' => $exception->getMessage(),
            ]);

            return $relativePath;
        }
    }

    protected function manager(): ?ImageManager
    {
        if ($this->manager instanceof ImageManager) {
            return $this->manager;
        }

        $driver = (string) config('image-optimizer.driver', 'gd');

        if ($driver === 'imagick' && extension_loaded('imagick')) {
            $this->manager = new ImageManager(new ImagickDriver);

            return $this->manager;
        }

        if (extension_loaded('gd') && function_exists('imagewebp')) {
            $this->manager = new ImageManager(new GdDriver);

            return $this->manager;
        }

        return null;
    }

    protected function shouldOptimize(string $absolutePath): bool
    {
        $mimeType = $this->resolveMimeType($absolutePath);
        $extension = strtolower((string) pathinfo($absolutePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['svg', 'gif', 'ico'], true)) {
            return false;
        }

        if (in_array($mimeType, [
            'image/jpeg',
            'image/jpg',
            'image/pjpeg',
            'image/png',
            'image/x-png',
            'image/webp',
            'image/avif',
        ], true)) {
            return true;
        }

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'avif'], true);
    }

    protected function resolveMimeType(string $absolutePath): string
    {
        $mimeType = (string) @mime_content_type($absolutePath);

        if ($mimeType !== '' && $mimeType !== 'application/octet-stream') {
            return $mimeType;
        }

        $imageInfo = @getimagesize($absolutePath);

        return is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';
    }

    protected function presetForDirectory(string $directory): ?array
    {
        $preset = app(MediaPresetService::class)->forDirectory($directory);

        return is_array($preset) ? $preset : null;
    }

    protected function process(
        Filesystem $storage,
        string $relativePath,
        string $absolutePath,
        array $preset,
        ImageManager $manager,
    ): string {
        $image = $manager->read($absolutePath);
        $image = $this->transform($image, $preset);

        $format = (string) ($preset['format'] ?? 'webp');
        $outputRelativePath = $this->outputPath($relativePath, $format);
        $outputAbsolutePath = $storage->path($outputRelativePath);

        $directory = dirname($outputAbsolutePath);

        if (! is_dir($directory)) {
            $storage->makeDirectory(trim(str_replace('\\', '/', dirname($outputRelativePath)), '/'));
        }

        $this->encode($image, $preset)->save($outputAbsolutePath);

        if ($outputRelativePath !== $relativePath && $storage->exists($relativePath)) {
            $storage->delete($relativePath);
        }

        return $outputRelativePath;
    }

    protected function transform(ImageInterface $image, array $preset): ImageInterface
    {
        $coverWidth = (int) ($preset['cover_width'] ?? 0);
        $coverHeight = (int) ($preset['cover_height'] ?? 0);

        if ($coverWidth > 0 && $coverHeight > 0) {
            return $image->cover($coverWidth, $coverHeight);
        }

        $maxWidth = (int) ($preset['max_width'] ?? 0);
        $maxHeight = (int) ($preset['max_height'] ?? 0);

        if ($maxWidth > 0 || $maxHeight > 0) {
            return $image->scaleDown(
                width: $maxWidth > 0 ? $maxWidth : null,
                height: $maxHeight > 0 ? $maxHeight : null,
            );
        }

        return $image;
    }

    protected function encode(ImageInterface $image, array $preset): EncodedImageInterface
    {
        $quality = (int) ($preset['quality'] ?? 85);

        return match ((string) ($preset['format'] ?? 'webp')) {
            'jpg', 'jpeg' => $image->toJpeg(quality: $quality),
            'png' => $image->toPng(),
            default => $image->toWebp(quality: $quality),
        };
    }

    protected function outputPath(string $relativePath, string $format): string
    {
        $directory = $this->directoryOf($relativePath);
        $filename = pathinfo($relativePath, PATHINFO_FILENAME).'.'.$format;

        return $directory === '' ? $filename : $directory.'/'.$filename;
    }

    protected function directoryOf(string $relativePath): string
    {
        $directory = str_replace('\\', '/', dirname($relativePath));
        $directory = trim($directory, '/');

        if ($directory === '.' || $directory === '') {
            return '';
        }

        return $directory;
    }

  /**
   * Resize/encode a source image into a dedicated output path (display thumbnails).
   *
   * @param  array<string, mixed>  $preset
   */
  public function optimizeFromPreset(string $disk, string $sourcePath, array $preset, string $outputPath): ?string
  {
    if (! $this->isEnabled()) {
      return null;
    }

    $manager = $this->manager();

    if ($manager === null) {
      return null;
    }

    $storage = Storage::disk($disk);
    $absolutePath = $storage->path($sourcePath);

    if (! is_file($absolutePath) || ! $this->shouldOptimize($absolutePath)) {
      return null;
    }

    try {
      $image = $manager->read($absolutePath);
      $image = $this->transform($image, $preset);

      $directory = $this->directoryOf($outputPath);

      if ($directory !== '') {
        $storage->makeDirectory($directory);
      }

      $absoluteOutput = $storage->path($outputPath);
      $absoluteDir = dirname($absoluteOutput);

      if (! is_dir($absoluteDir)) {
        mkdir($absoluteDir, 0775, true);
      }

      $this->encode($image, $preset)->save($absoluteOutput);

      return $outputPath;
    } catch (Throwable $exception) {
      Log::warning('Display thumbnail generation failed.', [
        'source' => $sourcePath,
        'output' => $outputPath,
        'error' => $exception->getMessage(),
      ]);

      return null;
    }
  }
}
