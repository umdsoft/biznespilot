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
        Schema::table('call_logs', function (Blueprint $table) {
            // Analysis status tracking
            $table->enum('analysis_status', [
                'pending',           // Tahlil qilinmagan
                'queued',            // Tahlil navbatda
                'transcribing',      // STT jarayonida
                'analyzing',         // AI tahlil jarayonida
                'completed',         // Tayyor
                'failed'             // Xatolik
            ])->default('pending')->after('metadata');

            $table->text('analysis_error')->nullable()->after('analysis_status');

            // Index for analysis status filtering
            $table->index(['business_id', 'analysis_status']);
            $table->index(['lead_id', 'analysis_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'analysis_status']);
            $table->dropIndex(['lead_id', 'analysis_status']);
            $table->dropColumn(['analysis_status', 'analysis_error']);
        });
    }
};
