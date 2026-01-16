<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add missing columns to instagram_media table
        Schema::table('instagram_media', function (Blueprint $table) {
            if (!Schema::hasColumn('instagram_media', 'media_product_type')) {
                $table->string('media_product_type', 50)->nullable()->after('media_type');
            }
            if (!Schema::hasColumn('instagram_media', 'plays')) {
                $table->integer('plays')->default(0)->after('shares');
            }
        });

        // Add missing columns to instagram_accounts table
        Schema::table('instagram_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('instagram_accounts', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_primary');
            }
            if (!Schema::hasColumn('instagram_accounts', 'access_token')) {
                $table->text('access_token')->nullable()->after('metadata');
            }
            if (!Schema::hasColumn('instagram_accounts', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('last_sync_at');
            }
            if (!Schema::hasColumn('instagram_accounts', 'disconnected_at')) {
                $table->timestamp('disconnected_at')->nullable()->after('last_synced_at');
            }
            if (!Schema::hasColumn('instagram_accounts', 'posts_count')) {
                $table->integer('posts_count')->default(0)->after('media_count');
            }
            if (!Schema::hasColumn('instagram_accounts', 'engagement_rate')) {
                $table->decimal('engagement_rate', 8, 4)->default(0)->after('posts_count');
            }
        });

        // Copy media_type to media_product_type for existing records
        // This ensures backward compatibility
        \DB::statement("
            UPDATE instagram_media
            SET media_product_type = CASE
                WHEN media_type = 'VIDEO' THEN 'REELS'
                WHEN media_type = 'IMAGE' THEN 'FEED'
                WHEN media_type = 'CAROUSEL_ALBUM' THEN 'CAROUSEL_ALBUM'
                ELSE media_type
            END
            WHERE media_product_type IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('instagram_media', function (Blueprint $table) {
            $table->dropColumn(['media_product_type', 'plays']);
        });

        Schema::table('instagram_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'access_token',
                'last_synced_at',
                'disconnected_at',
                'posts_count',
                'engagement_rate',
            ]);
        });
    }
};
