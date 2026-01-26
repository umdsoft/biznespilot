<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Global Trends - Hybrid Intelligence Engine
 *
 * Stores search trends data globally (per region/niche).
 * "Fetch Once, Serve Many" architecture - data cached for 7 days.
 *
 * Sources: Google Trends, TikTok Trends, etc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_trends', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Categorization
            $table->string('niche', 100)->index()->comment('Business niche: business, fashion, food, etc.');
            $table->string('region_code', 10)->default('UZ')->index()->comment('ISO region code');
            $table->string('platform', 50)->index()->comment('Data source: google, tiktok, instagram');
            $table->date('trend_date')->index()->comment('Date of the trend data');

            // Trend Data
            $table->json('data_json')->nullable()->comment('Keywords, topics, search volumes');
            $table->json('top_keywords')->nullable()->comment('Top 10 trending keywords');
            $table->json('rising_keywords')->nullable()->comment('Rising/breakout keywords');
            $table->integer('total_keywords')->default(0);

            // Metadata
            $table->string('language', 10)->default('uz');
            $table->string('data_source', 100)->nullable()->comment('API source: dataforseo, serpapi');
            $table->decimal('api_cost', 8, 4)->default(0)->comment('Cost of API call in USD');

            // Status
            $table->boolean('is_processed')->default(false);
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('expires_at')->nullable()->comment('Cache expiration');

            $table->timestamps();

            // Unique constraint to prevent duplicates
            $table->unique(['niche', 'region_code', 'platform', 'trend_date'], 'unique_trend_entry');

            // Indexes for efficient queries
            $table->index(['region_code', 'niche', 'fetched_at']);
            $table->index(['platform', 'trend_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_trends');
    }
};
