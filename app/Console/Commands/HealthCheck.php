<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\HomeSlider;
use App\Models\MediaFile;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Media\DisplayImageService;
use App\Services\Media\HomepageImageService;
use App\Services\Media\ImageOptimizer;
use App\Support\MediaPath;
use App\Support\ShopFormatter;
use App\Support\ShopMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;

class HealthCheck extends Command
{
    protected $signature = 'shop:health-check
                            {--section= : uploads|storage|optimizer|media|products|homepage|code (comma-separated)}
                            {--limit=5 : Sample rows per failing section}
                            {--json : Output machine-readable JSON}';

    protected $description = 'Run a full shop health / diagnostics report (uploads, media, images, homepage thumbnails)';

    /** @var list<array{section: string, check: string, status: string, detail: string}> */
    protected array $results = [];

    protected int $failures = 0;

    protected int $warnings = 0;

    public function handle(
        ImageOptimizer $optimizer,
        HomepageImageService $homepageImages,
        DisplayImageService $displayImages,
    ): int {
        $sections = $this->resolveSections();

        if (in_array('code', $sections, true)) {
            $this->checkCode();
        }

        if (in_array('storage', $sections, true)) {
            $this->checkStorage();
        }

        if (in_array('uploads', $sections, true)) {
            $this->checkUploads();
        }

        if (in_array('optimizer', $sections, true)) {
            $this->checkOptimizer($optimizer);
        }

        if (in_array('media', $sections, true)) {
            $this->checkMediaLibrary();
        }

        if (in_array('products', $sections, true)) {
            $this->checkProductImages();
        }

        if (in_array('homepage', $sections, true)) {
            $this->checkHomepageThumbnails($homepageImages, $displayImages);
        }

        return $this->renderSummary();
    }

    /** @return list<string> */
    protected function resolveSections(): array
    {
        $default = ['code', 'storage', 'uploads', 'optimizer', 'media', 'products', 'homepage'];
        $raw = (string) $this->option('section');

        if ($raw === '') {
            return $default;
        }

        return array_values(array_intersect($default, array_map('trim', explode(',', $raw))));
    }

    protected function checkCode(): void
    {
        $this->sectionHeading('کد و کلاس‌های حیاتی');

        $classes = [
            'MediaPath' => \App\Support\MediaPath::class,
            'FileUploadStateNormalizer' => \App\Filament\Support\FileUploadStateNormalizer::class,
            'MissingUploadPathCleaner' => \App\Filament\Support\MissingUploadPathCleaner::class,
            'DisplayImageService' => DisplayImageService::class,
            'HomepageImageService' => HomepageImageService::class,
            'RepairProductImages command' => RepairProductImages::class,
        ];

        foreach ($classes as $label => $class) {
            $this->record('code', $label, class_exists($class) ? 'ok' : 'fail', class_exists($class) ? 'loaded' : 'missing — redeploy master');
        }
    }

