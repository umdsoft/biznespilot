<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qo'ng'iroq tahlili va operator samaradorligi jadvallari.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Qo'ng'iroq tahlillari jadvali
        if (!Schema::hasTable('call_analyses')) {
            Schema::create('call_analyses', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->char('business_id', 36);
                $table->string('call_id', 100);
                $table->char('operator_id', 36)->nullable();
                $table->char('lead_id', 36)->nullable();
                $table->integer('duration_seconds');
                $table->text('audio_url')->nullable();
                $table->text('transcript')->nullable();
                $table->json('analysis_result')->nullable();
                $table->integer('overall_score')->nullable();
                $table->json('strengths')->nullable();
                $table->json('improvements')->nullable();
                $table->json('coaching_tips')->nullable();
                $table->json('detected_objections')->nullable();
                $table->enum('outcome', ['sale', 'lead', 'callback', 'lost'])->nullable();
                $table->string('model_used', 20)->nullable();
                $table->integer('tokens_used')->default(0);
                $table->decimal('cost_usd', 8, 6)->default(0);
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index('business_id', 'idx_business');
                $table->index('operator_id', 'idx_operator');
                $table->index('outcome', 'idx_outcome');
                $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            });
        }

        // Operator samaradorligi jadvali
        Schema::create('operator_performance', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('business_id', 36);
            $table->char('operator_id', 36);
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('total_calls')->default(0);
            $table->decimal('avg_score', 5, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->json('top_strengths')->nullable();
            $table->json('top_improvements')->nullable();
            $table->integer('rank_in_team')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'period_start'], 'idx_business_period');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_performance');
        Schema::dropIfExists('call_analyses');
    }
};
