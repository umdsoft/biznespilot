<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates extended Instagram tables with UUID compatibility
     */
    public function up(): void
    {
        // =====================================================
        // FLOW BUILDER TABLES
        // =====================================================

        // Flow nodes - visual automation builder blocks
        Schema::create('instagram_flow_nodes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('automation_id');
            $table->string('node_id')->comment('Frontend UUID');
            $table->string('node_type', 50); // trigger_keyword_dm, action_send_dm, etc.
            $table->json('data')->nullable();
            $table->json('position')->nullable(); // {x, y} canvas position
            $table->timestamps();

            $table->foreign('automation_id')->references('id')->on('instagram_automations')->onDelete('cascade');
            $table->unique(['automation_id', 'node_id']);
            $table->index('node_type');
        });

        // Flow edges - connections between nodes
        Schema::create('instagram_flow_edges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('automation_id');
            $table->string('edge_id')->comment('Frontend UUID');
            $table->string('source_node_id');
            $table->string('target_node_id');
            $table->string('source_handle')->nullable()->comment('yes/no for conditions');
            $table->timestamps();

            $table->foreign('automation_id')->references('id')->on('instagram_automations')->onDelete('cascade');
            $table->unique(['automation_id', 'edge_id']);
        });

        // =====================================================
        // AUTOMATION SUPPORT TABLES
        // =====================================================

        // Ready-made automation templates
        Schema::create('instagram_automation_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 50)->default('general');
            $table->string('icon', 50)->nullable();
            $table->json('nodes')->nullable();
            $table->json('edges')->nullable();
            $table->integer('usage_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });

        // Automation triggers - what starts the automation
        Schema::create('instagram_automation_triggers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('automation_id');
            $table->string('trigger_type', 50); // keyword_dm, keyword_comment, story_mention, etc.
            $table->json('keywords')->nullable();
            $table->string('media_id')->nullable();
            $table->boolean('case_sensitive')->default(false);
            $table->boolean('exact_match')->default(false);
            $table->timestamps();

            $table->foreign('automation_id')->references('id')->on('instagram_automations')->onDelete('cascade');
            $table->index('automation_id');
            $table->index('trigger_type');
        });

        // Automation actions - what happens when triggered
        Schema::create('instagram_automation_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('automation_id');
            $table->integer('order')->default(0);
            $table->string('action_type', 50); // send_dm, send_media, add_tag, delay, etc.
            $table->text('message_template')->nullable();
            $table->json('buttons')->nullable();
            $table->json('media')->nullable();
            $table->json('condition_rules')->nullable();
            $table->integer('delay_seconds')->nullable();
            $table->string('webhook_url')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->foreign('automation_id')->references('id')->on('instagram_automations')->onDelete('cascade');
            $table->index('automation_id');
            $table->index('action_type');
        });

        // Automation execution logs
        Schema::create('instagram_automation_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('automation_id');
            $table->uuid('conversation_id')->nullable();
            $table->string('trigger_type', 50);
            $table->string('trigger_value')->nullable();
            $table->string('status', 20)->default('triggered'); // triggered, completed, failed, skipped
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('automation_id')->references('id')->on('instagram_automations')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('instagram_conversations')->onDelete('set null');
            $table->index(['automation_id', 'created_at']);
            $table->index('status');
        });

        // =====================================================
        // MESSAGING TABLES
        // =====================================================

        // Quick replies / canned responses
        Schema::create('instagram_quick_replies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->string('title');
            $table->text('content');
            $table->string('shortcut', 50)->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('instagram_accounts')->onDelete('cascade');
            $table->index('account_id');
        });

        // Broadcast campaigns
        Schema::create('instagram_broadcasts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->string('name');
            $table->text('message');
            $table->json('media')->nullable();
            $table->json('target_tags')->nullable();
            $table->json('target_filters')->nullable();
            $table->string('status', 20)->default('draft'); // draft, scheduled, sending, completed, failed
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('instagram_accounts')->onDelete('cascade');
            $table->index('account_id');
            $table->index('status');
        });

        // =====================================================
        // ANALYTICS TABLES
        // =====================================================

        // Audience demographics (cached weekly)
        Schema::create('instagram_audience', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id')->unique();
            $table->uuid('business_id');
            $table->json('age_gender')->nullable();
            $table->json('top_cities')->nullable();
            $table->json('top_countries')->nullable();
            $table->json('online_hours')->nullable();
            $table->json('online_days')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('instagram_accounts')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });

        // DM analytics (daily stats)
        Schema::create('instagram_dm_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->uuid('business_id');
            $table->date('date');
            $table->integer('total_conversations')->default(0);
            $table->integer('new_conversations')->default(0);
            $table->integer('messages_received')->default(0);
            $table->integer('messages_sent')->default(0);
            $table->string('source_media_id')->nullable();
            $table->integer('dm_from_post')->default(0);
            $table->integer('dm_from_reel')->default(0);
            $table->integer('dm_from_story')->default(0);
            $table->integer('dm_from_profile')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('instagram_accounts')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['account_id', 'date']);
            $table->index(['business_id', 'date']);
        });

        // Hashtag performance tracking
        Schema::create('instagram_hashtag_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->uuid('business_id');
            $table->string('hashtag');
            $table->integer('usage_count')->default(0);
            $table->integer('total_reach')->default(0);
            $table->integer('total_impressions')->default(0);
            $table->integer('total_engagement')->default(0);
            $table->decimal('avg_engagement_rate', 8, 4)->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('instagram_accounts')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['account_id', 'hashtag']);
            $table->index(['business_id', 'hashtag']);
        });

        // API sync logs
        Schema::create('instagram_sync_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->string('sync_type', 50); // full, incremental, media, insights, audience
            $table->string('status', 20)->default('pending'); // pending, running, completed, failed
            $table->integer('items_synced')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('instagram_accounts')->onDelete('cascade');
            $table->index('account_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_sync_logs');
        Schema::dropIfExists('instagram_hashtag_stats');
        Schema::dropIfExists('instagram_dm_stats');
        Schema::dropIfExists('instagram_audience');
        Schema::dropIfExists('instagram_broadcasts');
        Schema::dropIfExists('instagram_quick_replies');
        Schema::dropIfExists('instagram_automation_logs');
        Schema::dropIfExists('instagram_automation_actions');
        Schema::dropIfExists('instagram_automation_triggers');
        Schema::dropIfExists('instagram_automation_templates');
        Schema::dropIfExists('instagram_flow_edges');
        Schema::dropIfExists('instagram_flow_nodes');
    }
};
