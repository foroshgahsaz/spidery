<?php

use App\Models\MediaFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('media_files')) {
            Schema::create('media_files', function (Blueprint $table) {
                $table->id();
                $table->string('disk', 32)->default('public');
                $table->string('path', 512);
                $table->char('path_hash', 64);
                $table->string('folder')->index();
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->unsignedInteger('width')->nullable();
                $table->unsignedInteger('height')->nullable();
                $table->string('original_name')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique('path_hash', 'media_files_path_hash_unique');
                $table->index('path', 'media_files_path_index');
            });
        } else {
            $this->upgradeExistingMediaFilesTable();
        }

        if (! Schema::hasTable('media_usages')) {
            Schema::create('media_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('media_file_id')->constrained('media_files')->cascadeOnDelete();
                $table->string('usable_type', 120);
                $table->unsignedBigInteger('usable_id');
                $table->string('field', 80);
                $table->timestamps();

                $table->index(['usable_type', 'usable_id'], 'media_usages_usable_index');
                $table->unique(
                    ['usable_type', 'usable_id', 'field'],
                    'media_usages_usable_field_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_usages');
        Schema::dropIfExists('media_files');
    }

    protected function upgradeExistingMediaFilesTable(): void
    {
        $this->dropIndexIfExists('media_files', 'media_files_path_unique');
        $this->dropIndexIfExists('media_files', 'media_files_disk_path_unique');

        if (Schema::hasColumn('media_files', 'path_hash')) {
            if (! $this->indexExists('media_files', 'media_files_path_hash_unique')) {
                Schema::table('media_files', function (Blueprint $table) {
                    $table->unique('path_hash', 'media_files_path_hash_unique');
                });
            }

            return;
        }

        Schema::table('media_files', function (Blueprint $table) {
            $table->char('path_hash', 64)->default('')->after('path');
        });

        DB::table('media_files')
            ->select(['id', 'disk', 'path'])
            ->orderBy('id')
            ->each(function (object $row): void {
                DB::table('media_files')
                    ->where('id', $row->id)
                    ->update([
                        'path_hash' => MediaFile::pathHash((string) $row->disk, (string) $row->path),
                    ]);
            });

        DB::statement('ALTER TABLE media_files MODIFY path_hash CHAR(64) NOT NULL');

        Schema::table('media_files', function (Blueprint $table) {
            $table->unique('path_hash', 'media_files_path_hash_unique');
        });
    }

    protected function dropIndexIfExists(string $table, string $index): void
    {
        if (! $this->indexExists($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($index) {
            $table->dropUnique($index);
        });
    }

    protected function indexExists(string $table, string $index): bool
    {
        foreach (Schema::getIndexes($table) as $definition) {
            if (($definition['name'] ?? '') === $index) {
                return true;
            }
        }

        return false;
    }
};
