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
        Schema::create('chatbot_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('channel_type', ['telegram', 'whatsapp', 'facebook', 'instagram', 'website', 'other'])->default('telegram');
            $table->text('welcome_message')->nullable();
            $table->text('default_response')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('ai_enabled')->default(true);
            $table->boolean('human_handoff_enabled')->default(false);
            $table->json('config')->nullable(); // Bot token, API keys, settings, etc.
            $table->json('business_hours')->nullable();
            $table->json('auto_responses')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_active']);
            $table->index('channel_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_configs');
    }
};
