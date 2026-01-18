<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitors', function (Blueprint $table) {
            // Social media
            $table->string('instagram_handle', 100)->nullable()->after('website');
            $table->string('telegram_channel', 100)->nullable()->after('instagram_handle');

            // Pricing
            $table->bigInteger('price_range_min')->nullable()->after('pricing');
            $table->bigInteger('price_range_max')->nullable()->after('price_range_min');

            // USP & positioning
            $table->json('unique_selling_points')->nullable()->after('price_range_max');
            $table->text('positioning_statement')->nullable()->after('unique_selling_points');
            $table->text('target_audience')->nullable()->after('positioning_statement');

            // Content analysis
            $table->string('content_frequency', 100)->nullable()->after('target_audience');
            $table->json('content_types')->nullable()->after('content_frequency');
            $table->decimal('engagement_rate_estimated', 5, 2)->nullable()->after('content_types');
            $table->integer('followers_count')->nullable()->after('engagement_rate_estimated');

            // Advertising
            $table->enum('ad_activity', ['low', 'medium', 'high'])->nullable()->after('followers_count');
            $table->json('ad_channels')->nullable()->after('ad_activity');

            // SWOT (renaming existing)
            $table->json('opportunities')->nullable()->after('weaknesses');
            $table->json('threats')->nullable()->after('opportunities');
            $table->enum('overall_threat_level', ['low', 'medium', 'high'])->nullable()->after('threats');

            // AI monitoring
            $table->boolean('ai_monitored')->default(false)->after('overall_threat_level');
            $table->timestamp('last_ai_update_at')->nullable()->after('ai_monitored');
        });
    }

    public function down(): void
    {
        Schema::table('competitors', function (Blueprint $table) {
            $table->dropColumn([
                'instagram_handle', 'telegram_channel',
                'price_range_min', 'price_range_max',
                'unique_selling_points', 'positioning_statement', 'target_audience',
                'content_frequency', 'content_types', 'engagement_rate_estimated',
                'followers_count', 'ad_activity', 'ad_channels',
                'opportunities', 'threats', 'overall_threat_level',
                'ai_monitored', 'last_ai_update_at',
            ]);
        });
    }
};
