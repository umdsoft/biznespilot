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
        Schema::table('call_analyses', function (Blueprint $table) {
            // Formatlangan transkript (Mijoz: / Operator: formatida)
            $table->longText('formatted_transcript')->nullable()->after('transcript');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_analyses', function (Blueprint $table) {
            $table->dropColumn('formatted_transcript');
        });
    }
};
