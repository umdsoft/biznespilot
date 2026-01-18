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
        Schema::table('leads', function (Blueprint $table) {
            // Add source_id column for foreign key relationship
            if (! Schema::hasColumn('leads', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source');
            }

            // Add score column if it doesn't exist
            if (! Schema::hasColumn('leads', 'score')) {
                $table->integer('score')->default(0)->after('estimated_value');
            }

            // Add uuid column if it doesn't exist
            if (! Schema::hasColumn('leads', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }
        });

        // Add foreign key constraint only if lead_sources table exists
        if (Schema::hasTable('lead_sources')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->foreign('source_id')
                    ->references('id')
                    ->on('lead_sources')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Try to drop foreign key if it exists
            try {
                $table->dropForeign(['source_id']);
            } catch (\Exception $e) {
                // Foreign key may not exist
            }

            if (Schema::hasColumn('leads', 'source_id')) {
                $table->dropColumn('source_id');
            }

            if (Schema::hasColumn('leads', 'score')) {
                $table->dropColumn('score');
            }

            if (Schema::hasColumn('leads', 'uuid')) {
                $table->dropColumn('uuid');
            }
        });
    }
};
