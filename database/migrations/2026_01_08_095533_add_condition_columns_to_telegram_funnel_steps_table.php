<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_funnel_steps', function (Blueprint $table) {
            // Condition configuration for condition step type
            $table->json('condition')->nullable()->after('action_config');

            // Condition branch connections (true/false paths)
            $table->uuid('condition_true_step_id')->nullable()->after('condition');
            $table->uuid('condition_false_step_id')->nullable()->after('condition_true_step_id');

            // Foreign keys for condition branches
            $table->foreign('condition_true_step_id')
                ->references('id')
                ->on('telegram_funnel_steps')
                ->nullOnDelete();

            $table->foreign('condition_false_step_id')
                ->references('id')
                ->on('telegram_funnel_steps')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('telegram_funnel_steps', function (Blueprint $table) {
            $table->dropForeign(['condition_true_step_id']);
            $table->dropForeign(['condition_false_step_id']);
            $table->dropColumn([
                'condition',
                'condition_true_step_id',
                'condition_false_step_id',
            ]);
        });
    }
};
