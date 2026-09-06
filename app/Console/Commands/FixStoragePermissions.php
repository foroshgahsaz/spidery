<?php

namespace App\Console\Commands;

use App\Support\StoragePermissionFixer;
use Illuminate\Console\Command;

class FixStoragePermissions extends Command
{
    protected $signature = 'shop:fix-storage-permissions';

    protected $description = 'Ensure /data upload directories exist and are writable by www-data (Runflare deploy hook)';

    public function handle(): int
    {
        StoragePermissionFixer::fix();

        foreach (StoragePermissionFixer::requiredPaths() as $path) {
            $writable = StoragePermissionFixer::isDirectoryWritable($path) ? 'writable' : 'NOT writable';
            $this->line("{$path} — {$writable}");
        }

        if (! StoragePermissionFixer::runningAsRoot()) {
            $this->warn('Run as root in deploy hook for chown (e.g. kubectl exec as root).');
        }

        return self::SUCCESS;
    }
}
