<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip for SQLite (used in tests) - column manipulation is problematic
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Skip if 'source' column doesn't exist
        if (! Schema::hasColumn('leads', 'source')) {
            return;
        }

        // For MySQL - drop index first if exists
        try {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropIndex('leads_business_id_source_index');
            });
        } catch (\Exception $e) {
            // Index doesn't exist, continue
        }

        // Now drop the column
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip for SQLite
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'source')) {
                $table->string('source')->nullable()->after('position');
            }
        });
    }
};
