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
        Schema::table('meta_insights', function (Blueprint $table) {
            // Fix decimal precision for metrics that can exceed 100%
            $table->decimal('ctr', 10, 4)->nullable()->change();
            $table->decimal('cpc', 15, 4)->nullable()->change();
            $table->decimal('cpm', 15, 4)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meta_insights', function (Blueprint $table) {
            $table->decimal('ctr', 5, 4)->nullable()->change();
            $table->decimal('cpc', 8, 4)->nullable()->change();
            $table->decimal('cpm', 8, 4)->nullable()->change();
        });
    }
};
