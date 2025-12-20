<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Value Equation - "$100M Offers" by Alex Hormozi
     * Formula: VALUE = (Dream Outcome × Perceived Likelihood) / (Time Delay × Effort & Sacrifice)
     */
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            // Value Equation Components
            $table->integer('dream_outcome_score')->default(5)->comment('1-10: Nimaga erishadi?');
            $table->integer('perceived_likelihood_score')->default(5)->comment('1-10: Muvaffaqiyat ehtimoli');
            $table->integer('time_delay_days')->default(30)->comment('Kun: Qancha vaqt ketadi?');
            $table->integer('effort_score')->default(5)->comment('1-10: Qancha mehnat kerak?');
            $table->decimal('value_score', 10, 2)->nullable()->comment('Avtomatik hisoblangan qiymat');

            // Guarantee Information
            $table->enum('guarantee_type', [
                'unconditional',  // Shartsiz qaytarish
                'conditional',    // Shartli qaytarish
                'performance',    // Natijaga bog\'liq
                'anti-guarantee', // Teskari kafolat
                'implied'         // Nazarda tutilgan
            ])->nullable();
            $table->text('guarantee_terms')->nullable()->comment('Kafolat shartlari');
            $table->integer('guarantee_period_days')->nullable()->comment('Kafolat muddati (kunlar)');

            // Additional Value Stack Info
            $table->text('core_offer')->nullable()->comment('Asosiy taklif tavsifi');
            $table->decimal('total_value', 10, 2)->nullable()->comment('Jami qiymat (core + bonuses)');

            // Note: bonuses, urgency, scarcity, guarantees already exist in the table
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn([
                'dream_outcome_score',
                'perceived_likelihood_score',
                'time_delay_days',
                'effort_score',
                'value_score',
                'guarantee_type',
                'guarantee_terms',
                'guarantee_period_days',
                'core_offer',
                'total_value',
            ]);
        });
    }
};
