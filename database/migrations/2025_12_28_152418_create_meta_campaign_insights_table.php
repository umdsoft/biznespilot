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
        if (!Schema::hasTable('meta_campaign_insights')) {
            Schema::create('meta_campaign_insights', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('campaign_id'); // References meta_campaigns.id
                $table->uuid('business_id');

                // Date
                $table->date('date');

                // Core Metrics
                $table->decimal('spend', 15, 2)->default(0);
                $table->bigInteger('impressions')->default(0);
                $table->bigInteger('reach')->default(0);
                $table->bigInteger('clicks')->default(0);
                $table->decimal('cpc', 10, 4)->default(0);
                $table->decimal('cpm', 10, 4)->default(0);
                $table->decimal('ctr', 10, 4)->default(0);
                $table->decimal('frequency', 5, 2)->default(0);

                // Actions/Conversions
                $table->integer('conversions')->default(0);
                $table->integer('leads')->default(0);
                $table->integer('purchases')->default(0);
                $table->integer('add_to_cart')->default(0);
                $table->integer('link_clicks')->default(0);
                $table->integer('video_views')->default(0);

                // Cost per action
                $table->decimal('cost_per_conversion', 10, 2)->default(0);
                $table->decimal('cost_per_lead', 10, 2)->default(0);

                // Raw actions JSON
                $table->json('actions')->nullable();
                $table->json('action_values')->nullable();

                $table->timestamps();

                // Unique constraint: one row per campaign per day
                $table->unique(['campaign_id', 'date']);

                // Indexes
                $table->index(['business_id', 'date']);
                $table->index('date');

                // Foreign keys
                $table->foreign('campaign_id')->references('id')->on('meta_campaigns')->onDelete('cascade');
                $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_campaign_insights');
    }
};
