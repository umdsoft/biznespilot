<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Operator qo'ng'iroq statistikasi - har bir operator uchun
     * kunlik/haftalik/oylik statistikani saqlash
     */
    public function up(): void
    {
        Schema::create('operator_call_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete(); // Operator

            // Davr (kun, hafta, oy)
            $table->enum('period_type', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->date('period_date'); // Davr boshi sanasi

            // Asosiy statistika
            $table->integer('total_calls')->default(0);
            $table->integer('analyzed_calls')->default(0);
            $table->integer('successful_calls')->default(0); // answered
            $table->integer('missed_calls')->default(0);

            // Tahlil statistikasi
            $table->decimal('avg_score', 5, 2)->nullable(); // O'rtacha ball
            $table->decimal('min_score', 5, 2)->nullable();
            $table->decimal('max_score', 5, 2)->nullable();

            // Bosqichlar bo'yicha o'rtacha balllar
            $table->json('avg_stage_scores')->nullable();

            // Anti-patterns statistikasi
            $table->integer('total_anti_patterns')->default(0);
            $table->json('anti_pattern_counts')->nullable(); // Har bir xato turi bo'yicha

            // Vaqt statistikasi
            $table->integer('total_duration_seconds')->default(0);
            $table->integer('avg_duration_seconds')->default(0);

            // Xarajat
            $table->decimal('total_analysis_cost', 10, 6)->default(0);

            // O'sish/pasayish (oldingi davrga nisbatan)
            $table->decimal('score_change', 5, 2)->nullable(); // +5.2 yoki -3.1
            $table->decimal('score_change_percent', 5, 2)->nullable();

            $table->timestamps();

            // Indekslar
            $table->unique(['business_id', 'user_id', 'period_type', 'period_date'], 'operator_period_unique');
            $table->index(['business_id', 'period_type', 'period_date']);
            $table->index(['user_id', 'period_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operator_call_stats');
    }
};
