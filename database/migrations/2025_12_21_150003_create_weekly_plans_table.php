<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('monthly_plan_id')->nullable()->constrained('monthly_plans')->nullOnDelete();

            // Week identification
            $table->year('year');
            $table->unsignedTinyInteger('week_number'); // ISO week 1-53
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('week_of_month'); // 1-5
            $table->date('start_date');
            $table->date('end_date');
            $table->string('title');
            $table->enum('status', ['draft', 'active', 'completed', 'archived'])->default('draft');

            // Focus and priorities
            $table->string('weekly_focus')->nullable();
            $table->json('priorities')->nullable(); // Top 3 priorities
            $table->text('notes')->nullable();

            // Goals for the week
            $table->json('goals')->nullable(); // [{name, target, metric}]

            // Daily breakdown
            $table->json('monday')->nullable();
            $table->json('tuesday')->nullable();
            $table->json('wednesday')->nullable();
            $table->json('thursday')->nullable();
            $table->json('friday')->nullable();
            $table->json('saturday')->nullable();
            $table->json('sunday')->nullable();

            // Tasks
            $table->json('tasks')->nullable(); // [{title, description, due_date, status, assigned_to}]
            $table->integer('total_tasks')->default(0);
            $table->integer('completed_tasks')->default(0);

            // Content to publish
            $table->json('content_items')->nullable();
            $table->integer('posts_planned')->default(0);
            $table->integer('posts_published')->default(0);

            // Targets
            $table->decimal('revenue_target', 15, 2)->nullable();
            $table->decimal('spend_budget', 15, 2)->nullable();
            $table->integer('lead_target')->nullable();
            $table->integer('engagement_target')->nullable();

            // Activities
            $table->json('marketing_activities')->nullable();
            $table->json('sales_activities')->nullable();
            $table->json('meetings')->nullable();

            // AI suggestions
            $table->json('ai_suggestions')->nullable();
            $table->json('ai_content_ideas')->nullable();

            // Results
            $table->json('actual_results')->nullable();
            $table->unsignedTinyInteger('completion_percent')->default(0);

            // Timestamps
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['business_id', 'year', 'week_number']);
            $table->index(['monthly_plan_id']);
            $table->index(['start_date', 'end_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_plans');
    }
};
