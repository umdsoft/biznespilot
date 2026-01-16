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
        Schema::table('instagram_automations', function (Blueprint $table) {
            // Add missing columns for flow builder
            if (!Schema::hasColumn('instagram_automations', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('instagram_automations', 'status')) {
                $table->string('status', 20)->default('draft')->after('type');
            }
            if (!Schema::hasColumn('instagram_automations', 'is_ai_enabled')) {
                $table->boolean('is_ai_enabled')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('instagram_automations', 'settings')) {
                $table->json('settings')->nullable()->after('action_config');
            }
            if (!Schema::hasColumn('instagram_automations', 'is_flow_based')) {
                $table->boolean('is_flow_based')->default(false)->after('flow_data');
            }
            if (!Schema::hasColumn('instagram_automations', 'trigger_count')) {
                $table->integer('trigger_count')->default(0)->after('executions_count');
            }
            if (!Schema::hasColumn('instagram_automations', 'conversion_count')) {
                $table->integer('conversion_count')->default(0)->after('trigger_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instagram_automations', function (Blueprint $table) {
            $columns = ['description', 'status', 'is_ai_enabled', 'settings', 'is_flow_based', 'trigger_count', 'conversion_count'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('instagram_automations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
