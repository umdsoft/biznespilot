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
            $table->unsignedBigInteger('source_id')->nullable()->after('source');

            // Add score column if it doesn't exist
            if (!Schema::hasColumn('leads', 'score')) {
                $table->integer('score')->default(0)->after('estimated_value');
            }

            // Add uuid column if it doesn't exist
            if (!Schema::hasColumn('leads', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }

            // Add foreign key constraint
            $table->foreign('source_id')
                ->references('id')
                ->on('lead_sources')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['source_id']);
            $table->dropColumn('source_id');

            if (Schema::hasColumn('leads', 'score')) {
                $table->dropColumn('score');
            }

            if (Schema::hasColumn('leads', 'uuid')) {
                $table->dropColumn('uuid');
            }
        });
    }
};
