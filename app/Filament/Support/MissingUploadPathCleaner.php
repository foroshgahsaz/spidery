<?php

namespace App\Filament\Support;

use App\Support\MediaPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCheckFileExistence;

class MissingUploadPathCleaner
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function clearFromFormData(array $data, ?Model $record = null): array
    {
        if ($record === null) {
            return $data;
        }

        $fields = config('media-library.models.'.$record::class, []);

        if (! is_array($fields) || $fields === []) {
            return $data;
        }

        $disk = Storage::disk('public');

        foreach ($fields as $field) {
            if (! array_key_exists($field, $data) || ! is_string($data[$field]) || $data[$field] === '') {
                continue;
            }

            $path = MediaPath::normalize($data[$field]) ?? $data[$field];

            if (! self::existsOnDisk($disk, $path)) {
                $data[$field] = null;
            }
        }

        return $data;
    }

    public static function existsOnDisk($disk, string $path): bool
    {
        try {
            return $disk->exists($path);
        } catch (UnableToCheckExistence|UnableToCheckFileExistence) {
            return false;
        }
    }
}
