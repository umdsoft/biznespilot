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
        Schema::table('business_problems', function (Blueprint $table) {
            $table->string('impact_level', 20)->default('medium')->after('description');
            $table->string('frequency', 20)->nullable()->after('impact_level');
            $table->text('current_solution')->nullable()->after('frequency');
            $table->text('desired_outcome')->nullable()->after('current_solution');
        });
    }

    public function down(): void
    {
        Schema::table('business_problems', function (Blueprint $table) {
            $table->dropColumn(['impact_level', 'frequency', 'current_solution', 'desired_outcome']);
        });
    }
};
