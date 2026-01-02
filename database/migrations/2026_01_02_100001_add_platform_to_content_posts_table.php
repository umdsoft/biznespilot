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
        Schema::table('content_posts', function (Blueprint $table) {
            $table->string('platform', 50)->nullable()->after('channel_id');
            $table->uuid('user_id')->nullable()->after('business_id');
            $table->string('external_id')->nullable()->after('shares');
            $table->string('external_url')->nullable()->after('external_id');
            $table->json('ai_suggestions')->nullable()->after('external_url');
            $table->json('metrics')->nullable()->after('ai_suggestions');

            // Index for platform
            $table->index('platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_posts', function (Blueprint $table) {
            $table->dropIndex(['platform']);
            $table->dropColumn(['platform', 'user_id', 'external_id', 'external_url', 'ai_suggestions', 'metrics']);
        });
    }
};
