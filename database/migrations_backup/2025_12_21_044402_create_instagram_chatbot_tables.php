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
        // Automation flows (funnels)
        Schema::create('instagram_automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'paused', 'draft'])->default('draft');
            $table->enum('type', ['keyword', 'comment', 'story_mention', 'story_reply', 'dm', 'welcome'])->default('keyword');
            $table->boolean('is_ai_enabled')->default(false);
            $table->json('settings')->nullable(); // AI settings, delay settings, etc.
            $table->integer('trigger_count')->default(0);
            $table->integer('conversion_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Triggers - what starts the automation
        Schema::create('instagram_automation_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('instagram_automations')->onDelete('cascade');
            $table->enum('trigger_type', [
                'keyword_dm',           // DM da kalit so'z
                'keyword_comment',      // Commentda kalit so'z
                'story_mention',        // Story'da mention qilganda
                'story_reply',          // Story'ga reply
                'new_follower',         // Yangi follower
                'post_like',            // Post like qilganda
                'post_save',            // Post save qilganda
                'reel_comment',         // Reel'ga comment
                'media_share',          // Media share qilganda
            ]);
            $table->json('keywords')->nullable(); // Kalit so'zlar ro'yxati
            $table->string('media_id')->nullable(); // Specific post/reel uchun
            $table->boolean('case_sensitive')->default(false);
            $table->boolean('exact_match')->default(false);
            $table->timestamps();
        });

        // Actions - what happens when triggered
        Schema::create('instagram_automation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('instagram_automations')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->enum('action_type', [
                'send_dm',              // DM yuborish
                'send_dm_with_buttons', // Tugmali DM
                'send_media',           // Rasm/video yuborish
                'send_voice',           // Voice message
                'add_tag',              // Tag qo'shish (segmentatsiya)
                'remove_tag',           // Tag olib tashlash
                'delay',                // Kutish
                'condition',            // Shart (if/else)
                'ai_response',          // AI javob
                'collect_data',         // Ma'lumot yig'ish (email, telefon)
                'webhook',              // Tashqi tizimga yuborish
                'reply_comment',        // Commentga javob
            ]);
            $table->text('message_template')->nullable();
            $table->json('buttons')->nullable(); // Quick reply buttons
            $table->json('media')->nullable(); // Media attachments
            $table->json('condition_rules')->nullable(); // For condition type
            $table->integer('delay_seconds')->nullable();
            $table->string('webhook_url')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // Conversation tracking
        Schema::create('instagram_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->constrained()->onDelete('cascade');
            $table->string('participant_id'); // Instagram user ID
            $table->string('participant_username')->nullable();
            $table->string('participant_name')->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->enum('status', ['active', 'waiting', 'resolved', 'blocked'])->default('active');
            $table->foreignId('current_automation_id')->nullable()->constrained('instagram_automations')->nullOnDelete();
            $table->integer('current_step')->default(0);
            $table->json('collected_data')->nullable(); // Email, phone, etc.
            $table->json('tags')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->boolean('is_bot_active')->default(true);
            $table->boolean('needs_human')->default(false);
            $table->timestamps();

            $table->unique(['instagram_account_id', 'participant_id'], 'ig_conv_account_participant_unique');
            $table->index('status');
            $table->index('last_message_at');
        });

        // Message history
        Schema::create('instagram_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('instagram_conversations')->onDelete('cascade');
            $table->foreignId('automation_id')->nullable()->constrained('instagram_automations')->nullOnDelete();
            $table->string('instagram_message_id')->nullable()->unique();
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->enum('message_type', ['text', 'media', 'voice', 'story_mention', 'story_reply', 'reaction', 'unsupported']);
            $table->text('content')->nullable();
            $table->json('media_data')->nullable();
            $table->boolean('is_automated')->default(false);
            $table->boolean('is_read')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('direction');
            $table->index('sent_at');
        });

        // Quick replies / Buttons
        Schema::create('instagram_quick_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->string('shortcut')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();
        });

        // Automation analytics
        Schema::create('instagram_automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('instagram_automations')->onDelete('cascade');
            $table->foreignId('conversation_id')->nullable()->constrained('instagram_conversations')->nullOnDelete();
            $table->string('trigger_type');
            $table->string('trigger_value')->nullable(); // e.g., the keyword that triggered
            $table->enum('status', ['triggered', 'completed', 'failed', 'skipped']);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['automation_id', 'created_at']);
            $table->index('status');
        });

        // Broadcast campaigns
        Schema::create('instagram_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('message');
            $table->json('media')->nullable();
            $table->json('target_tags')->nullable(); // Filter by tags
            $table->json('target_filters')->nullable(); // Additional filters
            $table->enum('status', ['draft', 'scheduled', 'sending', 'completed', 'failed'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_broadcasts');
        Schema::dropIfExists('instagram_automation_logs');
        Schema::dropIfExists('instagram_quick_replies');
        Schema::dropIfExists('instagram_messages');
        Schema::dropIfExists('instagram_conversations');
        Schema::dropIfExists('instagram_automation_actions');
        Schema::dropIfExists('instagram_automation_triggers');
        Schema::dropIfExists('instagram_automations');
    }
};
