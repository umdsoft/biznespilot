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
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['amocrm', 'google_analytics', 'facebook_ads', 'google_ads', 'stripe', 'paypal', 'mailchimp', 'sendgrid', 'zapier', 'webhook', 'other'])->default('other');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->text('credentials'); // Encrypted API keys, tokens, etc.
            $table->json('config')->nullable(); // Integration-specific configuration
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error_message')->nullable();
            $table->integer('sync_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_active']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
