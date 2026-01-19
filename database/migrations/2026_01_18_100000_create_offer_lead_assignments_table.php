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
        Schema::create('offer_lead_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('offer_id');
            $table->uuid('lead_id');
            $table->uuid('business_id');
            $table->uuid('assigned_by')->nullable(); // User who assigned
            $table->uuid('telegram_user_id')->nullable(); // If sent via Telegram

            // Status tracking
            $table->enum('status', [
                'pending',      // Tayyorlanmoqda
                'sent',         // Yuborildi
                'delivered',    // Yetkazildi
                'viewed',       // Ko'rildi
                'clicked',      // Bosildi
                'interested',   // Qiziqdi
                'converted',    // Sotib oldi
                'rejected',     // Rad etdi
                'expired',      // Muddati tugadi
            ])->default('pending');

            // Channel info
            $table->enum('channel', [
                'telegram',
                'sms',
                'email',
                'whatsapp',
                'manual',
            ])->default('manual');

            // Timing
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Metrics
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('click_count')->default(0);
            $table->unsignedInteger('share_count')->default(0);

            // Financial
            $table->decimal('offered_price', 15, 2)->nullable();
            $table->decimal('final_price', 15, 2)->nullable();
            $table->decimal('discount_amount', 15, 2)->nullable();
            $table->string('discount_code')->nullable();

            // Additional data
            $table->json('metadata')->nullable(); // Extra tracking data
            $table->json('utm_data')->nullable(); // UTM parameters
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // Unique tracking code for public links
            $table->string('tracking_code', 32)->unique();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('offer_id')->references('id')->on('offers')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('telegram_user_id')->references('id')->on('telegram_users')->onDelete('set null');

            // Indexes
            $table->index(['business_id', 'status']);
            $table->index(['lead_id', 'status']);
            $table->index(['offer_id', 'status']);
            $table->index('tracking_code');
            $table->index('sent_at');
            $table->index('converted_at');
        });

        // Offer metrics aggregation table
        Schema::create('offer_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('offer_id');
            $table->uuid('business_id');
            $table->date('date');

            // Daily metrics
            $table->unsignedInteger('sends_count')->default(0);
            $table->unsignedInteger('deliveries_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('unique_views_count')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->unsignedInteger('unique_clicks_count')->default(0);
            $table->unsignedInteger('conversions_count')->default(0);
            $table->unsignedInteger('rejections_count')->default(0);

            // Financial metrics
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('total_discounts', 15, 2)->default(0);

            // Rates (calculated)
            $table->decimal('delivery_rate', 5, 2)->default(0);
            $table->decimal('view_rate', 5, 2)->default(0);
            $table->decimal('click_rate', 5, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);

            $table->timestamps();

            // Foreign keys
            $table->foreign('offer_id')->references('id')->on('offers')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');

            // Unique constraint
            $table->unique(['offer_id', 'date']);
            $table->index(['business_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_metrics');
        Schema::dropIfExists('offer_lead_assignments');
    }
};
