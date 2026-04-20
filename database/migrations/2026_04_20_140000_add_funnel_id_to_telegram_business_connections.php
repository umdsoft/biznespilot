<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_business_connections', function (Blueprint $table) {
            $table->uuid('funnel_id')->nullable()->after('primary_offer_id');
            $table->foreign('funnel_id')->references('id')->on('telegram_funnels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('telegram_business_connections', function (Blueprint $table) {
            $table->dropForeign(['funnel_id']);
            $table->dropColumn('funnel_id');
        });
    }
};
