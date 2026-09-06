<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use App\Services\Media\MediaRegistry;
use App\Support\ProductPlaceholder;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class RepairProductImages extends Command
{
    protected $signature = 'shop:repair-product-images
                            {--dry-run : Show planned changes only}
                            {--placeholders : Create placeholder files for rows that still have no file}';

    protected $description = 'Relink broken product_images DB paths to orphan files already on the public disk';

    public function handle(MediaRegistry $registry): int
    {
        $disk = Storage::disk('public');
        $dryRun = (bool) $this->option('dry-run');

        $referenced = ProductImage::query()
            ->whereNotNull('image')
            ->pluck('image')
            ->filter()
            ->values();

        $orphans = collect($disk->allFiles('products'))
            ->filter(fn (string $path) => ! $referenced->contains($path))
            ->sort()
            ->values();

        $broken = ProductImage::query()
            ->whereNotNull('image')
            ->orderBy('product_id')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->filter(fn (ProductImage $image) => ! $disk->exists((string) $image->image))
            ->values();

        $this->info('Product image repair');
        $this->line('  Orphan files on disk: '.$orphans->count());
        $this->line('  Broken DB rows: '.$broken->count());

        if ($broken->isEmpty()) {
            $this->info('Nothing to repair.');

            return self::SUCCESS;
        }

        $repaired = 0;
        $rows = $this->tableRows($broken, $orphans, $disk);

        foreach ($rows as $row) {
            if ($row['new_path'] === null) {
                continue;
            }

            if ($dryRun) {
                $this->line("[dry-run] #{$row['id']} product {$row['product_id']}: {$row['old_path']} → {$row['new_path']}");
                $repaired++;

                continue;
            }

            $image = ProductImage::query()->find($row['id']);

            if ($image === null) {
                continue;
            }

            $image->update(['image' => $row['new_path']]);
            $registry->syncModel($image->fresh(), ['image']);
            $repaired++;
            $this->line("Updated #{$image->id} → {$row['new_path']}");
        }

        $remaining = $broken->count() - $repaired;

        if ($remaining > 0 && $this->option('placeholders')) {
            $remaining -= $this->createPlaceholders($broken, $disk, $dryRun);
        }

        $this->newLine();
        $this->info($dryRun
            ? "Dry run complete. {$repaired} row(s) would be updated."
            : "Repaired {$repaired} row(s).");

        if ($remaining > 0) {
            $this->warn("{$remaining} row(s) still missing a file. Re-upload in admin or run with --placeholders.");
        }

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, ProductImage>  $broken
     * @param  Collection<int, string>  $orphans
     * @return array<int, array{id:int, product_id:int, old_path:string, new_path:?string}>
     */
    protected function tableRows(Collection $broken, Collection $orphans, $disk): array
    {
        $rows = [];
        $pool = $orphans->values();

        foreach ($broken->groupBy('product_id') as $productId => $productRows) {
            $assigned = $this->assignForProduct($productRows->values(), $pool, $disk);
            $rows = [...$rows, ...$assigned];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, ProductImage>  $rows
     * @param  Collection<int, string>  $pool
     * @return array<int, array{id:int, product_id:int, old_path:string, new_path:?string}>
     */
    protected function assignForProduct(Collection $rows, Collection $pool, $disk): array
    {
        $planned = [];

        foreach ($rows as $row) {
            $newPath = $pool->shift();

            $planned[] = [
                'id' => (int) $row->id,
                'product_id' => (int) $row->product_id,
                'old_path' => (string) $row->image,
                'new_path' => $newPath,
            ];
        }

        return $planned;
    }

  /**
     * @param  Collection<int, ProductImage>  $broken
     */
    protected function createPlaceholders(Collection $broken, $disk, bool $dryRun): int
    {
        $created = 0;

        foreach ($broken as $image) {
            if ($disk->exists((string) $image->image)) {
                continue;
            }

            if ($dryRun) {
                $this->line("[dry-run] placeholder for #{$image->id}: {$image->image}");
                $created++;

                continue;
            }

            ProductPlaceholder::ensure((string) $image->image, 'محصول');
            $created++;
        }

        return $created;
    }
}
