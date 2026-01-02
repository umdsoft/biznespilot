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
        Schema::create('ad_integrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');

            // Platform type: google_ads, yandex_direct, youtube, facebook
            $table->string('platform', 50);

            // Account identifiers
            $table->string('account_id')->nullable();
            $table->string('account_name')->nullable();

            // OAuth tokens (encrypted)
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            // Platform-specific fields
            $table->string('developer_token')->nullable(); // For Google Ads
            $table->string('customer_id')->nullable(); // Google Ads Customer ID
            $table->string('login_customer_id')->nullable(); // Google Ads MCC ID

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status')->nullable(); // syncing, completed, failed
            $table->text('sync_error')->nullable();

            // Additional settings
            $table->json('settings')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['business_id', 'platform']);
            $table->index('platform');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_integrations');
    }
};