    protected function checkStorage(): void
    {
        $this->sectionHeading('دیسک و دسترسی‌ها');

        $publicRoot = (string) config('filesystems.disks.public.root');
        $publicUrl = (string) config('filesystems.disks.public.url');
        $tempRoot = (string) config('filesystems.disks.livewire-tmp.root');

        $this->record('storage', 'public disk root', $publicRoot !== '' ? 'ok' : 'fail', $publicRoot ?: 'empty');
        $this->record('storage', 'public disk url', $publicUrl !== '' ? 'ok' : 'warn', $publicUrl ?: 'empty');

        $sameHost = $this->publicUrlMatchesAppUrl();
        $this->record(
            'storage',
            'public url host',
            $sameHost ? 'ok' : 'warn',
            $sameHost ? 'matches APP_URL' : 'FILESYSTEM_PUBLIC_URL should match site domain'
        );

        $disk = Storage::disk('public');
        foreach (['products', 'sliders', 'categories', 'posts', 'thumbs', '.thumbs'] as $folder) {
            $writable = $this->isWritable($disk, $folder);
            $this->record('storage', "writable: {$folder}", $writable ? 'ok' : 'fail', $writable ? 'OK' : 'not writable');
        }

        $legacyCount = $this->countLocalFiles(storage_path('app/public'));
        $dataCount = $this->countLocalFiles('/data');
        $publicProducts = $this->countDiskFiles($disk, 'products');

        $this->record(
            'storage',
            'legacy storage/app/public',
            is_dir(storage_path('app/public')) ? 'ok' : 'warn',
            $legacyCount.' files (run shop:sync-public-storage if needed)'
        );

        $this->record(
            'storage',
            '/data volume',
            is_dir('/data') ? 'ok' : 'warn',
            is_dir('/data') ? $dataCount.' files' : 'not mounted (local dev OK)'
        );

        if ($legacyCount > 0 && (int) $publicProducts === 0) {
            $this->record(
                'storage',
                'sync needed',
                'fail',
                'files exist in storage/app/public but /data is empty — run: php artisan shop:sync-public-storage'
            );
        }

        if ($publicRoot !== $tempRoot) {
            $this->record('storage', 'temp vs public root', 'warn', "different roots — ensure both on same persistent volume");
        } else {
            $this->record('storage', 'temp vs public root', 'ok', 'same root');
        }
    }

    protected function checkUploads(): void
    {
        $this->sectionHeading('آپلود Livewire / Filament');

        $tempDiskName = (string) config('livewire.temporary_file_upload.disk', 'public');
        $tempDisk = Storage::disk($tempDiskName);
        $tempDirectory = FileUploadConfiguration::directory();
        $tempWritable = $this->isWritable($tempDisk, $tempDirectory);

        $this->record('uploads', 'livewire temp writable', $tempWritable ? 'ok' : 'fail', $tempDisk->path($tempDirectory));

        $writeOk = false;
        $testFile = $tempDirectory.'/health-'.uniqid('', true).'.txt';

        try {
            $tempDisk->put($testFile, 'ok');
            $writeOk = $tempDisk->exists($testFile);
            $tempDisk->delete($testFile);
        } catch (\Throwable $exception) {
            $this->record('uploads', 'temp write test', 'fail', $exception->getMessage());
        }

        if ($writeOk) {
            $this->record('uploads', 'temp write test', 'ok', 'write/read OK');
        } elseif (! isset($this->results[count($this->results) - 1]) || $this->results[array_key_last($this->results)]['check'] !== 'temp write test') {
            $this->record('uploads', 'temp write test', 'fail', 'could not write temp file');
        }

        $sessionDriver = (string) config('session.driver');
        $this->record(
            'uploads',
            'session driver',
            in_array($sessionDriver, ['database', 'redis'], true) ? 'ok' : 'warn',
            $sessionDriver.' (use database/redis on multi-pod)'
        );

        $this->record(
            'uploads',
            'APP_ENV / DEBUG',
            config('app.env') === 'production' && ! config('app.debug') ? 'ok' : 'warn',
            'env='.config('app.env').' debug='.(config('app.debug') ? 'true' : 'false')
        );

        try {
            \Illuminate\Support\Facades\Redis::connection()->ping();
            $this->record('uploads', 'redis', 'ok', 'reachable');
        } catch (\Throwable $exception) {
            $this->record('uploads', 'redis', 'warn', 'not available');
        }
    }

    protected function checkOptimizer(ImageOptimizer $optimizer): void
    {
        $this->sectionHeading('بهینه‌ساز تصویر');

        $gd = extension_loaded('gd') && function_exists('imagewebp');
        $this->record('optimizer', 'GD + WebP', $gd ? 'ok' : 'fail', $gd ? 'available' : 'missing');
        $this->record(
            'optimizer',
            'enabled',
            $optimizer->isEnabled() ? 'ok' : 'warn',
            $optimizer->isEnabled() ? 'yes' : 'IMAGE_OPTIMIZER_ENABLED=false'
        );
        $this->record('optimizer', 'driver', 'ok', (string) config('image-optimizer.driver', 'gd'));
    }

