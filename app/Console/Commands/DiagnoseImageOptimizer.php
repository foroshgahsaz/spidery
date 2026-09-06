<?php

namespace App\Console\Commands;

use App\Services\Media\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DiagnoseImageOptimizer extends Command
{
    protected $signature = 'shop:diagnose-image-optimizer {path? : Relative path on public disk, e.g. sliders/foo.jpg}';

    protected $description = 'Diagnose why ImageOptimizer may skip or fail for a stored file';

    public function handle(ImageOptimizer $optimizer): int
    {
        $disk = 'public';
        $storage = Storage::disk($disk);
        $root = (string) config("filesystems.disks.{$disk}.root");

        $this->info('Image optimizer diagnostics');
        $this->newLine();

        $this->table(['Check', 'Value'], [
            ['enabled', config('image-optimizer.enabled') ? 'yes' : 'no'],
            ['driver config', (string) config('image-optimizer.driver', 'gd')],
            ['gd loaded', extension_loaded('gd') ? 'yes' : 'no'],
            ['imagewebp', function_exists('imagewebp') ? 'yes' : 'no'],
            ['imagick loaded', extension_loaded('imagick') ? 'yes' : 'no'],
            ['public disk root', $root],
        ]);

        $path = $this->argument('path');

        if ($path === null) {
            $this->newLine();
            $this->comment('Pass a file path to test optimization, e.g.:');
            $this->line('  php artisan shop:diagnose-image-optimizer sliders/example.jpg');

            return self::SUCCESS;
        }

        $absolutePath = $storage->path($path);
        $directory = trim(str_replace('\\', '/', dirname($path)), '/.');

        $this->newLine();
        $this->table(['File check', 'Value'], [
            ['relative path', $path],
            ['absolute path', $absolutePath],
            ['exists (disk)', $storage->exists($path) ? 'yes' : 'no'],
            ['is_file', is_file($absolutePath) ? 'yes' : 'no'],
            ['readable', is_readable($absolutePath) ? 'yes' : 'no'],
            ['writable dir', is_writable(dirname($absolutePath)) ? 'yes' : 'no'],
            ['size bytes', is_file($absolutePath) ? (string) filesize($absolutePath) : 'n/a'],
            ['mime', is_file($absolutePath) ? ((string) @mime_content_type($absolutePath) ?: 'unknown') : 'n/a'],
            ['directory preset', (string) config("image-optimizer.directory_presets.{$directory}", 'default')],
        ]);

        $this->newLine();
        $this->line('Running optimizer...');

        $before = is_file($absolutePath) ? filesize($absolutePath) : 0;
        $result = $optimizer->optimize($disk, $path, $directory);
        $resultAbsolute = $storage->path($result);

        $this->table(['Result', 'Value'], [
            ['returned path', $result],
            ['changed', $result !== $path ? 'yes' : 'no'],
            ['output exists', $storage->exists($result) ? 'yes' : 'no'],
            ['output size', is_file($resultAbsolute) ? (string) filesize($resultAbsolute).' bytes' : 'n/a'],
            ['input still exists', $storage->exists($path) ? 'yes' : 'no'],
            ['input size before', (string) $before],
        ]);

        if ($result === $path) {
            $this->newLine();
            $this->warn('Optimizer kept the original file. Check storage/logs/laravel.log for "Image optimization" entries.');
        } else {
            $this->info('Optimization succeeded.');
        }

        return self::SUCCESS;
    }
}
