<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `alert_rules` jadvaliga `alert_type` ustun qo'shish.
 *
 * Sabab: AlertRule model fillable'da bor, KpiAlertService where filter va
 * insert da ishlatadi, lekin DB schema'da ustun yo'q. Har soatda KPI alert
 * tekshiruvida SQLSTATE[42S22] xato qaytarib, alertlar yuborilmasdi.
 *
 * Default 'kpi' — eski yozuvlar avtomat to'ldiriladi, kelajakda yangi
 * type'lar qo'shilishi mumkin (sales, marketing, hr va h.k.).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('alert_rules', 'alert_type')) {
            return;
        }

        Schema::table('alert_rules', function (Blueprint $table) {
            $table->string('alert_type', 50)->default('kpi')->after('name')
                ->comment('Alert turi: kpi, sales, marketing, hr, va h.k.');
            $table->index(['business_id', 'alert_type'], 'alert_rules_business_type_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('alert_rules', 'alert_type')) {
            return;
        }

        Schema::table('alert_rules', function (Blueprint $table) {
            $table->dropIndex('alert_rules_business_type_idx');
            $table->dropColumn('alert_type');
        });
    }
};
