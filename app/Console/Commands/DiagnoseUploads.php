<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;

class DiagnoseUploads extends Command
{
    protected $signature = 'shop:diagnose-uploads';

    protected $description = 'Report Livewire/Filament upload disk paths and writability (Runflare multi-pod)';

    public function handle(): int
    {
        $this->info('Upload diagnostics');
        $this->newLine();

        $publicDisk = Storage::disk('public');
        $tempDiskName = config('livewire.temporary_file_upload.disk', 'public');
        $tempDisk = Storage::disk($tempDiskName);
        $tempDirectory = FileUploadConfiguration::directory();

        $rows = [
            ['public disk root', (string) config('filesystems.disks.public.root')],
            ['public disk url', (string) config('filesystems.disks.public.url')],
            ['livewire temp disk', $tempDiskName],
            ['livewire temp root', (string) config("filesystems.disks.{$tempDiskName}.root")],
            ['livewire temp directory', $tempDirectory],
            ['livewire temp full path', $tempDisk->path($tempDirectory)],
            ['override loaded', $this->overridePath()],
        ];

        $this->table(['Setting', 'Value'], $rows);

        $this->newLine();
        $this->line('Writable checks:');
        $this->line('  public/products: '.($this->checkWritable($publicDisk, 'products') ? 'OK' : 'FAIL'));
        $this->line('  livewire temp: '.($this->checkWritable($tempDisk, $tempDirectory) ? 'OK' : 'FAIL'));
        $this->describeDirectoryPermissions($tempDisk->path($tempDirectory), 'livewire temp');
        $this->describeDirectoryPermissions($publicDisk->path('products'), 'public/products');

        $testFile = $tempDirectory.'/diagnose-'.uniqid('', true).'.txt';
        try {
            $tempDisk->put($testFile, 'ok');
            $exists = $tempDisk->exists($testFile);
            $tempDisk->delete($testFile);
            $this->line('  temp write/read: '.($exists ? 'OK' : 'FAIL'));
        } catch (\Throwable $exception) {
            $this->error('  temp write/read: FAIL — '.$exception->getMessage());
        }

        $this->newLine();
        $this->line('Sample public URL:');
        $this->line('  '.Storage::disk('public')->url('products/example.jpg'));

        $symlink = public_path('storage');
        $this->newLine();
        $this->line('public/storage symlink: '.(is_link($symlink) ? 'OK → '.readlink($symlink) : (is_dir($symlink) ? 'directory (not symlink)' : 'missing (OK on Runflare /data)')));

        $this->newLine();
        $this->line('Session / multi-pod:');
        $this->line('  SESSION_DRIVER: '.config('session.driver'));
        $this->line('  CACHE_STORE: '.config('cache.default'));
        $this->line('  APP_URL: '.config('app.url'));
        $this->line('  APP_ENV: '.config('app.env'));
        $this->line('  APP_DEBUG: '.(config('app.debug') ? 'true' : 'false'));
        $this->line('  SESSION_SECURE_COOKIE: '.(config('session.secure') ? 'true' : 'false'));
        $this->line('  livewire upload URL: '.url('/livewire/upload-file'));

        if (config('app.env') !== 'production') {
            $this->warn('  Set APP_ENV=production on Runflare.');
        }

        if (config('app.debug')) {
            $this->warn('  Set APP_DEBUG=false on Runflare.');
        }

        if (str_starts_with((string) config('app.url'), 'https://')
            && ! str_starts_with((string) config('filesystems.disks.public.url'), 'https://'.parse_url((string) config('app.url'), PHP_URL_HOST))) {
            $this->warn('  FILESYSTEM_PUBLIC_URL should use the same domain as APP_URL (e.g. https://sarayechini.ir/data).');
        }

        if (config('session.driver') === 'database') {
            $this->line('  Session note: database driver is OK for multi-pod when DB is shared.');
        }

        try {
            \Illuminate\Support\Facades\Redis::connection()->ping();
            $this->line('  Redis: OK');
        } catch (\Throwable $exception) {
            $this->line('  Redis: not available ('.$exception->getMessage().')');
            if (config('session.driver') === 'file') {
                $this->warn('  SESSION_DRIVER=file breaks Livewire uploads on multi-pod. Use database or redis.');
            }
        }

        $tempFiles = 0;
        try {
            $tempFiles = count($tempDisk->files($tempDirectory));
        } catch (\Throwable) {
            // ignore
        }
        $this->line('  livewire temp files on disk: '.$tempFiles);

        $this->newLine();
        $this->line('PHP limits:');
        $uploadTmpDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
        $this->line('  upload_tmp_dir: '.$uploadTmpDir);
        $this->line('  upload_tmp writable: '.(is_writable($uploadTmpDir) ? 'OK' : 'FAIL'));
        $this->line('  upload_max_filesize: '.ini_get('upload_max_filesize'));
        $this->line('  post_max_size: '.ini_get('post_max_size'));
        $this->line('  max_file_uploads: '.ini_get('max_file_uploads'));

        $this->newLine();
        $this->line('Recent upload log lines:');
        $logPath = storage_path('logs/laravel.log');
        if (is_file($logPath)) {
            $lines = collect(file($logPath, FILE_IGNORE_NEW_LINES))
                ->filter(fn (string $line) => str_contains($line, 'livewire.upload'))
                ->take(-5)
                ->values();
            if ($lines->isEmpty()) {
                $this->line('  (none yet — try an admin upload, then rerun this command)');
            } else {
                foreach ($lines as $line) {
                    $this->line('  '.$line);
                }
            }
        } else {
            $this->line('  log file not found');
        }

        $this->newLine();
        $this->comment('Runflare: FILESYSTEM_PUBLIC_ROOT and LIVEWIRE_TEMP_ROOT must be on the same persistent volume (e.g. /data).');
        $this->comment('After each deploy run: php artisan shop:fix-storage-permissions (as root in deploy hook).');
        $this->comment('Image URLs must use Storage::disk(\'public\')->url() — not hardcoded /storage paths.');
        $this->comment('If upload shows "انتخاب تصویر الزامی است" after selecting a file: wait for upload progress to finish, then save.');
        $this->comment('Check browser Network tab for POST /livewire/upload-file (419/413/500 = config or proxy issue).');
        $this->comment('If diagnose shows writable OK but uploads fail: artisan runs as root; PHP-FPM may be www-data. Run: chown -R www-data:www-data /data/livewire-tmp /data/products');
        $this->comment('After deploy, grep livewire.upload.incoming in laravel.log — if missing, the browser never reached Laravel.');
        $this->comment('Behind Runflare/custom domain, APP_URL must be https://your-domain and assets must not load over http.');

        return self::SUCCESS;
    }

