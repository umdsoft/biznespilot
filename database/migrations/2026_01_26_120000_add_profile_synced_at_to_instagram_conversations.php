<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Instagram chatbot MVP uchun qo'shimcha ustunlar
 *
 * 1. instagram_conversations.profile_synced_at - User Profile Sync timestamp
 * 2. instagram_messages.status - Xabar holati (sent, delivered, read, failed)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Conversations: profile_synced_at
        Schema::table('instagram_conversations', function (Blueprint $table) {
            $table->timestamp('profile_synced_at')->nullable()->after('profile_picture_url');
        });

        // Messages: status (for failed message tracking)
        Schema::table('instagram_messages', function (Blueprint $table) {
            $table->string('status', 20)->default('sent')->after('is_read');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('instagram_conversations', function (Blueprint $table) {
            $table->dropColumn('profile_synced_at');
        });

        Schema::table('instagram_messages', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
