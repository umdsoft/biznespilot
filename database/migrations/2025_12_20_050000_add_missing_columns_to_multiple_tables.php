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
        Schema::table('chatbot_conversations', function (Blueprint $table) {
            $table->string('channel')->nullable()->after('external_id');
        });

        Schema::table('dream_buyers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_primary');
        });

        // Add dream_buyer_id to customers table if not exists
        if (!Schema::hasColumn('customers', 'dream_buyer_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreignId('dream_buyer_id')->nullable()->after('business_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chatbot_conversations', function (Blueprint $table) {
            $table->dropColumn('channel');
        });

        Schema::table('dream_buyers', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        if (Schema::hasColumn('customers', 'dream_buyer_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('dream_buyer_id');
            });
        }
    }
};
