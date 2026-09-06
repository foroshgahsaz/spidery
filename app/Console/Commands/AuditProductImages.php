<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AuditProductImages extends Command
{
    protected $signature = 'shop:audit-product-images {--limit=20 : Number of missing rows to list}';

    protected $description = 'List product images whose files are missing from the public disk';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $root = (string) config('filesystems.disks.public.root');
        $url = (string) config('filesystems.disks.public.url');

        $this->info('Product image audit');
        $this->table(['Setting', 'Value'], [
            ['public disk root', $root],
            ['public disk url', $url],
            ['livewire temp root', (string) config('filesystems.disks.livewire-tmp.root')],
        ]);

        $this->newLine();
        $this->line('On-disk counts:');
        $this->line('  /data/products (if exists): '.$this->countFiles('/data/products'));
        $this->line('  storage/app/public/products: '.$this->countFiles(storage_path('app/public/products')));
        $this->line('  public disk products/: '.$this->countDiskFiles($disk, 'products'));

        $total = ProductImage::query()->whereNotNull('image')->count();
        $missing = 0;
        $rows = [];

        ProductImage::query()
            ->whereNotNull('image')
            ->orderByDesc('id')
            ->each(function (ProductImage $image) use ($disk, &$missing, &$rows): void {
                if ($disk->exists($image->image)) {
                    return;
                }

                $missing++;
                if (count($rows) < (int) $this->option('limit')) {
                    $rows[] = [
                        'id' => $image->id,
                        'product_id' => $image->product_id,
                        'path' => $image->image,
                        'alt_path_exists' => $this->alternatePathExists($image->image) ? 'yes' : 'no',
                    ];
                }
            });

        $this->newLine();
        $this->line("Database rows: {$total}");
        $this->line("Missing files: {$missing}");

        if ($rows !== []) {
            $this->newLine();
            $this->warn('Sample missing rows (alt_path_exists = found under storage/app/public instead of /data):');
            $this->table(['id', 'product_id', 'path', 'alt_path_exists'], $rows);
        }

        if ($missing > 0) {
            $this->newLine();
            $this->comment('If alt_path_exists=yes, run: php artisan config:clear');
            $this->comment('Then re-upload images. Old DB paths without files cannot be recovered automatically.');
        }

        return self::SUCCESS;
    }

    protected function countFiles(string $directory): string
    {
        if (! is_dir($directory)) {
            return 'missing dir';
        }

        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }

        return (string) $count;
    }

    protected function countDiskFiles($disk, string $directory): string
    {
        try {
            return (string) count($disk->allFiles($directory));
        } catch (\Throwable $exception) {
            return 'error: '.$exception->getMessage();
        }
    }

    protected function alternatePathExists(string $path): bool
    {
        $alternate = storage_path('app/public/'.ltrim($path, '/'));

        return is_file($alternate);
    }
}