    protected function overridePath(): string
    {
        $ref = new \ReflectionClass(\Livewire\Features\SupportFileUploads\TemporaryUploadedFile::class);

        return $ref->getFileName();
    }

    protected function checkWritable($disk, string $path): bool
    {
        try {
            $disk->makeDirectory($path);

            return is_writable($disk->path($path));
        } catch (\Throwable) {
            return false;
        }
    }

    protected function describeDirectoryPermissions(string $path, string $label): void
    {
        if (! is_dir($path)) {
            $this->line("  {$label} permissions: directory missing");

            return;
        }

        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $owner = function_exists('posix_getpwuid')
            ? (posix_getpwuid(fileowner($path))['name'] ?? (string) fileowner($path))
            : (string) fileowner($path);
        $group = function_exists('posix_getgrgid')
            ? (posix_getgrgid(filegroup($path))['name'] ?? (string) filegroup($path))
            : (string) filegroup($path);
        $phpUser = function_exists('posix_getpwuid')
            ? (posix_getpwuid(posix_geteuid())['name'] ?? get_current_user())
            : get_current_user();

        $this->line("  {$label} permissions: {$perms} owner={$owner} group={$group} (running as {$phpUser})");

        if (! is_writable($path)) {
            $this->warn("  {$label} is NOT writable by {$phpUser}. Fix: chown -R www-data:www-data {$path}");
        }
    }
}
