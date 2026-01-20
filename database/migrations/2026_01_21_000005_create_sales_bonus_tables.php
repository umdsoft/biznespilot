<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bonus sozlamalari va hisoblangan bonuslar jadvallari
     */
    public function up(): void
    {
        // 1. Bonus sozlamalari
        Schema::create('sales_bonus_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            // Bonus turi
            $table->string('bonus_type', 30); // fixed, revenue_percentage, kpi_based, tiered

            // Fixed bonus uchun
            $table->decimal('base_amount', 15, 2)->default(0);

            // Revenue percentage uchun
            $table->string('percentage_of', 30)->nullable(); // revenue, profit
            $table->decimal('percentage_rate', 5, 2)->nullable(); // 5.00 = 5%

            // Tiered bonus uchun (JSON)
            // [{"min": 80, "max": 99, "multiplier": 1.0}, {"min": 100, "max": 119, "multiplier": 1.2}, ...]
            $table->json('tiers')->nullable();

            // Minimal talab
            $table->integer('min_kpi_score')->default(80); // Minimal KPI ball
            $table->integer('min_working_days')->default(20); // Minimal ish kunlari

            // Qo'llash sozlamalari
            $table->string('calculation_period', 20)->default('monthly'); // monthly, quarterly
            $table->json('applies_to_roles')->nullable(); // ['sales_operator']
            $table->boolean('requires_approval')->default(true);
            $table->boolean('auto_calculate')->default(true);

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_active']);
        });

        // 2. Hisoblangan bonuslar
        Schema::create('sales_bonus_calculations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('bonus_setting_id')->constrained('sales_bonus_settings')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // Davr
            $table->string('period_type', 20); // monthly, quarterly
            $table->date('period_start');
            $table->date('period_end');

            // KPI ma'lumotlari
            $table->integer('kpi_score')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->integer('working_days')->default(0);

            // Hisoblash
            $table->boolean('is_qualified')->default(false); // Minimal talablarni bajardimi
            $table->string('disqualification_reason')->nullable(); // Agar qualified bo'lmasa

            $table->decimal('base_amount', 15, 2)->default(0);
            $table->decimal('tier_multiplier', 5, 2)->default(1);
            $table->string('applied_tier')->nullable(); // Qaysi tier qo'llanildi
            $table->decimal('final_amount', 15, 2)->default(0);

            // Hisoblash detallari
            $table->json('calculation_breakdown')->nullable();

            // Status va workflow
            $table->string('status', 20)->default('pending');
            // pending, calculated, approved, rejected, paid, cancelled

            $table->foreignUuid('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('calculated_at')->nullable();

            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            $table->text('rejection_reason')->nullable();
            $table->foreignUuid('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();

            $table->timestamps();

            // Unique - har bir foydalanuvchi, har bir bonus setting, har bir davr
            $table->unique(
                ['business_id', 'bonus_setting_id', 'user_id', 'period_start'],
                'unique_bonus_calculation'
            );

            $table->index(['business_id', 'period_start', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_bonus_calculations');
        Schema::dropIfExists('sales_bonus_settings');
    }
};
