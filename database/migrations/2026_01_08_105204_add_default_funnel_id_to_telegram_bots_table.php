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
        Schema::table('telegram_bots', function (Blueprint $table) {
            $table->uuid('default_funnel_id')->nullable()->after('settings');

            $table->foreign('default_funnel_id')
                ->references('id')
                ->on('telegram_funnels')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('telegram_bots', function (Blueprint $table) {
            $table->dropForeign(['default_funnel_id']);
            $table->dropColumn('default_funnel_id');
        });
    }
};
