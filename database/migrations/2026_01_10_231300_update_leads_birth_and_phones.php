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
        Schema::table('leads', function (Blueprint $table) {
            // Change birth_year to birth_date
            $table->dropColumn('birth_year');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('company');
            $table->string('phone2', 50)->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['birth_date', 'phone2']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->year('birth_year')->nullable()->after('company');
        });
    }
};
