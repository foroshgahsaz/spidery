<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class StoragePermissionFixer
{
    /** @return list<string> */
    public static function requiredPaths(): array
    {
        $publicRoot = rtrim((string) config('filesystems.disks.public.root'), '/');

        return array_values(array_unique(array_filter([
            $publicRoot,
            Storage::disk('livewire-tmp')->path((string) config('livewire.temporary_file_upload.directory', 'livewire-tmp')),
            Storage::disk('public')->path('products'),
            Storage::disk('public')->path('brands'),
            Storage::disk('public')->path('sliders'),
            Storage::disk('public')->path('categories'),
            Storage::disk('public')->path('posts'),
            Storage::disk('public')->path('seo'),
            Storage::disk('public')->path('pages'),
        ])));
    }

    public static function runningAsRoot(): bool
    {
        return function_exists('posix_geteuid') && posix_geteuid() === 0;
    }

    public static function webUser(): string
    {
        return 'www-data';
    }

    public static function fix(): void
    {
        $runningAsRoot = self::runningAsRoot();
        $webUser = self::webUser();

        foreach (self::requiredPaths() as $path) {
            if (! is_dir($path)) {
                @mkdir($path, 0775, true);
            }

            if (! is_dir($path)) {
                continue;
            }

            if ($runningAsRoot) {
                @chown($path, $webUser);
                @chgrp($path, $webUser);
            }

            @chmod($path, 0775);

            if (! self::isDirectoryWritable($path) && $runningAsRoot) {
                @chmod($path, 0777);
            }
        }
    }

    public static function isDirectoryWritable(string $absoluteDir): bool
    {
        if (! is_dir($absoluteDir)) {
            return false;
        }

        if (is_writable($absoluteDir)) {
            return true;
        }

        $probe = $absoluteDir.'/.write-probe-'.uniqid('', true);

        if (@file_put_contents($probe, '1') !== false) {
            @unlink($probe);

            return true;
        }

        return false;
    }
}
