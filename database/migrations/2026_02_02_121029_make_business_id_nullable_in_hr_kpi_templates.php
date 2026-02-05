<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK first, then modify column, then re-add FK
        Schema::table('hr_kpi_templates', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
        });

        Schema::table('hr_kpi_templates', function (Blueprint $table) {
            $table->uuid('business_id')->nullable()->change();
        });

        Schema::table('hr_kpi_templates', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('hr_kpi_templates', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
        });

        Schema::table('hr_kpi_templates', function (Blueprint $table) {
            $table->uuid('business_id')->nullable(false)->change();
        });

        Schema::table('hr_kpi_templates', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });
    }
};
