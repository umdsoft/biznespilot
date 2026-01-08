<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_funnel_steps', function (Blueprint $table) {
            // Subscribe check configuration
            $table->json('subscribe_check')->nullable()->after('condition_false_step_id');
            $table->uuid('subscribe_true_step_id')->nullable()->after('subscribe_check');
            $table->uuid('subscribe_false_step_id')->nullable()->after('subscribe_true_step_id');

            // Quiz configuration
            $table->json('quiz')->nullable()->after('subscribe_false_step_id');

            // A/B Test configuration
            $table->json('ab_test')->nullable()->after('quiz');

            // Tag configuration
            $table->json('tag')->nullable()->after('ab_test');

            // Foreign keys for subscribe check branches
            $table->foreign('subscribe_true_step_id')
                ->references('id')
                ->on('telegram_funnel_steps')
                ->nullOnDelete();

            $table->foreign('subscribe_false_step_id')
                ->references('id')
                ->on('telegram_funnel_steps')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('telegram_funnel_steps', function (Blueprint $table) {
            $table->dropForeign(['subscribe_true_step_id']);
            $table->dropForeign(['subscribe_false_step_id']);
            $table->dropColumn([
                'subscribe_check',
                'subscribe_true_step_id',
                'subscribe_false_step_id',
                'quiz',
                'ab_test',
                'tag'
            ]);
        });
    }
};
