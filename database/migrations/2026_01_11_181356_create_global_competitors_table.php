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
        // Global competitors - shared across all businesses
        Schema::create('global_competitors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('description')->nullable();
            $table->string('industry')->nullable();
            $table->unsignedBigInteger('industry_id')->nullable();

            // Location - for unique identification
            $table->string('region')->nullable(); // Viloyat
            $table->string('district')->nullable(); // Tuman/Shahar
            $table->string('address')->nullable();

            // Social media handles
            $table->string('instagram_handle')->nullable();
            $table->string('telegram_handle')->nullable();
            $table->string('facebook_page')->nullable();
            $table->string('tiktok_handle')->nullable();
            $table->string('youtube_channel')->nullable();

            // Global SWOT data - aggregated from all businesses
            $table->json('swot_data')->nullable();
            $table->timestamp('swot_updated_at')->nullable();
            $table->unsignedInteger('swot_contributors_count')->default(0);

            // Verification & quality
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->unsignedInteger('report_count')->default(0);

            $table->timestamps();

            // Indexes for searching
            $table->index(['name', 'region', 'district']);
            $table->index('instagram_handle');
            $table->index('telegram_handle');
        });

        // Add global_competitor_id to existing competitors table
        Schema::table('competitors', function (Blueprint $table) {
            $table->foreignId('global_competitor_id')->nullable()->after('business_id')
                ->constrained('global_competitors')->nullOnDelete();
            $table->string('region')->nullable()->after('location');
            $table->string('district')->nullable()->after('region');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitors', function (Blueprint $table) {
            $table->dropForeign(['global_competitor_id']);
            $table->dropColumn(['global_competitor_id', 'region', 'district']);
        });

        Schema::dropIfExists('global_competitors');
    }
};
