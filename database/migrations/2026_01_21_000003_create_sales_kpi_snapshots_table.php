<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * KPI kunlik snapshot jadvali
     * Har bir xodimning har bir KPI uchun kunlik natijalarini saqlaydi
     */
    public function up(): void
    {
        Schema::create('sales_kpi_daily_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('kpi_setting_id')->constrained('sales_kpi_settings')->cascadeOnDelete();

            // Sana
            $table->date('snapshot_date');

            // Qiymatlar
            $table->decimal('actual_value', 15, 2)->default(0); // Haqiqiy qiymat
            $table->decimal('target_value', 15, 2)->default(0); // Maqsad qiymat (shu kunga)
            $table->decimal('achievement_percent', 8, 2)->default(0); // Bajarilish foizi
            $table->integer('score')->default(0); // 0-100 ball

            // Hisoblash detallari (debug uchun)
            $table->json('calculation_details')->nullable();

            // Yaratilgan vaqt
            $table->timestamps();

            // Unikal indeks - har bir foydalanuvchi, har bir KPI, har bir kun
            $table->unique(
                ['business_id', 'user_id', 'kpi_setting_id', 'snapshot_date'],
                'unique_daily_snapshot'
            );

            // Tezkor qidiruv indekslari
            $table->index(['user_id', 'snapshot_date']);
            $table->index(['business_id', 'snapshot_date']);
            $table->index(['kpi_setting_id', 'snapshot_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_kpi_daily_snapshots');
    }
};
