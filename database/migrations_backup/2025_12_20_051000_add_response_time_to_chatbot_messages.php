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
        // Add response_time_seconds to chatbot_messages table
        if (Schema::hasTable('chatbot_messages') && ! Schema::hasColumn('chatbot_messages', 'response_time_seconds')) {
            Schema::table('chatbot_messages', function (Blueprint $table) {
                $table->integer('response_time_seconds')->nullable()->after('content');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('chatbot_messages', 'response_time_seconds')) {
            Schema::table('chatbot_messages', function (Blueprint $table) {
                $table->dropColumn('response_time_seconds');
            });
        }
    }
};
