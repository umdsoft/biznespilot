<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * activity_logs.business_id ustunini nullable qilish.
 *
 * Sabab: Auth event'lari (Login/Logout/Failed) paytida business_id
 * mavjud emas — admin tizimga kirayotgan paytda hali biznes contextga
 * ulanmagan, failed login'da esa user umuman aniqlanmagan.
 *
 * NOT NULL constraint bo'lsa har auth event SQLSTATE[HY000] xato qaytaradi
 * va silent fail bo'lib, "Faoliyat jurnali" sahifasi bo'sh ko'rinadi.
 *
 * Mavjud yozuvlarga ta'sir yo'q — ALTER COLUMN constraint'ni bo'shatadi
 * faqat.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('activity_logs', 'business_id')) {
            return;
        }

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->uuid('business_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Reverse qilmaymiz — null bilan yozuvlar bo'lishi mumkin va
        // ularni majburan o'chirish noto'g'ri.
    }
};
