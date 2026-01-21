<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Kamera orqali davomat tizimi uchun yangi ustunlar
     */
    public function up(): void
    {
        // attendance_records jadvaliga source va metadata qo'shish
        Schema::table('attendance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_records', 'source')) {
                $table->string('source', 50)->default('manual')->after('ip_address');
            }
            if (!Schema::hasColumn('attendance_records', 'metadata')) {
                $table->json('metadata')->nullable()->after('source');
            }
        });

        // business_user jadvaliga employee identification ustunlari qo'shish
        Schema::table('business_user', function (Blueprint $table) {
            if (!Schema::hasColumn('business_user', 'employee_code')) {
                $table->string('employee_code', 50)->nullable()->after('role');
            }
            if (!Schema::hasColumn('business_user', 'badge_id')) {
                $table->string('badge_id', 100)->nullable()->after('employee_code');
            }
            if (!Schema::hasColumn('business_user', 'face_id')) {
                $table->string('face_id', 100)->nullable()->after('badge_id');
            }
        });

        // Indexlarni qo'shish (xato bo'lsa o'tkazib yuborish)
        try {
            Schema::table('business_user', function (Blueprint $table) {
                $table->index(['business_id', 'employee_code'], 'bu_employee_code_idx');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            Schema::table('business_user', function (Blueprint $table) {
                $table->index(['business_id', 'badge_id'], 'bu_badge_id_idx');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            Schema::table('business_user', function (Blueprint $table) {
                $table->index(['business_id', 'face_id'], 'bu_face_id_idx');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn(['source', 'metadata']);
        });

        Schema::table('business_user', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'employee_code']);
            $table->dropIndex(['business_id', 'badge_id']);
            $table->dropIndex(['business_id', 'face_id']);
            $table->dropColumn(['employee_code', 'badge_id', 'face_id']);
        });
    }
};
