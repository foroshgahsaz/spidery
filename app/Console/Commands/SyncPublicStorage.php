<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SyncPublicStorage extends Command
{
    protected $signature = 'shop:sync-public-storage
                            {--dry-run : Show what would be copied without writing files}
                            {--force : Overwrite existing files in the target disk}';

    protected $description = 'Copy legacy files from storage/app/public into the configured public disk root (e.g. Runflare /data)';

    public function handle(): int
    {
        $sourceRoot = rtrim(storage_path('app/public'), DIRECTORY_SEPARATOR);
        $targetRoot = rtrim((string) config('filesystems.disks.public.root'), DIRECTORY_SEPARATOR);

        $this->info('Public storage sync');
        $this->table(['Path', 'Location'], [
            ['source', $sourceRoot],
            ['target', $targetRoot],
            ['public url', (string) config('filesystems.disks.public.url')],
        ]);

        if ($sourceRoot === $targetRoot) {
            $this->comment('Source and target are the same. Nothing to migrate.');

            return self::SUCCESS;
        }

        if (! is_dir($sourceRoot)) {
            $this->warn("Source directory does not exist: {$sourceRoot}");

            return self::SUCCESS;
        }

        if (! is_dir($targetRoot) && ! $this->option('dry-run')) {
            File::makeDirectory($targetRoot, 0755, true);
            $this->line("Created target directory: {$targetRoot}");
        }

        $copied = 0;
        $skipped = 0;
        $overwritten = 0;

        foreach ($this->filesIn($sourceRoot) as $absolutePath) {
            $relativePath = Str::after($absolutePath, $sourceRoot.DIRECTORY_SEPARATOR);
            $targetPath = $targetRoot.DIRECTORY_SEPARATOR.$relativePath;

            if (is_file($targetPath) && ! $this->option('force')) {
                $skipped++;

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line('[dry-run] '.$relativePath);
                $copied++;

                continue;
            }

            File::ensureDirectoryExists(dirname($targetPath));

            if (is_file($targetPath)) {
                $overwritten++;
            }

            if (! @copy($absolutePath, $targetPath)) {
                $this->error("Failed to copy: {$relativePath}");

                return self::FAILURE;
            }

            $copied++;
        }

        $this->newLine();
        $this->info("Copied: {$copied}");
        $this->line("Skipped (already exists): {$skipped}");

        if ($overwritten > 0) {
            $this->line("Overwritten: {$overwritten}");
        }

        if ($this->option('dry-run')) {
            $this->comment('Dry run only. Re-run without --dry-run to apply.');
        } else {
            $this->comment('Run `php artisan shop:audit-product-images` to verify missing files.');
        }

        return self::SUCCESS;
    }

    /**
     * @return iterable<int, string>
     */
    protected function filesIn(string $root): iterable
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                yield $file->getPathname();
            }
        }
    }
}
