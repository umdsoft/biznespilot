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
        Schema::table('ai_diagnostics', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('business_id');
            $table->string('diagnostic_type')->default('onboarding')->after('version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_diagnostics', function (Blueprint $table) {
            $table->dropColumn(['version', 'diagnostic_type']);
        });
    }
};
