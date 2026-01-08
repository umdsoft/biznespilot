<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_bots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();

            // Bot credentials (encrypted)
            $table->text('bot_token');
            $table->string('bot_username')->nullable();
            $table->string('bot_first_name')->nullable();

            // Webhook
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret', 64)->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            // Settings (JSON)
            $table->json('settings')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['business_id', 'bot_username']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_bots');
    }
};
