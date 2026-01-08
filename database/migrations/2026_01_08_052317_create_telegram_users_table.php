<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('telegram_bot_id')->constrained()->cascadeOnDelete();

            // Telegram data
            $table->bigInteger('telegram_id');
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('language_code', 10)->nullable();

            // Contact info
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Status
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_subscribed')->default(true);

            // BiznesPilot integration
            $table->foreignUuid('lead_id')->nullable()->constrained('leads')->nullOnDelete();

            // Tags va custom data
            $table->json('tags')->nullable();
            $table->json('custom_data')->nullable();

            // Activity tracking
            $table->timestamp('first_interaction_at')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->unsignedInteger('total_messages')->default(0);

            $table->timestamps();

            // Indexes
            $table->unique(['telegram_bot_id', 'telegram_id']);
            $table->index(['business_id', 'is_blocked']);
            $table->index('lead_id');
            $table->index('last_interaction_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_users');
    }
};
