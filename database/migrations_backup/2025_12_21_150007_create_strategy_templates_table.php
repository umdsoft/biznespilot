<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strategy_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Template identification
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Type and category
            $table->enum('type', ['annual', 'quarterly', 'monthly', 'weekly', 'content']);
            $table->string('industry')->nullable(); // For industry-specific templates
            $table->enum('business_size', ['micro', 'small', 'medium', 'large'])->nullable();

            // Template content
            $table->json('goals_template')->nullable();
            $table->json('kpis_template')->nullable();
            $table->json('budget_template')->nullable();
            $table->json('content_template')->nullable();
            $table->json('channels_template')->nullable();
            $table->json('activities_template')->nullable();

            // AI prompts
            $table->text('ai_system_prompt')->nullable();
            $table->text('ai_generation_prompt')->nullable();

            // Metadata
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_premium')->default(false);
            $table->integer('usage_count')->default(0);
            $table->decimal('avg_success_rate', 5, 2)->nullable();

            // Visual
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('thumbnail_url')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['type', 'is_active']);
            $table->index(['industry', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategy_templates');
    }
};
