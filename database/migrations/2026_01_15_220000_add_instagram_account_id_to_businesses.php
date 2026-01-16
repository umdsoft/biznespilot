<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->uuid('instagram_account_id')->nullable()->after('id');
            $table->foreign('instagram_account_id')
                ->references('id')
                ->on('instagram_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['instagram_account_id']);
            $table->dropColumn('instagram_account_id');
        });
    }
};
