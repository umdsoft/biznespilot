<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite uchun enum o'zgartirish
        // Avval yangi ustunlar qo'shamiz
        Schema::table('integrations', function (Blueprint $table) {
            $table->string('status')->default('disconnected')->after('is_active');
            $table->timestamp('connected_at')->nullable()->after('status');
            $table->timestamp('expires_at')->nullable()->after('connected_at');
        });

        // Type ustunini string ga o'zgartirish (meta_ads qo'shish uchun)
        // SQLite da enum o'zgartirish qiyin, shuning uchun type ni string qilamiz
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropColumn(['status', 'connected_at', 'expires_at']);
        });
    }
};
