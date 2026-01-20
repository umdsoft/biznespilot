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
        Schema::table('annual_strategies', function (Blueprint $table) {
            if (!Schema::hasColumn('annual_strategies', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }
            if (!Schema::hasColumn('annual_strategies', 'vision_statement')) {
                $table->text('vision_statement')->nullable()->after('title');
            }
            if (!Schema::hasColumn('annual_strategies', 'revenue_target')) {
                $table->decimal('revenue_target', 14, 2)->nullable()->after('vision_statement');
            }
            if (!Schema::hasColumn('annual_strategies', 'strategic_goals')) {
                $table->json('strategic_goals')->nullable()->after('annual_budget');
            }
            if (!Schema::hasColumn('annual_strategies', 'focus_areas')) {
                $table->json('focus_areas')->nullable()->after('strategic_goals');
            }
            if (!Schema::hasColumn('annual_strategies', 'primary_channels')) {
                $table->json('primary_channels')->nullable()->after('focus_areas');
            }
            if (!Schema::hasColumn('annual_strategies', 'customer_target')) {
                $table->integer('customer_target')->nullable()->after('revenue_target');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('annual_strategies', function (Blueprint $table) {
            $columns = ['uuid', 'vision_statement', 'revenue_target', 'strategic_goals', 'focus_areas', 'primary_channels', 'customer_target'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('annual_strategies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
