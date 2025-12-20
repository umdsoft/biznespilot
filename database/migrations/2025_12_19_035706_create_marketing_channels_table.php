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
        Schema::create('marketing_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['social_media', 'email', 'seo', 'ppc', 'content', 'affiliate', 'direct', 'referral', 'other'])->default('other');
            $table->string('platform')->nullable(); // facebook, instagram, google_ads, etc.
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable(); // API keys, settings, etc.
            $table->json('metrics')->nullable(); // impressions, clicks, conversions, etc.
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_active']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_channels');
    }
};
