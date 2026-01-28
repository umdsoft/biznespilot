<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lost Opportunities jadvalini yaratish.
 *
 * Bu jadval yo'qotilgan lidlarni va ularning marketing attribution
 * ma'lumotlarini saqlaydi. "Black Box" konsepsiyasi uchun - qancha pul
 * yo'qotilganini kuzatish.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lost_opportunities')) {
            return;
        }

        Schema::create('lost_opportunities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('lead_id')->constrained()->cascadeOnDelete();

            // Responsible user (kim yo'qotdi)
            $table->foreignUuid('lost_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            // === MARKETING ATTRIBUTION (Lead dan meros) ===
            $table->foreignUuid('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignUuid('marketing_channel_id')->nullable()->constrained('marketing_channels')->nullOnDelete();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreign('source_id')->references('id')->on('lead_sources')->nullOnDelete();

            // UTM Parameters
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 255)->nullable();
            $table->string('utm_content', 255)->nullable();
            $table->string('utm_term', 255)->nullable();

            // Attribution source type (digital, offline, organic, referral)
            $table->string('attribution_source_type', 50)->nullable();

            // === FINANCIAL IMPACT (asosiy metrika) ===
            $table->decimal('estimated_value', 15, 2)->default(0)->comment('Kutilgan daromad');
            $table->decimal('acquisition_cost', 15, 2)->default(0)->comment('Lid olishga sarflangan xarajat');
            $table->string('currency', 3)->default('UZS');

            // === LOSS TRACKING ===
            $table->string('lost_reason', 50)->comment('Yo\'qotish sababi kodi');
            $table->text('lost_reason_details')->nullable()->comment('Batafsil sabab');
            $table->string('lost_stage', 50)->nullable()->comment('Qaysi bosqichda yo\'qotildi');
            $table->timestamp('lost_at')->useCurrent();

            // === COMPETITOR TRACKING ===
            $table->string('lost_to_competitor')->nullable()->comment('Qaysi raqobatchiga yo\'qotildi');
            $table->text('competitor_notes')->nullable();

            // === RECOVERY TRACKING ===
            $table->boolean('is_recoverable')->default(true)->comment('Qayta urinib ko\'rish mumkinmi');
            $table->integer('recovery_attempts')->default(0);
            $table->timestamp('last_recovery_attempt_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->foreignUuid('recovered_lead_id')->nullable()->comment('Agar qayta ochilsa - yangi Lead ID');

            // === ANALYSIS ===
            $table->text('lessons_learned')->nullable()->comment('Xulosalar');
            $table->json('data')->nullable()->comment('Qo\'shimcha ma\'lumotlar');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for analytics (shortened names for MySQL 64 char limit)
            $table->index(['business_id', 'lost_at'], 'lo_biz_lost_idx');
            $table->index(['business_id', 'campaign_id', 'lost_at'], 'lo_biz_camp_lost_idx');
            $table->index(['business_id', 'marketing_channel_id', 'lost_at'], 'lo_biz_channel_lost_idx');
            $table->index(['business_id', 'lost_reason'], 'lo_biz_reason_idx');
            $table->index(['business_id', 'lost_to_competitor'], 'lo_biz_competitor_idx');
            $table->index(['business_id', 'is_recoverable'], 'lo_biz_recoverable_idx');
            $table->index(['business_id', 'utm_source', 'utm_medium'], 'lo_biz_utm_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_opportunities');
    }
};
