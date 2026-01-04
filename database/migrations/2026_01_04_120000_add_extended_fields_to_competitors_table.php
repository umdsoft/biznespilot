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
        Schema::table('competitors', function (Blueprint $table) {
            // Basic info
            $table->string('industry')->nullable()->after('description');
            $table->string('location')->nullable()->after('industry');

            // Threat & status
            $table->string('threat_level', 20)->default('medium')->after('location'); // low, medium, high, critical
            $table->string('status', 20)->default('active')->after('threat_level'); // active, inactive, archived

            // Social media handles
            $table->string('instagram_handle')->nullable()->after('status');
            $table->string('telegram_handle')->nullable()->after('instagram_handle');
            $table->string('facebook_page')->nullable()->after('telegram_handle');
            $table->string('tiktok_handle')->nullable()->after('facebook_page');
            $table->string('youtube_channel')->nullable()->after('tiktok_handle');

            // Monitoring settings
            $table->boolean('auto_monitor')->default(false)->after('youtube_channel');
            $table->integer('check_frequency_hours')->default(24)->after('auto_monitor');
            $table->timestamp('last_checked_at')->nullable()->after('check_frequency_hours');

            // Tags
            $table->json('tags')->nullable()->after('notes');

            // Indexes
            $table->index('threat_level');
            $table->index('status');
            $table->index('industry');
            $table->index('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitors', function (Blueprint $table) {
            $table->dropIndex(['threat_level']);
            $table->dropIndex(['status']);
            $table->dropIndex(['industry']);
            $table->dropIndex(['location']);

            $table->dropColumn([
                'industry',
                'location',
                'threat_level',
                'status',
                'instagram_handle',
                'telegram_handle',
                'facebook_page',
                'tiktok_handle',
                'youtube_channel',
                'auto_monitor',
                'check_frequency_hours',
                'last_checked_at',
                'tags',
            ]);
        });
    }
};
