<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove all AI-related tables as AI Tahlil feature is being removed.
     */
    public function up(): void
    {
        // Disable foreign key checks
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('ai_insights');
        Schema::dropIfExists('ai_diagnostics');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('ai_monthly_strategies');

        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tables cannot be restored - they need to be recreated manually if needed
    }
};
