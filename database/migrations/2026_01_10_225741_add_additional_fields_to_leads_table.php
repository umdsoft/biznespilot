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
            $table->year('birth_year')->nullable()->after('company');
            $table->string('region', 100)->nullable()->after('birth_year');
            $table->string('district', 100)->nullable()->after('region');
            $table->string('address')->nullable()->after('district');
            $table->enum('gender', ['male', 'female'])->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['birth_year', 'region', 'district', 'address', 'gender']);
        });
    }
};
