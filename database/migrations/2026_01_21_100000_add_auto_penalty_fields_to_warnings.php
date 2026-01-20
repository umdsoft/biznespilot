<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Avtomatik jarima tizimi uchun qo'shimcha maydonlar
     */
    public function up(): void
    {
        Schema::table('sales_penalty_warnings', function (Blueprint $table) {
            // Auto-penalty trigger uchun
            $table->string('rule_code', 50)->nullable()->after('penalty_rule_id');

            // Status tracking
            $table->string('status', 20)->default('pending')->after('warning_number');
            // pending, warned, resolved, converted, cancelled

            // Deadline for auto-conversion
            $table->timestamp('deadline_at')->nullable()->after('expires_at');

            // Auto-convert flag
            $table->boolean('auto_convert')->default(false)->after('deadline_at');

            // When converted to penalty
            $table->timestamp('converted_at')->nullable()->after('auto_convert');

            $table->index(['business_id', 'status', 'deadline_at']);
            $table->index(['rule_code', 'status']);
        });

        // SalesPenalty jadvaliga ham warning_id qo'shish
        Schema::table('sales_penalties', function (Blueprint $table) {
            $table->foreignUuid('warning_id')->nullable()->after('penalty_rule_id')
                ->constrained('sales_penalty_warnings')->nullOnDelete();
            $table->boolean('auto_generated')->default(false)->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_penalties', function (Blueprint $table) {
            $table->dropForeign(['warning_id']);
            $table->dropColumn(['warning_id', 'auto_generated']);
        });

        Schema::table('sales_penalty_warnings', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'status', 'deadline_at']);
            $table->dropIndex(['rule_code', 'status']);
            $table->dropColumn(['rule_code', 'status', 'deadline_at', 'auto_convert', 'converted_at']);
        });
    }
};
