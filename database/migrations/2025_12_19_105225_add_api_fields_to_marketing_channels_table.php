<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('marketing_channels', function (Blueprint $table) {
            // Change type enum to include platform-specific types
            $table->dropColumn('type');
        });

        Schema::table('marketing_channels', function (Blueprint $table) {
            $table->enum('type', [
                'instagram',
                'telegram',
                'facebook',
                'google_ads',
                'email',
                'seo',
                'ppc',
                'content',
                'affiliate',
                'direct',
                'referral',
                'other'
            ])->default('other')->after('name');

            // Add API-related fields
            $table->string('platform_account_id')->nullable()->after('platform');
            $table->text('access_token')->nullable()->after('platform_account_id');
            $table->text('refresh_token')->nullable()->after('access_token');
            $table->timestamp('token_expires_at')->nullable()->after('refresh_token');
            $table->timestamp('last_synced_at')->nullable()->after('is_active');

            // Add index for last_synced_at for efficient querying
            $table->index('last_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketing_channels', function (Blueprint $table) {
            $table->dropIndex(['last_synced_at']);
            $table->dropColumn([
                'platform_account_id',
                'access_token',
                'refresh_token',
                'token_expires_at',
                'last_synced_at',
            ]);
            $table->dropColumn('type');
        });

        Schema::table('marketing_channels', function (Blueprint $table) {
            $table->enum('type', ['social_media', 'email', 'seo', 'ppc', 'content', 'affiliate', 'direct', 'referral', 'other'])->default('other')->after('name');
        });
    }
};
