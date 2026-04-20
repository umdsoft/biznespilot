<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_business_connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('telegram_bot_id');

            // Telegram's BusinessConnection object fields
            $table->string('connection_id')->unique();
            $table->bigInteger('telegram_user_id'); // business owner's TG ID
            $table->string('owner_first_name')->nullable();
            $table->string('owner_last_name')->nullable();
            $table->string('owner_username')->nullable();
            $table->bigInteger('user_chat_id')->nullable();

            // Rights (Bot API 8.x BusinessBotRights)
            $table->boolean('can_reply')->default(true);
            $table->json('rights')->nullable();

            // Our control flags
            $table->boolean('is_enabled')->default(true);
            $table->boolean('ai_auto_reply')->default(true);
            $table->string('ai_mode')->default('hybrid'); // auto | hybrid | manual
            $table->json('settings')->nullable(); // working_hours, away_message, welcome_msg etc.

            // Persona (owner's tone of voice for AI)
            $table->text('persona_prompt')->nullable();

            $table->timestamp('connected_at');
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('telegram_bot_id')->references('id')->on('telegram_bots')->cascadeOnDelete();

            $table->index(['business_id', 'is_enabled']);
            $table->index('telegram_user_id');
            $table->index('disconnected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_business_connections');
    }
};
