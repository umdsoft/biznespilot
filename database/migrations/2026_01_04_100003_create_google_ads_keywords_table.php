<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_ads_keywords', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ad_group_id');
            $table->foreign('ad_group_id')->references('id')->on('google_ads_ad_groups')->onDelete('cascade');
            $table->uuid('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->string('google_criterion_id')->nullable();
            $table->text('keyword_text');
            $table->string('match_type')->default('BROAD'); // EXACT, PHRASE, BROAD
            $table->string('status')->default('PAUSED'); // ENABLED, PAUSED, REMOVED
            $table->decimal('cpc_bid', 15, 2)->nullable();
            $table->decimal('quality_score', 5, 2)->nullable();
            $table->string('expected_ctr')->nullable();
            $table->string('ad_relevance')->nullable();
            $table->string('landing_page_experience')->nullable();

            // Metrics
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->bigInteger('total_impressions')->default(0);
            $table->bigInteger('total_clicks')->default(0);
            $table->integer('total_conversions')->default(0);
            $table->decimal('avg_cpc', 10, 4)->default(0);
            $table->decimal('avg_position', 5, 2)->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['ad_group_id', 'google_criterion_id']);
            $table->index('business_id');
            $table->index('match_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_ads_keywords');
    }
};
