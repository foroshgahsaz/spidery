<?php

namespace App\Livewire\Admin;

use App\Services\Media\MediaLibrary;
use App\Services\Media\MediaRegistry;
use Filament\Notifications\Notification;
use Livewire\Component;

class MediaLibraryGrid extends Component
{
    public string $directory = '';

    public string $formRoot = '';

    public string $wireKey = '';

    /** @var array<int, string> */
    public array $checkedPaths = [];

    public function mount(string $directory = '', string $formRoot = '', string $wireKey = ''): void
    {
        $this->directory = trim($directory, '/');
        $this->formRoot = $formRoot;
        $this->wireKey = $wireKey !== '' ? $wireKey : $this->directory;
    }

    public function pickFile(string $path): void
    {
        $file = $this->files()->firstWhere('path', $path);

        $this->dispatch(
            'media-library-picked',
            path: $path,
            altText: $file?->alt_text,
            title: $file?->title,
            formRoot: $this->formRoot,
        );
    }

    public function togglePath(string $path): void
    {
        if (in_array($path, $this->checkedPaths, true)) {
            $this->checkedPaths = array_values(array_filter(
                $this->checkedPaths,
                fn (string $checked): bool => $checked !== $path,
            ));

            return;
        }

        $this->checkedPaths[] = $path;
    }

    public function selectAll(): void
    {
        $this->checkedPaths = $this->files()
            ->pluck('path')
            ->all();
    }

    public function clearSelection(): void
    {
        $this->checkedPaths = [];
    }

    public function deleteOne(string $path, MediaRegistry $registry): void
    {
        $result = $registry->deletePaths([$path]);

        $this->afterDelete($result, [$path]);
    }

    public function deleteSelected(MediaRegistry $registry): void
    {
        if ($this->checkedPaths === []) {
            return;
        }

        $result = $registry->deletePaths($this->checkedPaths);

        $this->afterDelete($result, $this->checkedPaths);
    }

    /**
     * @param  array{deleted: array<int, string>, errors: array<string, string>}  $result
     * @param  array<int, string>  $requested
     */
    protected function afterDelete(array $result, array $requested): void
    {
        $deleted = $result['deleted'];
        $errors = $result['errors'];

        $this->checkedPaths = array_values(array_filter(
            $this->checkedPaths,
            fn (string $path): bool => ! in_array($path, $deleted, true),
        ));

        if ($deleted !== []) {
            $this->dispatch('media-library-deleted', paths: $deleted, formRoot: $this->formRoot);

            Notification::make()
                ->title('حذف شد')
                ->body(count($deleted).' فایل حذف شد.')
                ->success()
                ->send();
        }

        if ($errors !== []) {
            Notification::make()
                ->title('برخی فایل‌ها حذف نشدند')
                ->body(implode("\n", array_slice(array_values($errors), 0, 3)))
                ->danger()
                ->send();
        }
    }

    public function render(MediaLibrary $library)
    {
        $files = $this->files($library);
        $directoryLabel = config('media-library.folders.'.$this->directory, $this->directory);

        return view('livewire.admin.media-library-grid', [
            'files' => $files,
            'directoryLabel' => $directoryLabel !== '' ? $directoryLabel : 'فایل‌ها',
        ]);
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    protected function files(?MediaLibrary $library = null)
    {
        return ($library ?? app(MediaLibrary::class))->filesInDirectory($this->directory);
    }
}
