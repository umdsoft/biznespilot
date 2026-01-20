<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sotuv KPI sozlamalari jadvali
     * Har bir biznes uchun KPI turlarini va ularning parametrlarini saqlaydi
     */
    public function up(): void
    {
        Schema::create('sales_kpi_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();

            // KPI asosiy ma'lumotlari
            $table->string('kpi_type', 50); // leads_converted, revenue, calls_made, etc.
            $table->string('name'); // Ko'rsatiladigan nom (o'zbekcha)
            $table->text('description')->nullable();

            // O'lchov parametrlari
            $table->string('measurement_unit', 30)->default('count'); // count, currency, percentage, minutes
            $table->string('calculation_method', 30)->default('sum'); // sum, average, count, rate
            $table->string('data_source', 30)->default('auto'); // leads, tasks, calls, orders, manual
            $table->string('period_type', 20)->default('monthly'); // daily, weekly, monthly

            // Vazn (umumiy ball hisobida)
            $table->decimal('weight', 5, 2)->default(0); // 0-100 orasida, jami 100% bo'lishi kerak

            // Maqsad darajalari
            $table->decimal('target_min', 15, 2)->nullable(); // Minimal qabul qilinadigan
            $table->decimal('target_good', 15, 2)->nullable(); // Yaxshi natija
            $table->decimal('target_excellent', 15, 2)->nullable(); // A'lo natija

            // Scoring formulasi (maxsus qoidalar uchun)
            $table->json('scoring_formula')->nullable();

            // Qo'llash sozlamalari
            $table->json('applies_to_roles')->nullable(); // ['sales_operator', 'sales_head']
            $table->json('applies_to_departments')->nullable(); // Maxsus bo'limlar uchun

            // Holat
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // Tizim tomonidan yaratilgan
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indekslar
            $table->index(['business_id', 'is_active']);
            $table->index(['business_id', 'kpi_type']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_kpi_settings');
    }
};
