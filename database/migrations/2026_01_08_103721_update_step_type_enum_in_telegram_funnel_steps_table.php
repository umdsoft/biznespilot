<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update step_type enum to include marketing node types
        DB::statement("ALTER TABLE telegram_funnel_steps MODIFY COLUMN step_type ENUM('message', 'input', 'condition', 'action', 'delay', 'subscribe_check', 'quiz', 'ab_test', 'tag') DEFAULT 'message'");
    }

    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE telegram_funnel_steps MODIFY COLUMN step_type ENUM('message', 'input', 'condition', 'action', 'delay') DEFAULT 'message'");
    }
};
