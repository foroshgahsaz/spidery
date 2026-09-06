<?php

namespace App\Filament\Support;

use App\Support\MediaPath;
use Illuminate\Support\Str;

class FileUploadStateNormalizer
{
    public static function normalizeStatePath(object $livewire, string $statePath): void
    {
        $state = data_get($livewire, $statePath);

        if (! is_string($state)) {
            return;
        }

        if ($state === '') {
            data_set($livewire, $statePath, []);

            return;
        }

        $path = MediaPath::normalize($state) ?? $state;

        data_set(
            $livewire,
            $statePath,
            $path !== '' ? [(string) Str::uuid() => $path] : [],
        );
    }

    public static function normalizeForm(object $livewire, \Filament\Forms\Form $form): void
    {
        foreach ($form->getFlatFields(withHidden: true) as $field) {
            if (! $field instanceof \Filament\Forms\Components\BaseFileUpload) {
                continue;
            }

            $statePath = $field->getStatePath();

            if ($statePath !== null) {
                self::normalizeStatePath($livewire, $statePath);
            }
        }
    }
}
