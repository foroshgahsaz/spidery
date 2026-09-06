<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use App\Support\ProductPlaceholder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class EnsureProductImages extends Command
{
    protected $signature = 'shop:ensure-product-images';

    protected $description = 'Create placeholder files for product images missing on disk';

    public function handle(): int
    {
        $count = 0;
        $disk = Storage::disk('public');

        ProductImage::query()
            ->select(['id', 'image'])
            ->orderBy('id')
            ->each(function (ProductImage $image) use (&$count, $disk): void {
                if (blank($image->image)) {
                    return;
                }

                $needsReplace = ! $disk->exists($image->image);

                if (! $needsReplace) {
                    try {
                        $needsReplace = $disk->size($image->image) < 512;
                    } catch (\Throwable) {
                        $needsReplace = true;
                    }
                }

                if (! $needsReplace) {
                    return;
                }

                ProductPlaceholder::ensure($image->image, 'محصول');
                $count++;
            });

        $this->info("Created {$count} missing product image file(s).");

        return self::SUCCESS;
    }
}
