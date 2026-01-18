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
        Schema::table('meta_insights', function (Blueprint $table) {
            // Add breakdown columns for demographics and placements
            if (! Schema::hasColumn('meta_insights', 'age_range')) {
                $table->string('age_range')->nullable()->after('actions');
            }
            if (! Schema::hasColumn('meta_insights', 'gender')) {
                $table->string('gender')->nullable()->after('age_range');
            }
            if (! Schema::hasColumn('meta_insights', 'publisher_platform')) {
                $table->string('publisher_platform')->nullable()->after('gender');
            }
            if (! Schema::hasColumn('meta_insights', 'platform_position')) {
                $table->string('platform_position')->nullable()->after('publisher_platform');
            }
            if (! Schema::hasColumn('meta_insights', 'device_platform')) {
                $table->string('device_platform')->nullable()->after('platform_position');
            }
            if (! Schema::hasColumn('meta_insights', 'object_type')) {
                $table->string('object_type')->nullable()->after('ad_account_id');
            }
            if (! Schema::hasColumn('meta_insights', 'object_id')) {
                $table->string('object_id')->nullable()->after('object_type');
            }
            if (! Schema::hasColumn('meta_insights', 'object_name')) {
                $table->string('object_name')->nullable()->after('object_id');
            }
            if (! Schema::hasColumn('meta_insights', 'business_id')) {
                $table->uuid('business_id')->nullable()->after('ad_account_id');
            }
            if (! Schema::hasColumn('meta_insights', 'frequency')) {
                $table->decimal('frequency', 10, 4)->nullable()->after('reach');
            }
            if (! Schema::hasColumn('meta_insights', 'unique_clicks')) {
                $table->integer('unique_clicks')->nullable()->after('clicks');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meta_insights', function (Blueprint $table) {
            $table->dropColumn([
                'age_range', 'gender', 'publisher_platform', 'platform_position',
                'device_platform', 'object_type', 'object_id', 'object_name',
                'business_id', 'frequency', 'unique_clicks',
            ]);
        });
    }
};
