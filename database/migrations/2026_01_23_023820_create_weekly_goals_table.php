<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('weekly_analytics_id')->nullable()->constrained()->nullOnDelete();
            $table->date('week_start');
            $table->date('week_end');

            // Goal targets
            $table->integer('target_leads')->default(0);
            $table->integer('target_won')->default(0);
            $table->decimal('target_conversion', 5, 2)->default(0);
            $table->decimal('target_revenue', 15, 2)->default(0);
            $table->integer('target_calls')->default(0);
            $table->integer('target_meetings')->default(0);

            // Actual results (filled at end of week)
            $table->integer('actual_leads')->default(0);
            $table->integer('actual_won')->default(0);
            $table->decimal('actual_conversion', 5, 2)->default(0);
            $table->decimal('actual_revenue', 15, 2)->default(0);
            $table->integer('actual_calls')->default(0);
            $table->integer('actual_meetings')->default(0);

            // Achievement percentages
            $table->decimal('leads_achievement', 5, 2)->default(0);
            $table->decimal('won_achievement', 5, 2)->default(0);
            $table->decimal('conversion_achievement', 5, 2)->default(0);
            $table->decimal('revenue_achievement', 5, 2)->default(0);
            $table->decimal('calls_achievement', 5, 2)->default(0);
            $table->decimal('meetings_achievement', 5, 2)->default(0);

            // Overall score
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'missed'])->default('pending');

            // AI generated goal (from previous week analysis)
            $table->text('ai_suggested_goal')->nullable();
            $table->json('ai_focus_areas')->nullable();

            // Notes
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['business_id', 'week_start']);
            $table->index(['business_id', 'status']);
        });

        // Operator-level weekly KPIs
        Schema::create('operator_weekly_kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('weekly_goal_id')->nullable()->constrained('weekly_goals')->nullOnDelete();
            $table->date('week_start');
            $table->date('week_end');

            // Targets
            $table->integer('target_leads')->default(0);
            $table->integer('target_won')->default(0);
            $table->decimal('target_revenue', 15, 2)->default(0);
            $table->integer('target_calls')->default(0);

            // Actuals
            $table->integer('actual_leads')->default(0);
            $table->integer('actual_won')->default(0);
            $table->decimal('actual_revenue', 15, 2)->default(0);
            $table->integer('actual_calls')->default(0);

            // Achievement
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->integer('rank')->nullable();

            $table->timestamps();

            $table->unique(['business_id', 'user_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_weekly_kpis');
        Schema::dropIfExists('weekly_goals');
    }
};
