<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('telegram_user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('telegram_bot_id')->constrained()->cascadeOnDelete();

            // Status
            $table->enum('status', [
                'active',
                'handoff',
                'closed',
            ])->default('active');

            // Handoff
            $table->foreignUuid('assigned_operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handoff_at')->nullable();
            $table->text('handoff_reason')->nullable();

            // Funnel tracking
            $table->foreignUuid('started_funnel_id')->nullable()->constrained('telegram_funnels')->nullOnDelete();
            $table->foreignUuid('lead_id')->nullable()->constrained('leads')->nullOnDelete();

            // Tags
            $table->json('tags')->nullable();

            // Timestamps
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'status']);
            $table->index('assigned_operator_id');
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_conversations');
    }
};
