<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->string('alt_text')->nullable()->after('original_name');
            $table->string('title')->nullable()->after('alt_text');
        });
    }

    public function down(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->dropColumn(['alt_text', 'title']);
        });
    }
};
