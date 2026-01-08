<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_funnel_steps', function (Blueprint $table) {
            // Step type for visual builder
            $table->enum('step_type', [
                'message',
                'input',
                'condition',
                'action',
                'delay'
            ])->default('message')->after('order');

            // Next step reference for visual builder connections
            $table->uuid('next_step_id')->nullable()->after('step_type');

            // Action configuration
            $table->enum('action_type', [
                'none',
                'create_lead',
                'update_user',
                'handoff',
                'send_notification',
                'webhook'
            ])->default('none')->after('actions');

            $table->json('action_config')->nullable()->after('action_type');

            // Input field name for storing user input
            $table->string('input_field')->nullable()->after('input_type');

            // Position for visual builder (canvas coordinates)
            $table->integer('position_x')->default(0)->after('delay_ms');
            $table->integer('position_y')->default(0)->after('position_x');

            // Foreign key (nullable because step might be deleted)
            $table->foreign('next_step_id')
                ->references('id')
                ->on('telegram_funnel_steps')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('telegram_funnel_steps', function (Blueprint $table) {
            $table->dropForeign(['next_step_id']);
            $table->dropColumn([
                'step_type',
                'next_step_id',
                'action_type',
                'action_config',
                'input_field',
                'position_x',
                'position_y'
            ]);
        });
    }
};
