<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-business login alias on `business_user` pivot.
 *
 * Maqsad: bir biznes egasi o'z xodimiga "manager", "operator1" kabi
 * username-asosida login berishi. Ushbu username faqat shu biznes ichida
 * unikal — boshqa biznes ham xuddi shu username'ni ishlatishi mumkin.
 *
 * Auth flow:
 *   1. AuthController login matnni tekshiradi:
 *      - phone (+998...) yoki users.login (global) → mavjud yo'l
 *      - aks holda: business_user.login orqali (yangi) — login_password tekshirib
 *        topilgan user_id bilan Auth::loginUsingId().
 *
 * Foydalanuvchi parolini o'zgartirish: agar shu xodim alias-login orqali kiradigan
 * bo'lsa, parol business_user.login_password ustunida saqlanadi (alohida bcrypt
 * hash). Agar global users.password orqali kiradigan bo'lsa (legacy), users.password
 * yangilanadi. Ikkala yondashuv parallel ishlaydi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_user', function (Blueprint $table) {
            // Per-business username — chiroyli, qisqa identifier (alphanumeric + _).
            $table->string('login', 50)->nullable()->after('permissions');
            // Per-business password hash. NULL bo'lsa user global users.password orqali kiradi.
            $table->string('login_password')->nullable()->after('login');

            // Per-business uniqueness: bir biznesda bir xil login takrorlanmasin.
            // NULL qiymatlar MySQL'da unique constraint'ga zid kelmaydi (multi-NULL ruxsat).
            $table->unique(['business_id', 'login'], 'business_user_business_login_unique');
        });
    }

    public function down(): void
    {
        Schema::table('business_user', function (Blueprint $table) {
            $table->dropUnique('business_user_business_login_unique');
            $table->dropColumn(['login', 'login_password']);
        });
    }
};