    protected function checkMediaLibrary(): void
    {
        $this->sectionHeading('کتابخانه رسانه');

        if (! Schema::hasTable('media_files')) {
            $this->record('media', 'media_files table', 'fail', 'missing — run migrate');

            return;
        }

        $disk = Storage::disk('public');
        $total = MediaFile::query()->count();
        $missingOnDisk = 0;
        $limit = (int) $this->option('limit');

        MediaFile::query()->orderByDesc('id')->each(function (MediaFile $file) use (&$missingOnDisk, $limit): void {
            if ($file->existsOnDisk()) {
                return;
            }

            $missingOnDisk++;

            if ($missingOnDisk <= $limit) {
                $this->record('media', "missing file #{$file->id}", 'fail', $file->path);
            }
        });

        $this->record(
            'media',
            'registry rows',
            'ok',
            (string) $total.' total, '.$missingOnDisk.' missing on disk'
        );

        if ($missingOnDisk > 0) {
            $this->record('media', 'fix hint', 'warn', 'shop:audit-media — re-upload or shop:import-existing-media');
        }
    }

    protected function checkProductImages(): void
    {
        $this->sectionHeading('تصاویر محصولات');

        $disk = Storage::disk('public');
        $total = ProductImage::query()->whereNotNull('image')->count();
        $missing = 0;
        $limit = (int) $this->option('limit');

        ProductImage::query()
            ->whereNotNull('image')
            ->orderByDesc('id')
            ->each(function (ProductImage $image) use ($disk, &$missing, $limit): void {
                if ($disk->exists($image->image)) {
                    return;
                }

                $missing++;

                if ($missing <= $limit) {
                    $alt = is_file(storage_path('app/public/'.ltrim($image->image, '/'))) ? ' (in storage/app/public)' : '';
                    $this->record('products', "product_image #{$image->id}", 'fail', $image->image.$alt);
                }
            });

        $this->record('products', 'summary', $missing === 0 ? 'ok' : 'fail', "{$missing} missing of {$total} DB rows");

        if ($missing > 0) {
            $this->record('products', 'fix hint', 'warn', 'shop:repair-product-images --dry-run then shop:repair-product-images');
        }
    }

    protected function checkHomepageThumbnails(HomepageImageService $homepageImages, DisplayImageService $displayImages): void
    {
        $this->sectionHeading('تامبنیل صفحه اصلی');

        $samples = [
            'hero' => fn () => HomeSlider::query()->whereNotNull('image')->value('image'),
            'categories' => fn () => Category::query()->whereNotNull('image')->value('image'),
            'deals' => fn () => $this->productImagePath(
                Product::query()->where('sale_price', '>', 0)->whereHas('images')->first()
            ),
            'new_products' => fn () => $this->productImagePath(
                Product::query()->latest('id')->whereHas('images')->first()
            ),
            'best_sellers' => fn () => $this->productImagePath(
                Product::query()->whereHas('images')->latest('id')->first()
            ),
            'blog' => fn () => Post::query()->whereNotNull('image')->value('image'),
        ];

        foreach ($homepageImages->definitions() as $key => $definition) {
            $preset = $homepageImages->forSection($key) ?? [];
            $enabled = (bool) ($preset['enabled'] ?? true);
            $label = (string) ($definition['label'] ?? $key);
            $path = isset($samples[$key]) ? rescue(fn () => ($samples[$key])(), report: false) : null;

            if (! $enabled) {
                $this->record('homepage', $label, 'warn', 'preset disabled — using full image');

                continue;
            }

            if (! is_string($path) || $path === '') {
                $this->record('homepage', $label, 'warn', 'no sample record in database');

                continue;
            }

            if (Str::startsWith($path, ['http://', 'https://', 'shop/'])) {
                $this->record('homepage', $label, 'ok', 'external/legacy path');

                continue;
            }

            $normalized = MediaPath::normalize($path);
            $exists = $normalized !== null && Storage::disk('public')->exists($normalized);

            if (! $exists) {
                $this->record('homepage', $label, 'fail', "file missing: {$path}");

                continue;
            }

            $thumbUrl = $displayImages->url($key, $normalized);
            $fullUrl = ShopMedia::url($normalized);

            $this->record(
                'homepage',
                $label,
                $thumbUrl ? 'ok' : 'warn',
                ($thumbUrl ? 'thumb: '.$thumbUrl : 'no thumb url').' | full: '.($fullUrl ?? '—')
            );
        }
    }

