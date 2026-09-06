<?php

namespace App\Console\Commands;

use App\Models\MediaFile;
use App\Services\Media\MediaRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PruneUnusedMedia extends Command
{
    protected $signature = 'shop:prune-unused-media {--dry-run : List files without deleting}';

    protected $description = 'Delete media files that are not used anywhere (registry + content tables)';

    public function handle(MediaRegistry $registry): int
    {
        if (! Schema::hasTable('media_files') || ! Schema::hasTable('media_usages')) {
            $this->error('جداول media_files یا media_usages وجود ندارد. ابتدا php artisan migrate --force را اجرا کنید.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $deleted = 0;

        MediaFile::query()
            ->withCount('usages')
            ->orderBy('id')
            ->chunkById(100, function ($files) use ($registry, $dryRun, &$deleted): void {
                foreach ($files as $file) {
                    if ($file->usages_count > 0) {
                        continue;
                    }

                    if ($registry->isPathInUse($file->path, $file->disk)) {
                        continue;
                    }

                    if ($dryRun) {
                        $this->line('[dry-run] would delete: '.$file->path);

                        continue;
                    }

                    if (Storage::disk($file->disk)->exists($file->path)) {
                        Storage::disk($file->disk)->delete($file->path);
                    }

                    $file->delete();
                    $deleted++;
                }
            });

        $this->info($dryRun
            ? 'Dry run complete. Re-run without --dry-run to delete.'
            : "Deleted {$deleted} unused media file(s).");

        return self::SUCCESS;
    }
}
