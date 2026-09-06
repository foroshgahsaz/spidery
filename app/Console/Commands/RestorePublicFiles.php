<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeSlider;
use App\Models\MediaFile;
use App\Models\Post;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Services\Media\MediaRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class RestorePublicFiles extends Command
{
    protected $signature = 'shop:restore-public-files
                            {--dry-run : Show what would be restored}
                            {--force : Overwrite existing files on public disk}';

    protected $description = 'Copy missing DB media paths from storage/app/public into the public disk (/data)';

    public function handle(MediaRegistry $registry): int
    {
        $disk = Storage::disk('public');
        $publicRoot = rtrim((string) config('filesystems.disks.public.root'), '/');
        $legacyRoot = rtrim(storage_path('app/public'), '/');
        $dryRun = (bool) $this->option('dry-run');

        if ($publicRoot === $legacyRoot) {
            $this->comment('Public disk and legacy storage are the same path. Nothing to restore.');

            return self::SUCCESS;
        }

        $paths = $this->collectReferencedPaths();
        $restored = 0;
        $missingEverywhere = 0;
        $alreadyOk = 0;

        $this->info('Restore public files');
        $this->table(['Path', 'Location'], [
            ['public disk', $publicRoot],
            ['legacy source', $legacyRoot],
        ]);

        foreach ($paths as $path) {
            $target = $publicRoot.'/'.ltrim($path, '/');

            if (is_file($target) && ! $this->option('force')) {
                $alreadyOk++;

                continue;
            }

            $legacyFile = $legacyRoot.'/'.ltrim($path, '/');

            if (! is_file($legacyFile)) {
                $missingEverywhere++;

                continue;
            }

            if ($dryRun) {
                $this->line("[dry-run] restore {$path}");
                $restored++;

                continue;
            }

            $target = $publicRoot.'/'.ltrim($path, '/');
            File::ensureDirectoryExists(dirname($target));

            if (! @copy($legacyFile, $target)) {
                $this->error("Failed to copy: {$path}");

                return self::FAILURE;
            }

            $registry->registerFromPath('public', $path);
            $restored++;
            $this->line("Restored: {$path}");
        }

        $this->newLine();
        $this->info("Restored: {$restored}");
        $this->line("Already on public disk: {$alreadyOk}");
        $this->line("Missing everywhere: {$missingEverywhere}");

        if ($missingEverywhere > 0) {
            $this->warn('Some DB paths have no file on disk anywhere — re-upload those in admin.');
        }

        if (! $dryRun && $restored > 0) {
            $this->comment('Run: php artisan shop:fix-storage-permissions');
            $this->comment('Run: php artisan shop:import-existing-media');
        }

        return self::SUCCESS;
    }

    /** @return list<string> */
    protected function collectReferencedPaths(): array
    {
        $paths = collect();

        ProductImage::query()->whereNotNull('image')->pluck('image')->each(fn ($p) => $paths->push($p));
        Category::query()->whereNotNull('image')->pluck('image')->each(fn ($p) => $paths->push($p));
        HomeSlider::query()->whereNotNull('image')->pluck('image')->each(fn ($p) => $paths->push($p));
        Post::query()->whereNotNull('image')->pluck('image')->each(fn ($p) => $paths->push($p));
        Brand::query()->whereNotNull('logo')->pluck('logo')->each(fn ($p) => $paths->push($p));
        MediaFile::query()->pluck('path')->each(fn ($p) => $paths->push($p));

        Setting::query()
            ->where('group', 'site')
            ->whereIn('key', ['logo', 'favicon'])
            ->pluck('value')
            ->each(fn ($p) => $paths->push($p));

        return $paths
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
