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
        Schema::create('kpi_daily_actuals', function (Blueprint $table) {
            $table->id();

            // Business & KPI Reference (UUID for business_id)
            $table->uuid('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->string('kpi_code', 100)->index();
            $table->foreign('kpi_code')->references('kpi_code')->on('kpi_templates')
                ->onDelete('cascade')->onUpdate('cascade');

            // Date & Time
            $table->date('date')->index()->comment('The date this data is for');
            $table->time('recorded_time')->nullable()->comment('Time of day if intraday tracking needed');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
                ->nullable()->index();

            // Values
            $table->decimal('planned_value', 15, 2)->comment('The target/goal for this day');
            $table->decimal('actual_value', 15, 2)->comment('The actual achieved value');
            $table->string('unit', 50)->comment('dona, %, UZS, minutes, etc.');

            // Performance Calculation
            $table->decimal('achievement_percentage', 7, 2)->comment('(actual / planned) * 100');
            $table->decimal('variance', 15, 2)->comment('actual - planned (can be negative)');
            $table->decimal('variance_percentage', 7, 2)->comment('((actual - planned) / planned) * 100');

            // Status
            $table->enum('status', ['green', 'yellow', 'red', 'grey'])->default('grey')->index()
                ->comment('green: ≥90%, yellow: 70-89%, red: <70%, grey: no data yet');
            $table->boolean('is_on_track')->default(false)
                ->comment('Is this day contributing to weekly/monthly target achievement');

            // Data Source & Quality
            $table->enum('data_source', [
                'manual',
                'instagram_api',
                'facebook_api',
                'google_api',
                'sales_system',
                'crm',
                'pos_system',
                'automated_calculation',
                'imported',
            ])->default('manual')->index();

            $table->boolean('is_verified')->default(false)->index()
                ->comment('Has this data been verified/confirmed');
            $table->uuid('verified_by_user_id')->nullable();
            $table->foreign('verified_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();

            $table->boolean('is_estimated')->default(false)
                ->comment('Is this an estimated value or actual measurement');
            $table->text('estimation_method')->nullable();

            // Context & Notes
            $table->text('notes')->nullable()->comment('User notes about this day performance');
            $table->json('metadata')->nullable()->comment('Additional context: weather, events, campaigns, etc.');

            // Anomaly Detection
            $table->boolean('is_anomaly')->default(false)->index()
                ->comment('Flagged by anomaly detection algorithm');
            $table->enum('anomaly_type', ['spike', 'drop', 'outlier', 'none'])->default('none');
            $table->text('anomaly_reason')->nullable();

            // Financial Impact (if applicable)
            $table->decimal('financial_impact', 15, 2)->nullable()
                ->comment('Money gained/lost due to this performance (UZS)');
            $table->enum('impact_type', ['revenue', 'cost', 'opportunity_cost', 'none'])->default('none');

            // Related Data (No foreign key constraints to avoid circular dependency)
            $table->unsignedBigInteger('weekly_summary_id')->nullable()->index()
                ->comment('Which weekly summary this belongs to');
            $table->unsignedBigInteger('monthly_summary_id')->nullable()->index()
                ->comment('Which monthly summary this belongs to');

            // Audit (UUID for user IDs)
            $table->uuid('created_by_user_id')->nullable();
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->uuid('updated_by_user_id')->nullable();
            $table->foreign('updated_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Unique Constraint: One record per business per KPI per date (per time if intraday)
            $table->unique(['business_id', 'kpi_code', 'date', 'recorded_time'], 'unique_daily_kpi');

            // Performance Indexes
            $table->index(['business_id', 'date']);
            $table->index(['kpi_code', 'date']);
            $table->index(['status', 'date']);
            $table->index(['is_anomaly', 'date']);
            $table->index(['data_source', 'is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_daily_actuals');
    }
};
