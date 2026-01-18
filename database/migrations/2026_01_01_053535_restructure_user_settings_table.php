<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Restructure user_settings from key-value to column-based structure
     * for better performance and easier querying
     */
    public function up(): void
    {
        // Drop the old table and recreate with proper structure
        Schema::dropIfExists('user_settings');

        Schema::create('user_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();

            // Notification Settings
            $table->boolean('email_notifications')->default(true);
            $table->boolean('browser_notifications')->default(true);
            $table->boolean('marketing_emails')->default(false);

            // AI Settings
            $table->string('preferred_ai_model', 50)->default('gpt-4');
            $table->integer('ai_creativity_level')->default(7);

            // API Keys (encrypted)
            $table->text('openai_api_key')->nullable();
            $table->text('claude_api_key')->nullable();

            // UI Preferences
            $table->string('theme', 20)->default('light');
            $table->string('language', 5)->default('uz');

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');

        // Recreate old key-value structure
        Schema::create('user_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'key']);
        });
    }
};
