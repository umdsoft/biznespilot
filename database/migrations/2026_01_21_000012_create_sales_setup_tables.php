<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Setup Wizard va Templates jadvallari
     */
    public function up(): void
    {
        // KPI shablon to'plamlari
        Schema::create('sales_kpi_template_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Shablon ma'lumotlari
            $table->string('code', 50)->unique(); // b2b_sales, b2c_retail, edtech, etc.
            $table->string('name'); // "B2B Sotuv", "B2C Retail", etc.
            $table->text('description')->nullable();
            $table->string('industry', 50)->nullable(); // IT, Retail, Education, etc.
            $table->string('icon', 100)->nullable();

            // Shablon tarkibi (JSON)
            $table->json('kpi_settings'); // KPI sozlamalari
            $table->json('bonus_settings'); // Bonus sozlamalari
            $table->json('penalty_rules'); // Jarima qoidalari
            $table->json('achievement_definitions')->nullable(); // Yutuqlar

            // Tavsiya etilgan sozlamalar
            $table->json('recommended_targets')->nullable(); // Tavsiya etilgan maqsadlar
            $table->json('onboarding_tips')->nullable(); // Yordam matnlari

            // Holat
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false); // Tavsiya etilgan
            $table->integer('usage_count')->default(0); // Qancha marta ishlatilgan
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indeks
            $table->index(['is_active', 'industry']);
        });

        // Biznes setup progress
        Schema::create('sales_setup_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();

            // Qaysi shablondan boshlangan
            $table->foreignUuid('template_set_id')->nullable()->constrained('sales_kpi_template_sets')->nullOnDelete();

            // Bosqichlar holati
            $table->json('completed_steps'); // ['kpi_settings', 'bonus_settings', ...]
            $table->string('current_step', 50)->nullable();
            $table->integer('progress_percent')->default(0);

            // Sozlash jarayoni ma'lumotlari
            $table->json('wizard_data')->nullable(); // Vaqtinchalik ma'lumotlar
            $table->json('customizations')->nullable(); // Foydalanuvchi o'zgartirishlari

            // Holat
            $table->string('status', 20)->default('in_progress'); // in_progress, completed, skipped
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();

            // Kim tomonidan
            $table->foreignUuid('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('completed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Unikal
            $table->unique('business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_setup_progress');
        Schema::dropIfExists('sales_kpi_template_sets');
    }
};
