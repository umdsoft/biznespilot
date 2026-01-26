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
        Schema::table('offers', function (Blueprint $table) {
            // Add value_proposition if not exists
            if (!Schema::hasColumn('offers', 'value_proposition')) {
                $table->text('value_proposition')->nullable()->after('description');
            }

            // Add target_audience if not exists
            if (!Schema::hasColumn('offers', 'target_audience')) {
                $table->text('target_audience')->nullable()->after('value_proposition');
            }

            // Add pricing if not exists (alias for price for model compatibility)
            if (!Schema::hasColumn('offers', 'pricing')) {
                $table->decimal('pricing', 12, 2)->nullable()->after('target_audience');
            }

            // Add pricing_model if not exists
            if (!Schema::hasColumn('offers', 'pricing_model')) {
                $table->string('pricing_model', 50)->nullable()->after('pricing');
            }

            // Add scarcity if not exists
            if (!Schema::hasColumn('offers', 'scarcity')) {
                $table->text('scarcity')->nullable()->after('bonuses');
            }

            // Add urgency if not exists
            if (!Schema::hasColumn('offers', 'urgency')) {
                $table->text('urgency')->nullable()->after('scarcity');
            }

            // Add time_delay_days if not exists (for Value Equation)
            if (!Schema::hasColumn('offers', 'time_delay_days')) {
                $table->integer('time_delay_days')->nullable()->after('perceived_likelihood_score');
            }

            // Add effort_score if not exists (for Value Equation)
            if (!Schema::hasColumn('offers', 'effort_score')) {
                $table->integer('effort_score')->nullable()->after('time_delay_days');
            }

            // Add guarantee_type if not exists
            if (!Schema::hasColumn('offers', 'guarantee_type')) {
                $table->string('guarantee_type', 50)->nullable();
            }

            // Add guarantee_terms if not exists
            if (!Schema::hasColumn('offers', 'guarantee_terms')) {
                $table->text('guarantee_terms')->nullable();
            }

            // Add guarantee_period_days if not exists
            if (!Schema::hasColumn('offers', 'guarantee_period_days')) {
                $table->integer('guarantee_period_days')->nullable();
            }

            // Add core_offer if not exists
            if (!Schema::hasColumn('offers', 'core_offer')) {
                $table->text('core_offer')->nullable();
            }

            // Add total_value if not exists
            if (!Schema::hasColumn('offers', 'total_value')) {
                $table->decimal('total_value', 14, 2)->nullable();
            }

            // Add conversion_rate if not exists
            if (!Schema::hasColumn('offers', 'conversion_rate')) {
                $table->decimal('conversion_rate', 5, 2)->nullable();
            }

            // Add metadata if not exists
            if (!Schema::hasColumn('offers', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop columns in down() as they may have data
    }
};
