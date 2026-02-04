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
        Schema::table('pbx_accounts', function (Blueprint $table) {
            $table->string('provider')->default('onlinepbx')->after('business_id');
            $table->index('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbx_accounts', function (Blueprint $table) {
            $table->dropIndex(['provider']);
            $table->dropColumn('provider');
        });
    }
};
