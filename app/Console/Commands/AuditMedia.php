<?php

namespace App\Console\Commands;

use App\Models\MediaFile;
use App\Services\Media\MediaRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AuditMedia extends Command
{
    protected $signature = 'shop:audit-media {--limit=30 : Max rows per section}';

    protected $description = 'Audit media library records vs disk files and database image paths';

    public function handle(MediaRegistry $registry): int
    {
        if (! Schema::hasTable('media_files')) {
            $this->error('جدول media_files وجود ندارد. ابتدا php artisan migrate --force را اجرا کنید.');

            return self::FAILURE;
        }

        $disk = Storage::disk('public');
        $limit = (int) $this->option('limit');

        $this->info('Media audit');
        $this->table(['Setting', 'Value'], [
            ['public root', (string) config('filesystems.disks.public.root')],
            ['media_files count', (string) MediaFile::query()->count()],
        ]);

        $missingOnDisk = MediaFile::query()
            ->orderByDesc('id')
            ->get()
            ->filter(fn (MediaFile $file) => ! $file->existsOnDisk())
            ->take($limit);

        $this->newLine();
        $this->warn('Registry rows without file on disk: '.$missingOnDisk->count());

        if ($missingOnDisk->isNotEmpty()) {
            $this->table(
                ['id', 'path', 'usages'],
                $missingOnDisk->map(fn (MediaFile $f) => [
                    $f->id,
                    $f->path,
                    $f->usages()->count(),
                ])->all()
            );
        }

        $orphanPaths = [];

        foreach (array_keys(config('media-library.folders', [])) as $folder) {
            if (! $disk->exists($folder)) {
                continue;
            }

            foreach ($disk->allFiles($folder) as $path) {
                if (MediaFile::query()->where('path', $path)->exists()) {
                    continue;
                }

                $orphanPaths[] = $path;

                if (count($orphanPaths) >= $limit) {
                    break 2;
                }
            }
        }

        $this->newLine();
        $this->warn('Files on disk without registry row (sample): '.count($orphanPaths));

        foreach ($orphanPaths as $path) {
            $this->line('  '.$path.($registry->isPathInUse($path) ? ' [referenced in DB]' : ''));
        }

        $this->newLine();
        $this->comment('Import orphans: php artisan shop:import-existing-media');
        $this->comment('Prune safe orphans: php artisan shop:prune-unused-media --dry-run');

        return self::SUCCESS;
    }
}
