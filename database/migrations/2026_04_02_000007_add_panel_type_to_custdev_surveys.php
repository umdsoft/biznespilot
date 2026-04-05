<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custdev_surveys', function (Blueprint $table) {
            $table->string('panel_type', 20)->default('business')->after('business_id'); // business, marketing, hr
            $table->index(['business_id', 'panel_type']);
        });
    }

    public function down(): void
    {
        Schema::table('custdev_surveys', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'panel_type']);
            $table->dropColumn('panel_type');
        });
    }
};
