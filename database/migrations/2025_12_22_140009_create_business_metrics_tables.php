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
        // Sales Metrics - joriy ko'rsatkichlar
        Schema::create('sales_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');

            // Lead hajmi
            $table->string('monthly_lead_volume', 50)->nullable(); // 0_10, 10_50, 50_200, 200_plus

            // Lead manbalari (JSON array)
            $table->json('lead_sources')->nullable(); // ['instagram', 'telegram', 'website', ...]

            // Lead sifati
            $table->string('lead_quality', 20)->nullable(); // low, medium, high

            // Sotuv hajmi
            $table->string('monthly_sales_volume', 50)->nullable(); // 0_10, 10_50, 50_100, 100_plus

            // O'rtacha chek
            $table->string('avg_deal_size')->nullable(); // so'm da, text sifatida

            // Sotuv davri
            $table->string('sales_cycle', 50)->nullable(); // same_day, 1_3_days, 1_week, etc.

            // Sotuv jamoasi
            $table->string('sales_team_type', 50)->nullable(); // owner_only, small_team, medium_team, large_team

            // Ishlatilayotgan vositalar (JSON array)
            $table->json('sales_tools')->nullable(); // ['excel', 'crm', 'telegram_bot', ...]

            // Qiyinchiliklar
            $table->text('sales_challenges')->nullable();

            // Qo'shimcha ma'lumotlar
            $table->json('additional_data')->nullable();

            $table->timestamps();

            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('cascade');

            $table->unique('business_id');
        });

        // Sales Metrics History - tarixiy o'zgarishlar
        Schema::create('sales_metrics_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('sales_metrics_id')->nullable();

            // Barcha maydonlar nusxasi
            $table->string('monthly_lead_volume', 50)->nullable();
            $table->json('lead_sources')->nullable();
            $table->string('lead_quality', 20)->nullable();
            $table->string('monthly_sales_volume', 50)->nullable();
            $table->string('avg_deal_size')->nullable();
            $table->string('sales_cycle', 50)->nullable();
            $table->string('sales_team_type', 50)->nullable();
            $table->json('sales_tools')->nullable();
            $table->text('sales_challenges')->nullable();
            $table->json('additional_data')->nullable();

            // Qachon saqlangan
            $table->timestamp('recorded_at');

            // O'zgarish turi
            $table->string('change_type', 20)->default('update'); // initial, update

            // Izoh (ixtiyoriy)
            $table->string('note')->nullable();

            $table->timestamps();

            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('cascade');

            $table->index(['business_id', 'recorded_at']);
        });

        // Marketing Metrics - joriy ko'rsatkichlar
        Schema::create('marketing_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');

            // Byudjet
            $table->string('monthly_budget')->nullable(); // so'm da
            $table->string('ad_spend')->nullable(); // reklama xarajatlari

            // Web-sayt
            $table->string('website_purpose', 50)->nullable(); // lead_generation, ecommerce, info_brand, no_website
            $table->integer('monthly_visits')->nullable();
            $table->decimal('website_conversion', 5, 2)->nullable(); // foiz

            // Faol kanallar (JSON array)
            $table->json('active_channels')->nullable(); // ['instagram', 'telegram', 'google_ads', ...]

            // Eng samarali kanallar
            $table->string('best_channel', 50)->nullable();
            $table->string('top_lead_channel', 50)->nullable();

            // Ijtimoiy tarmoq statistikasi
            $table->integer('instagram_followers')->nullable();
            $table->integer('telegram_subscribers')->nullable();
            $table->integer('facebook_followers')->nullable();

            // ROI
            $table->string('roi_tracking_level', 20)->nullable(); // yes, partially, no
            $table->decimal('marketing_roi', 8, 2)->nullable(); // foiz

            // Kontent faoliyati (JSON array)
            $table->json('content_activities')->nullable(); // ['blog', 'videos', 'reels', ...]

            // Qiyinchiliklar
            $table->text('marketing_challenges')->nullable();

            // Qo'shimcha ma'lumotlar
            $table->json('additional_data')->nullable();

            $table->timestamps();

            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('cascade');

            $table->unique('business_id');
        });

        // Marketing Metrics History - tarixiy o'zgarishlar
        Schema::create('marketing_metrics_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('marketing_metrics_id')->nullable();

            // Barcha maydonlar nusxasi
            $table->string('monthly_budget')->nullable();
            $table->string('ad_spend')->nullable();
            $table->string('website_purpose', 50)->nullable();
            $table->integer('monthly_visits')->nullable();
            $table->decimal('website_conversion', 5, 2)->nullable();
            $table->json('active_channels')->nullable();
            $table->string('best_channel', 50)->nullable();
            $table->string('top_lead_channel', 50)->nullable();
            $table->integer('instagram_followers')->nullable();
            $table->integer('telegram_subscribers')->nullable();
            $table->integer('facebook_followers')->nullable();
            $table->string('roi_tracking_level', 20)->nullable();
            $table->decimal('marketing_roi', 8, 2)->nullable();
            $table->json('content_activities')->nullable();
            $table->text('marketing_challenges')->nullable();
            $table->json('additional_data')->nullable();

            // Qachon saqlangan
            $table->timestamp('recorded_at');

            // O'zgarish turi
            $table->string('change_type', 20)->default('update'); // initial, update

            // Izoh (ixtiyoriy)
            $table->string('note')->nullable();

            $table->timestamps();

            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('cascade');

            $table->index(['business_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_metrics_history');
        Schema::dropIfExists('marketing_metrics');
        Schema::dropIfExists('sales_metrics_history');
        Schema::dropIfExists('sales_metrics');
    }
};
