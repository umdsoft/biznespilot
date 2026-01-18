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
        Schema::create('competitor_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competitor_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_id')->constrained()->onDelete('cascade');

            // Alert info
            $table->enum('type', [
                'follower_surge',      // >10% follower growth
                'engagement_spike',    // High engagement on post
                'new_campaign',        // New campaign detected
                'price_change',        // Price change detected
                'viral_content',       // Viral post detected
                'product_launch',      // New product/service
                'other',
            ]);
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('title');
            $table->text('message');

            // Related data
            $table->foreignId('activity_id')->nullable()->constrained('competitor_activities')->onDelete('cascade');
            $table->json('data')->nullable(); // Store alert-specific data

            // Status
            $table->enum('status', ['unread', 'read', 'archived'])->default('unread');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            // Notifications
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('notification_sent_at')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'type']);
            $table->index(['competitor_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitor_alerts');
    }
};
