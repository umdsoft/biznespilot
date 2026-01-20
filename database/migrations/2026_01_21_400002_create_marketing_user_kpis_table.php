<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_user_kpis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // Period
            $table->date('date');
            $table->enum('period_type', ['daily', 'weekly', 'monthly'])->default('daily');

            // Lead metrics
            $table->unsignedInteger('leads_created')->default(0);
            $table->unsignedInteger('leads_qualified')->default(0);
            $table->unsignedInteger('leads_converted')->default(0);

            // Content metrics
            $table->unsignedInteger('content_published')->default(0);
            $table->unsignedInteger('campaigns_launched')->default(0);
            $table->unsignedInteger('campaigns_managed')->default(0);

            // Performance metrics
            $table->decimal('spend_managed', 15, 2)->default(0);
            $table->decimal('revenue_attributed', 15, 2)->default(0);

            // Activity metrics
            $table->unsignedInteger('tasks_completed')->default(0);
            $table->unsignedInteger('reports_generated')->default(0);

            // Calculated scores
            $table->decimal('performance_score', 5, 2)->default(0);
            $table->decimal('efficiency_score', 5, 2)->default(0);
            $table->decimal('overall_score', 5, 2)->default(0);

            $table->timestamps();

            // Unique constraint
            $table->unique(['business_id', 'user_id', 'date', 'period_type'], 'marketing_user_kpi_unique');

            // Indexes
            $table->index(['business_id', 'user_id', 'date']);
            $table->index(['business_id', 'date', 'period_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_user_kpis');
    }
};
