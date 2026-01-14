<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Skip for SQLite (used in tests) - ENUM not supported
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Update step_type enum to include marketing node types
        DB::statement("ALTER TABLE telegram_funnel_steps MODIFY COLUMN step_type ENUM('message', 'input', 'condition', 'action', 'delay', 'subscribe_check', 'quiz', 'ab_test', 'tag') DEFAULT 'message'");
    }

    public function down(): void
    {
        // Skip for SQLite
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Revert to original enum values
        DB::statement("ALTER TABLE telegram_funnel_steps MODIFY COLUMN step_type ENUM('message', 'input', 'condition', 'action', 'delay') DEFAULT 'message'");
    }
};
