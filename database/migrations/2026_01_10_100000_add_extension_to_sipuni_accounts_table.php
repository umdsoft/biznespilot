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
        // Check if table and column exist before adding
        if (Schema::hasTable('sipuni_accounts') && ! Schema::hasColumn('sipuni_accounts', 'extension')) {
            Schema::table('sipuni_accounts', function (Blueprint $table) {
                $table->string('extension')->nullable()->after('caller_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sipuni_accounts') && Schema::hasColumn('sipuni_accounts', 'extension')) {
            Schema::table('sipuni_accounts', function (Blueprint $table) {
                $table->dropColumn('extension');
            });
        }
    }
};
