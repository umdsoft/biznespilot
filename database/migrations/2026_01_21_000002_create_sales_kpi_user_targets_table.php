<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foydalanuvchi KPI maqsadlari jadvali
     * Har bir xodim uchun individual maqsadlarni saqlaydi
     */
    public function up(): void
    {
        Schema::create('sales_kpi_user_targets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('kpi_setting_id')->constrained('sales_kpi_settings')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // Davr ma'lumotlari
            $table->string('period_type', 20); // daily, weekly, monthly
            $table->date('period_start');
            $table->date('period_end');

            // Maqsad qiymatlari
            $table->decimal('target_value', 15, 2); // Belgilangan maqsad
            $table->decimal('adjusted_target', 15, 2)->nullable(); // O'rta muddatda o'zgartirilgan
            $table->text('adjustment_reason')->nullable(); // Nima uchun o'zgartirildi

            // Hisoblangan natijalar (snapshot dan olinadi, tezkor kirish uchun)
            $table->decimal('achieved_value', 15, 2)->default(0);
            $table->decimal('achievement_percent', 8, 2)->default(0);
            $table->integer('score')->default(0); // 0-100 ball

            // Kim tomonidan belgilangan
            $table->foreignUuid('set_by')->nullable()->constrained('users')->nullOnDelete();

            // Holat
            $table->string('status', 20)->default('active'); // active, completed, cancelled

            $table->timestamps();

            // Unikal indeks - har bir foydalanuvchi uchun bitta KPI, bitta davr
            $table->unique(
                ['business_id', 'kpi_setting_id', 'user_id', 'period_type', 'period_start'],
                'unique_user_kpi_period'
            );

            // Tezkor qidiruv indekslari
            $table->index(['user_id', 'period_start', 'period_end'], 'skut_user_period_idx');
            $table->index(['business_id', 'period_type', 'period_start'], 'skut_biz_type_start_idx');
            $table->index(['kpi_setting_id', 'status'], 'skut_kpi_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_kpi_user_targets');
    }
};
