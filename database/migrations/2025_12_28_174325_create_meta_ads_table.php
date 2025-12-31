<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_ads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ad_account_id');
            $table->uuid('adset_id')->nullable();
            $table->uuid('campaign_id');
            $table->string('meta_adset_id')->nullable();
            $table->string('meta_campaign_id')->nullable();
            $table->uuid('business_id');
            $table->string('meta_ad_id')->unique();
            $table->string('name');
            $table->string('status')->nullable();
            $table->string('effective_status')->nullable();
            $table->string('creative_id')->nullable();
            $table->json('creative_data')->nullable();
            $table->string('creative_thumbnail_url', 1024)->nullable();
            $table->text('creative_body')->nullable();
            $table->string('creative_title')->nullable();
            $table->string('creative_link_url', 1024)->nullable();
            $table->string('creative_call_to_action')->nullable();
            $table->json('metadata')->nullable();
            // Aggregated metrics
            $table->decimal('total_spend', 12, 2)->default(0);
            $table->bigInteger('total_impressions')->default(0);
            $table->bigInteger('total_reach')->default(0);
            $table->bigInteger('total_clicks')->default(0);
            $table->bigInteger('total_conversions')->default(0);
            $table->decimal('avg_cpc', 12, 4)->default(0);
            $table->decimal('avg_cpm', 12, 4)->default(0);
            $table->decimal('avg_ctr', 12, 4)->default(0);
            $table->timestamps();

            $table->index('ad_account_id');
            $table->index('adset_id');
            $table->index('campaign_id');
            $table->index('business_id');
            $table->index('effective_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_ads');
    }
};
