<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_ad_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ad_account_id');
            $table->uuid('campaign_id');
            $table->string('meta_campaign_id')->nullable();
            $table->uuid('business_id');
            $table->string('meta_adset_id')->unique();
            $table->string('name');
            $table->string('status')->nullable();
            $table->string('effective_status')->nullable();
            $table->string('optimization_goal')->nullable();
            $table->string('billing_event')->nullable();
            $table->string('bid_strategy')->nullable();
            $table->decimal('daily_budget', 12, 2)->nullable();
            $table->decimal('lifetime_budget', 12, 2)->nullable();
            $table->decimal('bid_amount', 12, 4)->nullable();
            $table->json('targeting')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
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
            $table->index('campaign_id');
            $table->index('business_id');
            $table->index('effective_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_ad_sets');
    }
};
