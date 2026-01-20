<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_bonuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // Period
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('period_type', ['weekly', 'monthly'])->default('monthly');

            // Target reference
            $table->uuid('target_id')->nullable();

            // Base salary
            $table->decimal('base_salary', 15, 2)->default(0);

            // Bonus components
            $table->decimal('lead_bonus', 15, 2)->default(0);
            $table->decimal('lead_bonus_percent', 5, 2)->default(0);

            $table->decimal('cpl_bonus', 15, 2)->default(0);
            $table->decimal('cpl_bonus_percent', 5, 2)->default(0);

            $table->decimal('roas_bonus', 15, 2)->default(0);
            $table->decimal('roas_bonus_percent', 5, 2)->default(0);

            $table->decimal('accelerator_bonus', 15, 2)->default(0);
            $table->decimal('accelerator_percent', 5, 2)->default(0);

            // Penalties
            $table->decimal('total_penalties', 15, 2)->default(0);
            $table->json('penalty_details')->nullable();

            // Totals
            $table->decimal('gross_bonus', 15, 2)->default(0);
            $table->decimal('net_bonus', 15, 2)->default(0);
            $table->decimal('total_earnings', 15, 2)->default(0);

            // Snapshots
            $table->json('performance_snapshot')->nullable();
            $table->json('targets_snapshot')->nullable();

            // Status
            $table->enum('status', ['draft', 'calculated', 'approved', 'paid', 'disputed'])->default('draft');
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['business_id', 'user_id', 'period_start']);
            $table->index(['business_id', 'status']);
            $table->unique(['business_id', 'user_id', 'period_start', 'period_type'], 'marketing_bonus_unique');

            // Foreign key for target (after marketing_targets exists)
            $table->foreign('target_id')
                ->references('id')
                ->on('marketing_targets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_bonuses');
    }
};
