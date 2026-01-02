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
        Schema::create('kpi_daily_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->date('date');

            // LIDLAR (Leads)
            $table->integer('leads_digital')->default(0);
            $table->integer('leads_offline')->default(0);
            $table->integer('leads_referral')->default(0);
            $table->integer('leads_organic')->default(0);
            $table->integer('leads_total')->default(0); // Auto-calculated

            // XARAJATLAR (Expenses)
            $table->decimal('spend_digital', 15, 2)->default(0);
            $table->decimal('spend_offline', 15, 2)->default(0);
            $table->decimal('spend_other', 15, 2)->default(0);
            $table->decimal('spend_total', 15, 2)->default(0); // Auto-calculated

            // SOTUVLAR (Sales)
            $table->integer('sales_new')->default(0);
            $table->integer('sales_repeat')->default(0);
            $table->integer('sales_total')->default(0); // Auto-calculated

            // DAROMAD (Revenue)
            $table->decimal('revenue_new', 15, 2)->default(0);
            $table->decimal('revenue_repeat', 15, 2)->default(0);
            $table->decimal('revenue_total', 15, 2)->default(0); // Auto-calculated

            // TO'LOVLAR (Payments) - Optional breakdown
            $table->decimal('payment_cash', 15, 2)->default(0);
            $table->decimal('payment_card', 15, 2)->default(0);
            $table->decimal('payment_transfer', 15, 2)->default(0);
            $table->decimal('payment_credit', 15, 2)->default(0);
            $table->decimal('payment_other', 15, 2)->default(0);

            // HISOBLANGAN METRIKALAR (Calculated Metrics)
            $table->decimal('avg_check', 15, 2)->nullable();
            $table->decimal('conversion_rate', 5, 2)->nullable();
            $table->decimal('cpl', 15, 2)->nullable(); // Cost Per Lead
            $table->decimal('cac', 15, 2)->nullable(); // Customer Acquisition Cost

            // META
            $table->text('notes')->nullable();
            $table->enum('source', ['manual', 'import', 'integration', 'pos'])->default('manual');
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->boolean('is_complete')->default(false); // All required fields filled
            $table->uuid('verified_by')->nullable();
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            // Unique constraint - one entry per business per day
            $table->unique(['business_id', 'date']);

            // Indexes
            $table->index(['business_id', 'date']);
            $table->index(['date', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_daily_entries');
    }
};
