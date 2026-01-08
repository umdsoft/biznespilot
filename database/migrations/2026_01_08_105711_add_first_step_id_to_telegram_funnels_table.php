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
        Schema::table('telegram_funnels', function (Blueprint $table) {
            $table->uuid('first_step_id')->nullable()->after('settings');
            $table->text('completion_message')->nullable()->after('first_step_id');

            $table->foreign('first_step_id')
                ->references('id')
                ->on('telegram_funnel_steps')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('telegram_funnels', function (Blueprint $table) {
            $table->dropForeign(['first_step_id']);
            $table->dropColumn(['first_step_id', 'completion_message']);
        });
    }
};
