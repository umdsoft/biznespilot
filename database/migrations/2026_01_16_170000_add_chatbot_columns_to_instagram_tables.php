<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add missing columns for Instagram Chatbot functionality
     */
    public function up(): void
    {
        // Add missing columns to instagram_conversations
        Schema::table('instagram_conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('instagram_conversations', 'profile_picture_url')) {
                $table->string('profile_picture_url')->nullable()->after('participant_name');
            }
            if (!Schema::hasColumn('instagram_conversations', 'current_automation_id')) {
                $table->uuid('current_automation_id')->nullable()->after('status');
            }
            if (!Schema::hasColumn('instagram_conversations', 'current_step')) {
                $table->integer('current_step')->default(0)->after('current_automation_id');
            }
            if (!Schema::hasColumn('instagram_conversations', 'collected_data')) {
                $table->json('collected_data')->nullable()->after('current_step');
            }
            if (!Schema::hasColumn('instagram_conversations', 'tags')) {
                $table->json('tags')->nullable()->after('collected_data');
            }
            if (!Schema::hasColumn('instagram_conversations', 'is_bot_active')) {
                $table->boolean('is_bot_active')->default(true)->after('last_message_at');
            }
            if (!Schema::hasColumn('instagram_conversations', 'needs_human')) {
                $table->boolean('needs_human')->default(false)->after('is_bot_active');
            }
        });

        // Add missing columns to instagram_messages
        Schema::table('instagram_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('instagram_messages', 'instagram_message_id')) {
                $table->string('instagram_message_id')->nullable()->after('conversation_id');
            }
            if (!Schema::hasColumn('instagram_messages', 'message_type')) {
                $table->string('message_type', 50)->default('text')->after('direction');
            }
            if (!Schema::hasColumn('instagram_messages', 'media_data')) {
                $table->json('media_data')->nullable()->after('content');
            }
            if (!Schema::hasColumn('instagram_messages', 'is_automated')) {
                $table->boolean('is_automated')->default(false)->after('media_data');
            }
            if (!Schema::hasColumn('instagram_messages', 'automation_id')) {
                $table->uuid('automation_id')->nullable()->after('is_automated');
            }
            if (!Schema::hasColumn('instagram_messages', 'is_read')) {
                $table->boolean('is_read')->default(false)->after('automation_id');
            }
            if (!Schema::hasColumn('instagram_messages', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('is_read');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instagram_conversations', function (Blueprint $table) {
            $columns = ['profile_picture_url', 'current_automation_id', 'current_step', 'collected_data', 'tags', 'is_bot_active', 'needs_human'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('instagram_conversations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('instagram_messages', function (Blueprint $table) {
            $columns = ['instagram_message_id', 'message_type', 'media_data', 'is_automated', 'automation_id', 'is_read', 'sent_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('instagram_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
