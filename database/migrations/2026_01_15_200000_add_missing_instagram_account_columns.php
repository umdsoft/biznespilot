<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instagram_accounts', function (Blueprint $table) {
            // Add missing columns for InstagramSyncService
            if (! Schema::hasColumn('instagram_accounts', 'biography')) {
                $table->text('biography')->nullable()->after('name');
            }
            if (! Schema::hasColumn('instagram_accounts', 'website')) {
                $table->string('website')->nullable()->after('biography');
            }
            if (! Schema::hasColumn('instagram_accounts', 'follows_count')) {
                $table->integer('follows_count')->default(0)->after('followers_count');
            }
            if (! Schema::hasColumn('instagram_accounts', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('media_count');
            }
            if (! Schema::hasColumn('instagram_accounts', 'metadata')) {
                $table->json('metadata')->nullable()->after('last_sync_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('instagram_accounts', function (Blueprint $table) {
            $columns = ['biography', 'website', 'follows_count', 'is_primary', 'metadata'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('instagram_accounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
