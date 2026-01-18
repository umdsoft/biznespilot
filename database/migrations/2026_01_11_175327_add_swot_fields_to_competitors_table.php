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
        Schema::table('competitors', function (Blueprint $table) {
            // Full SWOT data for each competitor
            if (! Schema::hasColumn('competitors', 'swot_data')) {
                $table->json('swot_data')->nullable()->after('weaknesses');
            }
            if (! Schema::hasColumn('competitors', 'swot_analyzed_at')) {
                $table->timestamp('swot_analyzed_at')->nullable();
            }
        });

        // Add business SWOT fields if not exists
        Schema::table('businesses', function (Blueprint $table) {
            if (! Schema::hasColumn('businesses', 'swot_data')) {
                $table->json('swot_data')->nullable();
            }
            if (! Schema::hasColumn('businesses', 'swot_updated_at')) {
                $table->timestamp('swot_updated_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitors', function (Blueprint $table) {
            $table->dropColumn(['swot_data', 'swot_analyzed_at']);
        });
    }
};
