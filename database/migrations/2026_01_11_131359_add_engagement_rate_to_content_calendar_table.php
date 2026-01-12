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
        Schema::table('content_calendar', function (Blueprint $table) {
            // Analytics metrics columns
            if (!Schema::hasColumn('content_calendar', 'views')) {
                $table->unsignedBigInteger('views')->nullable()->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'likes')) {
                $table->unsignedBigInteger('likes')->nullable()->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'comments')) {
                $table->unsignedBigInteger('comments')->nullable()->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'shares')) {
                $table->unsignedBigInteger('shares')->nullable()->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'saves')) {
                $table->unsignedBigInteger('saves')->nullable()->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'clicks')) {
                $table->unsignedBigInteger('clicks')->nullable()->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'reach')) {
                $table->unsignedBigInteger('reach')->nullable()->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'impressions')) {
                $table->unsignedBigInteger('impressions')->nullable()->default(0);
            }
            if (!Schema::hasColumn('content_calendar', 'engagement_rate')) {
                $table->decimal('engagement_rate', 8, 2)->nullable()->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_calendar', function (Blueprint $table) {
            $columns = ['views', 'likes', 'comments', 'shares', 'saves', 'clicks', 'reach', 'impressions', 'engagement_rate'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('content_calendar', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
