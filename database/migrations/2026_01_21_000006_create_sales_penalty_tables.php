<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jarima qoidalari, jarimalar va ogohlantirishlar jadvallari
     */
    public function up(): void
    {
        // 1. Jarima qoidalari
        Schema::create('sales_penalty_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            // Kategoriya
            $table->string('category', 30); // crm_discipline, performance, attendance, customer_service

            // Trigger turi
            $table->string('trigger_type', 20); // auto, manual
            $table->string('trigger_event', 50)->nullable();
            // lead_not_contacted_24h, crm_not_filled, task_overdue, low_kpi_3_days, missed_call

            // Trigger shartlari (JSON)
            // {"hours": 24, "consecutive_days": 3, "threshold": 50}
            $table->json('trigger_conditions')->nullable();

            // Jarima qiymati
            $table->string('penalty_type', 30); // fixed, percentage_of_bonus, warning_only
            $table->decimal('penalty_amount', 15, 2)->default(0); // Fixed summa
            $table->decimal('penalty_percentage', 5, 2)->default(0); // Bonus foizi

            // Ogohlantirish sozlamalari
            $table->boolean('warning_before_penalty')->default(true);
            $table->integer('warnings_before_penalty')->default(2);
            $table->integer('warning_validity_days')->default(30); // Ogohlantirishlar necha kunda o'chadi

            // Limitlar
            $table->integer('max_per_day')->nullable();
            $table->integer('max_per_month')->nullable();

            // Appeal
            $table->boolean('allow_appeal')->default(true);
            $table->integer('appeal_deadline_days')->default(3);

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_active']);
            $table->index(['trigger_type', 'trigger_event']);
        });

        // 2. Berilgan jarimalar
        Schema::create('sales_penalties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('penalty_rule_id')->nullable()->constrained('sales_penalty_rules')->nullOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // Sabab
            $table->string('category', 30);
            $table->string('reason');
            $table->text('description')->nullable();

            // Bog'liq entity (agar bor bo'lsa)
            $table->string('related_type', 50)->nullable(); // Lead, Task, CallLog
            $table->uuid('related_id')->nullable();

            // Trigger ma'lumotlari
            $table->json('trigger_data')->nullable();
            $table->timestamp('triggered_at');

            // Jarima qiymati
            $table->decimal('penalty_amount', 15, 2)->default(0);

            // Status
            $table->string('status', 20)->default('pending');
            // pending, warning, confirmed, appealed, appeal_approved, appeal_rejected, cancelled, deducted

            // Kim tomonidan
            $table->foreignUuid('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();

            // Tasdiqlash
            $table->foreignUuid('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();

            // Appeal
            $table->text('appeal_reason')->nullable();
            $table->timestamp('appealed_at')->nullable();
            $table->foreignUuid('appeal_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('appeal_reviewed_at')->nullable();
            $table->string('appeal_decision', 20)->nullable(); // approved, rejected
            $table->text('appeal_resolution')->nullable();

            // Bonus dan ayirildi
            $table->foreignUuid('deducted_from_bonus_id')->nullable()->constrained('sales_bonus_calculations')->nullOnDelete();
            $table->timestamp('deducted_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'user_id', 'status']);
            $table->index(['user_id', 'triggered_at']);
            $table->index(['status', 'triggered_at']);
        });

        // 3. Ogohlantirishlar
        Schema::create('sales_penalty_warnings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('penalty_rule_id')->constrained('sales_penalty_rules')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // Ogohlantirish turi
            $table->string('warning_type', 20)->default('system'); // system, verbal, written, final

            // Sabab
            $table->string('reason');
            $table->text('description')->nullable();

            // Bog'liq entity
            $table->string('related_type', 50)->nullable();
            $table->uuid('related_id')->nullable();

            // Hisob
            $table->integer('warning_number')->default(1); // 1, 2, 3...

            // Kim tomonidan
            $table->foreignUuid('issued_by')->nullable()->constrained('users')->nullOnDelete();

            // Foydalanuvchi ko'rdimi
            $table->timestamp('acknowledged_at')->nullable();

            // Amal qilish muddati
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'user_id', 'penalty_rule_id']);
            $table->index(['user_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_penalty_warnings');
        Schema::dropIfExists('sales_penalties');
        Schema::dropIfExists('sales_penalty_rules');
    }
};
