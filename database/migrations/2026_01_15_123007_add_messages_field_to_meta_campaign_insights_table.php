<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meta_campaign_insights', function (Blueprint $table) {
            $table->unsignedBigInteger('messages')->default(0)->after('video_views');
        });
    }

    public function down(): void
    {
        Schema::table('meta_campaign_insights', function (Blueprint $table) {
            $table->dropColumn('messages');
        });
    }
};
