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
        Schema::create('competitor_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('competitor_id')->constrained()->onDelete('cascade');
            $table->enum('activity_type', ['product_launch', 'pricing_change', 'marketing_campaign', 'content', 'social_media', 'other'])->default('other');
            $table->string('title');
            $table->text('description');
            $table->string('source_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('detected_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'competitor_id']);
            $table->index('detected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitor_activities');
    }
};