    protected function productImagePath(?Product $product): ?string
    {
        return $product ? ShopFormatter::productImagePath($product) : null;
    }

    protected function renderSummary(): int
    {
        if ($this->option('json')) {
            $this->line(json_encode([
                'failures' => $this->failures,
                'warnings' => $this->warnings,
                'results' => $this->results,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return $this->failures > 0 ? self::FAILURE : self::SUCCESS;
        }

        $this->newLine();
        $this->info('خلاصه گزارش سلامت فروشگاه');
        $this->table(['بخش', 'بررسی', 'وضعیت', 'جزئیات'], array_map(
            fn (array $row) => [$row['section'], $row['check'], $this->statusLabel($row['status']), $row['detail']],
            $this->results
        ));

        $this->newLine();
        $this->line("خطا: {$this->failures} | هشدار: {$this->warnings}");

        $this->newLine();
        $this->comment('دستورات تکمیلی:');
        $this->line('  php artisan shop:diagnose-uploads');
        $this->line('  php artisan shop:audit-media');
        $this->line('  php artisan shop:audit-product-images');
        $this->line('  php artisan shop:diagnose-image-optimizer sliders/example.jpg');
        $this->line('  php artisan test');

        if ($this->failures > 0) {
            $this->error('برخی بررسی‌ها ناموفق بودند.');

            return self::FAILURE;
        }

        $this->info('همه بررسی‌های حیاتی OK هستند (هشدارها را در صورت نیاز بررسی کنید).');

        return self::SUCCESS;
    }

    protected function sectionHeading(string $title): void
    {
        if (! $this->option('json')) {
            $this->newLine();
            $this->info($title);
        }
    }

    protected function record(string $section, string $check, string $status, string $detail): void
    {
        $this->results[] = [
            'section' => $section,
            'check' => $check,
            'status' => $status,
            'detail' => $detail,
        ];

        if ($status === 'fail') {
            $this->failures++;
        } elseif ($status === 'warn') {
            $this->warnings++;
        }
    }

    protected function statusLabel(string $status): string
    {
        return match ($status) {
            'ok' => '✓ OK',
            'warn' => '⚠ WARN',
            default => '✗ FAIL',
        };
    }

    protected function isWritable($disk, string $path): bool
    {
        try {
            $disk->makeDirectory($path);

            return is_writable($disk->path($path));
        } catch (\Throwable) {
            return false;
        }
    }

    protected function publicUrlMatchesAppUrl(): bool
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $publicHost = parse_url((string) config('filesystems.disks.public.url'), PHP_URL_HOST);

        return is_string($appHost) && is_string($publicHost) && $appHost === $publicHost;
    }

    protected function countLocalFiles(string $directory): int
    {
        if (! is_dir($directory)) {
            return 0;
        }

        $count = 0;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $count++;
                }
            }
        } catch (\Throwable) {
            return 0;
        }

        return $count;
    }

    protected function countDiskFiles($disk, string $directory): int
    {
        try {
            return count($disk->allFiles($directory));
        } catch (\Throwable) {
            return 0;
        }
    }
}
