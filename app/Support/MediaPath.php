<?php

namespace App\Support;

use Illuminate\Support\Str;

class MediaPath
{
    public static function normalize(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        $path = str_replace('\\', '/', trim($path));

        if (Str::startsWith($path, ['http://', 'https://'])) {
            $parsedPath = parse_url($path, PHP_URL_PATH);

            if (! is_string($parsedPath) || $parsedPath === '') {
                return null;
            }

            $path = $parsedPath;
        }

        $path = ltrim($path, '/');

        if (Str::startsWith($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        $publicUrlPath = parse_url((string) config('filesystems.disks.public.url'), PHP_URL_PATH);

        if (is_string($publicUrlPath) && $publicUrlPath !== '' && $publicUrlPath !== '/') {
            $prefix = trim($publicUrlPath, '/');

            if (Str::startsWith($path, $prefix.'/')) {
                $path = substr($path, strlen($prefix) + 1);
            }
        }

        $root = str_replace('\\', '/', (string) config('filesystems.disks.public.root'));

        if ($root !== '') {
            $root = trim($root, '/');

            if (Str::startsWith($path, $root.'/')) {
                $path = substr($path, strlen($root) + 1);
            }
        }

        return $path !== '' ? $path : null;
    }
}
