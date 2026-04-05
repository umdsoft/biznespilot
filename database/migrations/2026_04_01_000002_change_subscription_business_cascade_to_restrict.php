<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('cascade');
        });
    }
};
