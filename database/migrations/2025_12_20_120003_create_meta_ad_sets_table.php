<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_ad_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('meta_campaigns')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('meta_adset_id');
            $table->string('name');
            $table->string('status');
            $table->string('effective_status')->nullable();
            $table->string('optimization_goal')->nullable();
            $table->string('billing_event')->nullable();
            $table->decimal('daily_budget', 15, 2)->nullable();
            $table->decimal('lifetime_budget', 15, 2)->nullable();
            $table->decimal('bid_amount', 15, 4)->nullable();
            $table->json('targeting')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'meta_adset_id']);
            $table->index('business_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_ad_sets');
    }
};
