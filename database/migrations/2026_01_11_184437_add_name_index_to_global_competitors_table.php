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
        Schema::table('global_competitors', function (Blueprint $table) {
            // Add index on name column for faster LIKE searches
            $table->index('name', 'global_competitors_name_index');

            // Add composite index for region + industry searches
            $table->index(['region', 'industry'], 'global_competitors_region_industry_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('global_competitors', function (Blueprint $table) {
            $table->dropIndex('global_competitors_name_index');
            $table->dropIndex('global_competitors_region_industry_index');
        });
    }
};
