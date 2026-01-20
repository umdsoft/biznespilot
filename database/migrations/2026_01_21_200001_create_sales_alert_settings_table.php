<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alert sozlamalari jadvali
     */
    public function up(): void
    {
        Schema::create('sales_alert_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();

            // Alert turi
            $table->string('alert_type', 50);
            $table->boolean('is_enabled')->default(true);

            // Qoidalar
            $table->json('conditions')->nullable(); // {"hours_before_penalty": 4, "kpi_threshold": 50}
            $table->json('recipients')->nullable(); // ["operator", "sales_head", "owner"]
            $table->json('channels')->nullable(); // ["app", "telegram", "email"]

            // Vaqt sozlamalari
            $table->string('frequency', 20)->default('instant'); // instant, hourly, daily
            $table->string('schedule_time', 10)->nullable(); // "09:00" - daily uchun

            $table->timestamps();

            // Unique constraint - har bir biznes uchun bitta alert_type
            $table->unique(['business_id', 'alert_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_alert_settings');
    }
};
