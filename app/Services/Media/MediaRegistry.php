<?php

namespace App\Services\Media;

use App\Models\MediaFile;
use App\Models\MediaUsage;
use App\Support\MediaPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaRegistry
{
    public function registerFromPath(
        string $disk,
        string $path,
        ?string $originalName = null,
        ?int $uploadedBy = null,
    ): MediaFile {
        $path = MediaPath::normalize($path) ?? ltrim(str_replace('\\', '/', $path), '/');
        $folder = trim(str_replace('\\', '/', dirname($path)), '/.');
        $folder = $folder === '.' ? 'uploads' : $folder;

        $storage = Storage::disk($disk);
        $absolutePath = $storage->path($path);
        $dimensions = is_file($absolutePath) ? @getimagesize($absolutePath) : false;

        return MediaFile::query()->updateOrCreate(
            ['path_hash' => MediaFile::pathHash($disk, $path)],
            [
                'disk' => $disk,
                'path' => $path,
                'folder' => $folder,
                'mime_type' => is_file($absolutePath) ? (string) (@mime_content_type($absolutePath) ?: null) : null,
                'size' => $storage->exists($path)
                    ? (int) (rescue(fn () => $storage->size($path), 0, false) ?? 0)
                    : 0,
                'width' => is_array($dimensions) ? (int) ($dimensions[0] ?? 0) ?: null : null,
                'height' => is_array($dimensions) ? (int) ($dimensions[1] ?? 0) ?: null : null,
                'original_name' => $originalName,
                'uploaded_by' => $uploadedBy ?? Auth::id(),
            ],
        );
    }

    public function updateSeo(
        string $disk,
        string $path,
        ?string $altText = null,
        ?string $title = null,
    ): ?MediaFile {
        $mediaFile = $this->registerFromPath($disk, $path);

        $mediaFile->fill([
            'alt_text' => $altText,
            'title' => $title,
        ]);
        $mediaFile->save();

        return $mediaFile;
    }

    /** @param  array<int, string>  $fields */
    public function syncModel(Model $model, array $fields): void
    {
        $modelType = $model::class;
        $modelId = (int) $model->getKey();

        if ($modelId <= 0) {
            return;
        }

        foreach ($fields as $field) {
            $path = $model->getAttribute($field);

            if (! is_string($path) || $path === '') {
                MediaUsage::query()
                    ->where('usable_type', $modelType)
                    ->where('usable_id', $modelId)
                    ->where('field', $field)
                    ->delete();

                continue;
            }

            $mediaFile = $this->registerFromPath('public', $path);

            MediaUsage::query()->updateOrCreate(
                [
                    'usable_type' => $modelType,
                    'usable_id' => $modelId,
                    'field' => $field,
                ],
                ['media_file_id' => $mediaFile->id],
            );
        }
    }

    /** @param  array<string, string|null>  $paths keyed by logical field name */
    public function syncPaths(array $paths, string $usableType, int $usableId = 0): void
    {
        foreach ($paths as $field => $path) {
            if (! is_string($path) || $path === '') {
                if ($usableId > 0) {
                    MediaUsage::query()
                        ->where('usable_type', $usableType)
                        ->where('usable_id', $usableId)
                        ->where('field', $field)
                        ->delete();
                }

                continue;
            }

            $this->registerFromPath('public', $path);

            if ($usableId <= 0) {
                continue;
            }

            $mediaFile = MediaFile::query()->where('disk', 'public')->where('path', $path)->first();

            if ($mediaFile === null) {
                continue;
            }

            MediaUsage::query()->updateOrCreate(
                [
                    'usable_type' => $usableType,
                    'usable_id' => $usableId,
                    'field' => $field,
                ],
                ['media_file_id' => $mediaFile->id],
            );
        }
    }

    public function detachModel(Model $model): void
    {
        MediaUsage::query()
            ->where('usable_type', $model::class)
            ->where('usable_id', $model->getKey())
            ->delete();
    }

    public function isPathInUse(string $path, string $disk = 'public'): bool
    {
        $mediaFile = MediaFile::query()
            ->where('disk', $disk)
            ->where('path', ltrim($path, '/'))
            ->first();

        if ($mediaFile === null) {
            return $this->pathReferencedInContentTables($path);
        }

        if ($mediaFile->usages()->exists()) {
            return true;
        }

        return $this->pathReferencedInContentTables($path);
    }

    /**
     * @param  array<int, string>  $paths
     * @return array{deleted: array<int, string>, errors: array<string, string>}
     */
    public function deletePaths(array $paths, string $disk = 'public'): array
    {
        $deleted = [];
        $errors = [];

        foreach (array_values(array_unique(array_filter($paths))) as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            try {
                $this->deletePath($path, $disk);
                $deleted[] = $path;
            } catch (\Throwable $exception) {
                $errors[$path] = $exception->getMessage();
            }
        }

        return [
            'deleted' => $deleted,
            'errors' => $errors,
        ];
    }

    public function deletePath(string $path, string $disk = 'public'): void
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if ($path === '' || str_contains($path, '..')) {
            throw new \RuntimeException('مسیر فایل نامعتبر است.');
        }

        if ($this->isPathInUse($path, $disk)) {
            throw new \RuntimeException('این فایل هنوز در محتوا استفاده می‌شود و قابل حذف نیست.');
        }

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }

        MediaFile::query()
            ->where('disk', $disk)
            ->where('path', $path)
            ->delete();
    }

    protected function pathReferencedInContentTables(string $path): bool
    {
        $path = ltrim($path, '/');

        foreach (config('media-library.models', []) as $modelClass => $fields) {
            if (! class_exists($modelClass)) {
                continue;
            }

            /** @var Model $modelClass */
            $query = $modelClass::query();

            $query->where(function ($builder) use ($fields, $path) {
                foreach ($fields as $field) {
                    $builder->orWhere($field, $path);
                }
            });

            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }

    public function usageLabel(MediaUsage $usage): string
    {
        $model = $usage->usable;

        if ($model === null) {
            return Str::afterLast($usage->usable_type, '\\').' #'.$usage->usable_id;
        }

        $name = method_exists($model, 'getFilamentName')
            ? $model->getFilamentName()
            : ($model->getAttribute('name')
                ?? $model->getAttribute('title')
                ?? '#'.$model->getKey());

        return Str::afterLast($usage->usable_type, '\\').': '.$name.' ('.$usage->field.')';
    }
}
