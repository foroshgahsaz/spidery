<?php

namespace App\Services\Media;

use App\Models\MediaFile;
use App\Support\ShopMedia;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MediaLibrary
{
    public function __construct(
        protected MediaRegistry $registry,
    ) {}

    /** @return Collection<int, object{path: string, url: ?string, name: string, alt_text: ?string, title: ?string}> */
    public function filesInDirectory(string $directory, int $limit = 60): Collection
    {
        $directory = trim($directory, '/');
        $disk = Storage::disk('public');
        $files = collect();

        try {
            MediaFile::query()
                ->when($directory !== '', fn ($query) => $query->where('folder', $directory))
                ->latest('id')
                ->limit(120)
                ->get()
                ->each(function (MediaFile $file) use ($files): void {
                    if (! $this->safeExists($file->disk, $file->path)) {
                        return;
                    }

                    $files->put($file->path, (object) [
                        'path' => $file->path,
                        'url' => $file->url(),
                        'name' => $file->original_name ?: basename($file->path),
                        'alt_text' => $file->alt_text,
                        'title' => $file->title,
                    ]);
                });
        } catch (\Throwable) {
            // Listing must never break the form.
        }

        if ($directory !== '' && $disk->exists($directory)) {
            try {
                foreach ($disk->allFiles($directory) as $path) {
                    if ($files->has($path) || ! $this->isImagePath($path)) {
                        continue;
                    }

                    $files->put($path, (object) [
                        'path' => $path,
                        'url' => ShopMedia::url($path),
                        'name' => basename($path),
                        'alt_text' => null,
                        'title' => null,
                    ]);
                }
            } catch (\Throwable) {
                // Ignore unreadable folders.
            }
        }

        return $files
            ->sortByDesc(function (object $file): int {
                try {
                    return (int) @filemtime(Storage::disk('public')->path($file->path));
                } catch (\Throwable) {
                    return 0;
                }
            })
            ->take($limit)
            ->values();
    }

    protected function safeExists(string $disk, string $path): bool
    {
        try {
            return Storage::disk($disk)->exists($path);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function isImagePath(string $path): bool
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'svg'], true);
    }
}
