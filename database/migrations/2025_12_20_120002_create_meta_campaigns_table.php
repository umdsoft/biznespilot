<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_account_id')->constrained('meta_ad_accounts')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('meta_campaign_id');
            $table->string('name');
            $table->string('objective')->nullable();
            $table->string('status');
            $table->string('effective_status')->nullable();
            $table->decimal('daily_budget', 15, 2)->nullable();
            $table->decimal('lifetime_budget', 15, 2)->nullable();
            $table->string('budget_remaining')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('stop_time')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['ad_account_id', 'meta_campaign_id']);
            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_campaigns');
    }
};
