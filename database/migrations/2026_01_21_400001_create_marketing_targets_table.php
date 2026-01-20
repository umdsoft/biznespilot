<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_targets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();

            // Target davri
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('period_type', ['weekly', 'monthly', 'quarterly'])->default('monthly');

            // Optional filters
            $table->foreignUuid('channel_id')
                ->nullable()
                ->constrained('marketing_channels')
                ->cascadeOnDelete();

            $table->foreignUuid('campaign_id')
                ->nullable()
                ->constrained('campaigns')
                ->cascadeOnDelete();

            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            // Lead targets
            $table->unsignedInteger('leads_target')->default(0);
            $table->unsignedInteger('mql_target')->default(0);
            $table->unsignedInteger('sql_target')->default(0);
            $table->unsignedInteger('won_target')->default(0);

            // Financial targets
            $table->decimal('spend_budget', 15, 2)->default(0);
            $table->decimal('revenue_target', 15, 2)->default(0);

            // KPI targets
            $table->decimal('cpl_target', 12, 2)->nullable();
            $table->decimal('roas_target', 10, 4)->nullable();
            $table->decimal('roi_target', 10, 4)->nullable();
            $table->decimal('conversion_target', 5, 2)->nullable();

            // Status
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['business_id', 'period_start', 'period_end']);
            $table->index(['business_id', 'user_id', 'period_type']);
            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_targets');
    }
};
