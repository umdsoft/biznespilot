<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_calendar', function (Blueprint $table) {
            if (! Schema::hasColumn('content_calendar', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (! Schema::hasColumn('content_calendar', 'monthly_plan_id')) {
                $table->uuid('monthly_plan_id')->nullable()->after('weekly_plan_id');
            }
            if (! Schema::hasColumn('content_calendar', 'media_urls')) {
                $table->json('media_urls')->nullable()->after('hashtags');
            }
        });
    }

    public function down(): void
    {
        Schema::table('content_calendar', function (Blueprint $table) {
            $table->dropColumn(['description', 'monthly_plan_id', 'media_urls']);
        });
    }
};
