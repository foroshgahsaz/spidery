<?php

namespace App\Console\Commands;

use App\Services\Media\MediaRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportExistingMedia extends Command
{
    protected $signature = 'shop:import-existing-media {--folder= : Import a single folder only}';

    protected $description = 'Register existing files on the public disk into the media library';

    public function handle(MediaRegistry $registry): int
    {
        $disk = Storage::disk('public');
        $folders = $this->option('folder')
            ? [(string) $this->option('folder')]
            : array_keys(config('media-library.folders', []));

        $imported = 0;
        $diskRoot = rtrim((string) config('filesystems.disks.public.root'), '/');

        foreach ($folders as $folder) {
            $folder = trim($folder, '/');

            if ($folder === '') {
                continue;
            }

            if (! $disk->exists($folder)) {
                $this->warn("Folder missing on public disk: {$folder} ({$diskRoot}/{$folder})");

                continue;
            }

            foreach ($disk->allFiles($folder) as $path) {
                $registry->registerFromPath('public', $path);
                $imported++;
            }
        }

        if ($imported === 0) {
            $this->warn('No files found on public disk folders.');
            $this->line('Run: php artisan shop:verify-storage');
            $this->line('Run: php artisan shop:sync-public-storage');
        }

        $this->info("Registered {$imported} file(s) in media library.");

        return self::SUCCESS;
    }
}
