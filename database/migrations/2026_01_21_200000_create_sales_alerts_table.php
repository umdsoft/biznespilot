<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Smart Alert tizimi uchun jadval
     */
    public function up(): void
    {
        Schema::create('sales_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete(); // NULL = barcha uchun

            // Alert turi
            $table->string('type', 50); // lead_followup, kpi_warning, target_reminder, penalty_warning, daily_summary
            $table->string('priority', 20)->default('medium'); // low, medium, high, urgent

            // Kontent
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Qo'shimcha ma'lumot (lead_id, kpi_score, etc.)

            // Bog'liq entity (polymorphic)
            $table->string('alertable_type')->nullable();
            $table->uuid('alertable_id')->nullable();

            // Status
            $table->string('status', 20)->default('unread'); // unread, read, dismissed, actioned
            $table->timestamp('read_at')->nullable();
            $table->timestamp('scheduled_at')->nullable(); // Qachon ko'rsatilishi kerak
            $table->timestamp('expires_at')->nullable(); // Qachon o'chishi kerak

            // Channels
            $table->json('channels')->nullable(); // app, telegram, email, push
            $table->json('sent_via')->nullable(); // Qaysi kanallar orqali yuborildi

            $table->timestamps();

            // Indekslar
            $table->index(['business_id', 'user_id', 'status']);
            $table->index(['scheduled_at', 'status']);
            $table->index(['business_id', 'type']);
            $table->index(['alertable_type', 'alertable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_alerts');
    }
};
