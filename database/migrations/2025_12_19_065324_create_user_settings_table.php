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
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // API Keys (encrypted)
            $table->text('openai_api_key')->nullable();
            $table->text('claude_api_key')->nullable();

            // Notification Settings
            $table->boolean('email_notifications')->default(true);
            $table->boolean('browser_notifications')->default(true);
            $table->boolean('marketing_emails')->default(false);

            // AI Preferences
            $table->string('preferred_ai_model')->default('gpt-4'); // gpt-4, claude-3, etc.
            $table->integer('ai_creativity_level')->default(7); // 1-10 scale

            // UI Preferences
            $table->string('theme')->default('light'); // light, dark, auto
            $table->string('language')->default('uz'); // uz, ru, en

            // Business Preferences
            $table->json('notification_preferences')->nullable();
            $table->json('integrations')->nullable(); // social media, email, etc.

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
