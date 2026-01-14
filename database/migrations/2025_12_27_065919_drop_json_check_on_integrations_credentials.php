<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Drop the json_valid CHECK constraint on credentials column
     * because we use Laravel's 'encrypted' cast which stores
     * encrypted data (not valid JSON)
     */
    public function up(): void
    {
        // Skip for SQLite (used in tests) - constraint doesn't exist there
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Modify column to LONGTEXT removes the JSON CHECK constraint in MariaDB/MySQL
        DB::statement('ALTER TABLE integrations MODIFY COLUMN credentials LONGTEXT NULL');
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

        // Re-add the JSON column type (which adds the constraint back)
        DB::statement('ALTER TABLE integrations MODIFY COLUMN credentials JSON NULL');
    }
};
