<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_broadcasts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('telegram_bot_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();

            // Identification
            $table->string('name');

            // Content
            $table->json('content');

            // Keyboard
            $table->json('keyboard')->nullable();

            // Target filter
            $table->json('target_filter')->nullable();

            // Stats
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('blocked_count')->default(0);

            // Status
            $table->enum('status', [
                'draft',
                'scheduled',
                'sending',
                'paused',
                'completed',
                'cancelled'
            ])->default('draft');

            // Schedule
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_broadcasts');
    }
};
