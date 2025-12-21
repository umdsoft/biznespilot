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
        // Flow nodes - bloklar
        Schema::create('instagram_flow_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('instagram_automations')->onDelete('cascade');
            $table->string('node_id')->comment('Frontend UUID'); // frontend da unique ID
            $table->enum('node_type', [
                // Triggerlar
                'trigger_keyword_dm',      // DM da kalit so'z
                'trigger_keyword_comment', // Commentda kalit so'z
                'trigger_story_mention',   // Story mention
                'trigger_story_reply',     // Story reply
                'trigger_new_follower',    // Yangi follower

                // Shartlar (conditions)
                'condition_is_follower',   // Obunachimi tekshirish
                'condition_liked_post',    // Post ga like bosdimi
                'condition_saved_post',    // Post ni saqladimi
                'condition_has_tag',       // Tag bormi
                'condition_time_passed',   // Vaqt o'tdimi

                // Harakatlar
                'action_send_dm',          // DM yuborish
                'action_send_media',       // Media yuborish
                'action_send_link',        // Link yuborish
                'action_add_tag',          // Tag qo'shish
                'action_remove_tag',       // Tag olib tashlash
                'action_delay',            // Kutish
                'action_reply_comment',    // Commentga javob
                'action_ai_response',      // AI javob
            ]);
            $table->json('data')->nullable(); // Node specific data
            $table->json('position')->nullable(); // {x, y} position on canvas
            $table->timestamps();

            $table->unique(['automation_id', 'node_id']);
        });

        // Flow edges - ulanishlar
        Schema::create('instagram_flow_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('instagram_automations')->onDelete('cascade');
            $table->string('edge_id')->comment('Frontend UUID');
            $table->string('source_node_id');
            $table->string('target_node_id');
            $table->string('source_handle')->nullable()->comment('yes/no for conditions');
            $table->timestamps();

            $table->unique(['automation_id', 'edge_id']);
        });

        // Ready-made templates
        Schema::create('instagram_automation_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('category')->default('general');
            $table->string('icon')->nullable();
            $table->json('nodes'); // Array of nodes
            $table->json('edges'); // Array of edges
            $table->integer('usage_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add flow_data to automations for quick access
        Schema::table('instagram_automations', function (Blueprint $table) {
            $table->json('flow_data')->nullable()->after('settings');
            $table->boolean('is_flow_based')->default(false)->after('flow_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instagram_automations', function (Blueprint $table) {
            $table->dropColumn(['flow_data', 'is_flow_based']);
        });

        Schema::dropIfExists('instagram_automation_templates');
        Schema::dropIfExists('instagram_flow_edges');
        Schema::dropIfExists('instagram_flow_nodes');
    }
};
