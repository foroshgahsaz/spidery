<?php

namespace App\Filament\Forms\Components;

use App\Models\MediaFile;
use App\Support\ShopMedia;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class MediaPicker extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->hintAction($this->getLibraryAction());
    }

    public function getLibraryAction(): Action
    {
        return Action::make('pickFromLibrary')
            ->label('از کتابخانه')
            ->icon('heroicon-m-photo')
            ->modalHeading('مرکز فایل')
            ->modalDescription('یکی از تصاویر موجود را انتخاب کنید. برای فایل جدید، همین‌جا مودال را ببندید و روی کادر آپلود فیلد بگذارید.')
            ->modalWidth('7xl')
            ->modalSubmitActionLabel('تأیید و استفاده')
            ->modalCancelActionLabel('انصراف')
            ->form(fn (): array => [
                Radio::make('selected_path')
                    ->label($this->getLibraryFiles()->isEmpty()
                        ? 'فایلی روی دیسک نیست. مودال را ببندید و فایل جدید آپلود کنید.'
                        : 'یک تصویر را انتخاب کنید')
                    ->options($this->libraryRadioOptions())
                    ->allowHtml()
                    ->columns(5)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $path = $data['selected_path'] ?? null;

                if (! is_string($path) || $path === '') {
                    return;
                }

                $this->state([(string) Str::uuid() => $path]);
            });
    }

    protected function libraryRadioOptions(): array
    {
        return $this->getLibraryFiles()
            ->mapWithKeys(function (object $file): array {
                $url = e((string) ($file->url ?? ''));
                $name = e((string) ($file->name ?? basename((string) $file->path)));

                return [
                    $file->path => new HtmlString(
                        '<span class="media-picker-radio-card">'
                        .($url !== '' ? '<img src="'.$url.'" alt="'.$name.'" />' : '')
                        .'<span>'.$name.'</span>'
                        .'</span>'
                    ),
                ];
            })
            ->all();
    }

    /** @return Collection<int, object> */
    public function getLibraryFiles(): Collection
    {
        $directory = trim((string) $this->getDirectory(), '/');
        $disk = Storage::disk('public');
        $files = collect();

        try {
            MediaFile::query()
                ->latest('id')
                ->limit(200)
                ->get()
                ->each(function (MediaFile $file) use ($files): void {
                    if (! $this->safeExists($file->disk, $file->path)) {
                        return;
                    }

                    $files->put($file->path, (object) [
                        'path' => $file->path,
                        'url' => $file->url(),
                        'name' => $file->original_name ?: basename($file->path),
                    ]);
                });
        } catch (\Throwable) {
            // Library listing must never break the edit form.
        }

        $folders = array_values(array_unique(array_filter([
            $directory,
            ...array_keys(config('media-library.folders', [])),
        ])));

        foreach ($folders as $folder) {
            try {
                if ($folder === '' || ! $disk->exists($folder)) {
                    continue;
                }

                foreach ($disk->allFiles($folder) as $path) {
                    if ($files->has($path) || ! $this->isImagePath($path)) {
                        continue;
                    }

                    $files->put($path, (object) [
                        'path' => $path,
                        'url' => ShopMedia::url($path),
                        'name' => basename($path),
                    ]);
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $files
            ->sortByDesc(function (object $file) use ($directory): int {
                $path = (string) $file->path;
                $inFolder = $directory !== '' && str_starts_with($path, $directory.'/') ? 1 : 0;

                return $inFolder;
            })
            ->take(80)
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
