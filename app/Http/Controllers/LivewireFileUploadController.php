<?php

namespace App\Http\Controllers;

use App\Support\LivewireUploadFilename;
use App\Support\StoragePermissionFixer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\FileUploadController as BaseFileUploadController;

class LivewireFileUploadController extends BaseFileUploadController
{
    public function handle()
    {
        Log::info('livewire.upload.request', [
            'disk' => FileUploadConfiguration::disk(),
            'file_keys' => array_keys(request()->allFiles()),
            'host' => request()->getHost(),
            'scheme' => request()->getScheme(),
        ]);

        try {
            $response = parent::handle();

            Log::info('livewire.upload.response', [
                'paths' => is_array($response) ? count($response['paths'] ?? []) : null,
            ]);

            return $response;
        } catch (\Throwable $exception) {
            Log::error('livewire.upload.failed', [
                'message' => $exception->getMessage(),
                'type' => $exception::class,
            ]);

            throw $exception;
        }
    }

    public function validateAndStore($files, $disk)
    {
        $files = Arr::wrap($files);

        Validator::make(['files' => $files], [
            'files.*' => FileUploadConfiguration::rules(),
        ])->validate();

        $storePath = trim(FileUploadConfiguration::directory(), '/');

        $fileHashPaths = collect($files)->map(function (UploadedFile $file) use ($disk, $storePath) {
            if (! $file->isValid()) {
                Log::error('livewire.upload.invalid_php_upload', [
                    'error' => $file->getErrorMessage(),
                    'error_code' => $file->getError(),
                    'pathname' => $file->getPathname(),
                ]);

                throw ValidationException::withMessages([
                    'files' => 'فایل به سرور نرسید. دوباره انتخاب کنید. اگر تکرار شد، upload_tmp_dir سرور را بررسی کنید.',
                ]);
            }

            $filename = LivewireUploadFilename::generate($file);
            $storedPath = $this->storeTempUpload($file, $disk, $storePath, $filename);

            Log::info('livewire.upload.stored', [
                'disk' => $disk,
                'path' => $storedPath,
                'absolute' => Storage::disk($disk)->path($storedPath),
                'size' => Storage::disk($disk)->size($storedPath),
            ]);

            return $storedPath;
        });

        return $fileHashPaths->map(function (string $path) use ($storePath) {
            $prefix = $storePath.'/';

            return str_starts_with($path, $prefix)
                ? substr($path, strlen($prefix))
                : $path;
        });
    }

    protected function storeTempUpload(UploadedFile $file, string $disk, string $storePath, string $filename): string
    {
        $storage = Storage::disk($disk);
        $absoluteDir = $storage->path($storePath);

        if (! is_dir($absoluteDir)) {
            $storage->makeDirectory($storePath);
        }

        if (! StoragePermissionFixer::isDirectoryWritable($absoluteDir)) {
            Log::error('livewire.upload.dir_not_writable', [
                'disk' => $disk,
                'absolute' => $absoluteDir,
                'perms' => is_dir($absoluteDir) ? substr(sprintf('%o', fileperms($absoluteDir)), -4) : null,
                'owner' => is_dir($absoluteDir) ? fileowner($absoluteDir) : null,
            ]);

            throw ValidationException::withMessages([
                'files' => 'پوشه آپلود قابل نوشتن نیست. روی سرور (به‌عنوان root) اجرا کنید: chown -R www-data:www-data /data/livewire-tmp && chmod -R 775 /data/livewire-tmp',
            ]);
        }

        $relativePath = trim($storePath.'/'.$filename, '/');

        $storedPath = $file->storeAs($storePath, $filename, ['disk' => $disk]);

        if (is_string($storedPath) && $storedPath !== '' && $storage->exists($storedPath)) {
            return $storedPath;
        }

        $stream = fopen($file->getRealPath(), 'r');

        if ($stream === false) {
            throw ValidationException::withMessages([
                'files' => 'خواندن فایل آپلودشده ممکن نشد. دوباره انتخاب کنید.',
            ]);
        }

        try {
            $written = $storage->writeStream($relativePath, $stream);
        } finally {
            fclose($stream);
        }

        if ($written !== true || ! $storage->exists($relativePath)) {
            Log::error('livewire.upload.store_failed', [
                'disk' => $disk,
                'store_path' => $storePath,
                'relative_path' => $relativePath,
                'absolute' => $storage->path($relativePath),
                'pathname' => $file->getPathname(),
                'size' => $file->getSize(),
            ]);

            throw ValidationException::withMessages([
                'files' => 'فایل روی دیسک ذخیره نشد. دستور php artisan shop:fix-storage-permissions را روی سرور اجرا کنید.',
            ]);
        }

        return $relativePath;
    }
}
