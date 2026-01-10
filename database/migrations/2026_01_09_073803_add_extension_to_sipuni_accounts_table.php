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
        Schema::table('sipuni_accounts', function (Blueprint $table) {
            $table->string('extension')->nullable()->after('caller_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sipuni_accounts', function (Blueprint $table) {
            $table->dropColumn('extension');
        });
    }
};
