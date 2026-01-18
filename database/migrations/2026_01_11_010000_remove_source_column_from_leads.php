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
            // Remove the old 'source' text column that conflicts with the 'source' relationship
            // The source_id foreign key is used instead
            if (Schema::hasColumn('leads', 'source')) {
                $table->dropColumn('source');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'source')) {
                $table->string('source')->nullable()->after('position');
            }
        });
    }
};
