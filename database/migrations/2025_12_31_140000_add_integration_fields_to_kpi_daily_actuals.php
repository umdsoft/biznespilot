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
        Schema::table('kpi_daily_actuals', function (Blueprint $table) {
            // Integration tracking fields
            $table->string('integration_sync_id', 100)->nullable()->after('data_source')
                ->comment('Unique ID from integration source for this data point');

            $table->enum('sync_status', ['synced', 'pending', 'failed', 'manual'])
                ->default('manual')->after('integration_sync_id')
                ->comment('Status of data synchronization from integration');

            $table->timestamp('last_synced_at')->nullable()->after('sync_status')
                ->comment('Last time data was synced from integration');

            $table->boolean('auto_calculated')->default(false)->after('last_synced_at')
                ->comment('Whether this value was automatically calculated from integration');

            $table->boolean('can_override')->default(true)->after('auto_calculated')
                ->comment('Whether manual override is allowed for this data point');

            $table->json('sync_metadata')->nullable()->after('can_override')
                ->comment('Additional metadata from integration sync (raw data, API response, etc)');

            $table->string('overridden_by')->nullable()->after('sync_metadata')
                ->comment('User ID who manually overrode the auto-calculated value');

            $table->timestamp('overridden_at')->nullable()->after('overridden_by')
                ->comment('When the value was manually overridden');

            $table->decimal('original_synced_value', 15, 2)->nullable()->after('overridden_at')
                ->comment('Original value from integration before manual override');

            // Indexes for performance
            $table->index(['sync_status', 'last_synced_at'], 'kpi_daily_sync_status_idx');
            $table->index(['auto_calculated', 'can_override'], 'kpi_daily_auto_calc_idx');
            $table->index('integration_sync_id', 'kpi_daily_integration_sync_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpi_daily_actuals', function (Blueprint $table) {
            $table->dropIndex('kpi_daily_sync_status_idx');
            $table->dropIndex('kpi_daily_auto_calc_idx');
            $table->dropIndex('kpi_daily_integration_sync_idx');

            $table->dropColumn([
                'integration_sync_id',
                'sync_status',
                'last_synced_at',
                'auto_calculated',
                'can_override',
                'sync_metadata',
                'overridden_by',
                'overridden_at',
                'original_synced_value',
            ]);
        });
    }
};
