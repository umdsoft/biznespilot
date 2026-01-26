<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketing KPI Snapshots jadvaliga loss_rate va win_rate qo'shish.
 *
 * Bu "Black Box" konsepsiyasi uchun - qancha lid yo'qotilganini kuzatish.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_kpi_snapshots', function (Blueprint $table) {
            // Loss rate - yo'qotilgan lidlar foizi
            $table->decimal('loss_rate', 5, 2)->default(0)->after('overall_conversion_rate');

            // Win rate - g'alaba qozongan lidlar foizi (won / (won + lost))
            $table->decimal('win_rate', 5, 2)->default(0)->after('loss_rate');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_kpi_snapshots', function (Blueprint $table) {
            $table->dropColumn(['loss_rate', 'win_rate']);
        });
    }
};
