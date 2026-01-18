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
        Schema::table('telegram_triggers', function (Blueprint $table) {
            // Add name column
            $table->string('name')->after('step_id')->default('Trigger');
        });

        // Update type enum to include more options (MySQL only)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE telegram_triggers MODIFY COLUMN type ENUM('command', 'callback', 'keyword', 'event', 'start_payload', 'text') DEFAULT 'keyword'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_triggers', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        // Revert type enum (MySQL only)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE telegram_triggers MODIFY COLUMN type ENUM('command', 'callback', 'keyword', 'event') DEFAULT 'keyword'");
        }
    }
};
