<?php

namespace App\Filament\Support;

use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FileUploadSanitizer
{
    public static function sanitize(Component $livewire, Form $form, ?Model $record = null): void
    {
        foreach ($form->getFlatFields(withHidden: true) as $field) {
            if (! $field instanceof BaseFileUpload) {
                continue;
            }

            $statePath = $field->getStatePath();

            if ($statePath === null) {
                continue;
            }

            $state = data_get($livewire, $statePath);

            if (! is_array($state)) {
                continue;
            }

            $fallback = $record?->getAttribute($field->getName());

            data_set(
                $livewire,
                $statePath,
                self::sanitizeState(
                    $state,
                    is_string($fallback) ? $fallback : null,
                    $statePath,
                ),
            );
        }
    }

    /**
     * @param  array<string, TemporaryUploadedFile|string>  $state
     * @return array<string, TemporaryUploadedFile|string>
     */
    public static function sanitizeState(array $state, ?string $fallbackPath = null, ?string $statePath = null): array
    {
        $hadInvalidTemp = false;

        $cleaned = collect($state)->filter(function (TemporaryUploadedFile|string $file) use (&$hadInvalidTemp): bool {
            if ($file instanceof TemporaryUploadedFile) {
                if ($file->isValid()) {
                    return true;
                }

                $hadInvalidTemp = true;

                return false;
            }

            return filled($file);
        });

        if ($cleaned->isNotEmpty()) {
            return $cleaned->all();
        }

        if ($hadInvalidTemp && blank($fallbackPath)) {
            throw ValidationException::withMessages([
                $statePath ?? 'file' => 'آپلود تصویر کامل نشد یا فایل موقت روی سرور پیدا نشد. تا پایان آپلود صبر کنید و دوباره انتخاب کنید. اگر چند سرور دارید، SESSION_DRIVER=redis را فعال کنید.',
            ]);
        }

        if (filled($fallbackPath)) {
            return [(string) Str::uuid() => $fallbackPath];
        }

        return [];
    }
}
