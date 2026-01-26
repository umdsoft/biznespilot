<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Instagram Content Links - Kontent Reja va Instagram Post Bog'lovchisi
 *
 * Bu jadval content_calendar (rejadagi post) va haqiqiy Instagram postlar
 * o'rtasidagi "ko'prik" vazifasini bajaradi.
 *
 * Flow:
 * 1. Kontent reja yaratiladi (content_calendar)
 * 2. Post Instagramga yuklanadi
 * 3. Bu jadvalda bog'lanish yaratiladi
 * 4. Statistika muntazam sinxronlanadi
 *
 * @see SyncContentPerformanceJob
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_content_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('instagram_account_id')->nullable();

            // Kontent Reja bog'lanishi
            $table->uuid('content_calendar_id')->nullable();
            $table->uuid('content_idea_id')->nullable();

            // Instagram Media identifikatorlari
            $table->string('instagram_media_id', 100)->unique();
            $table->string('media_type', 20)->default('post'); // post, reel, story, carousel, igtv
            $table->string('permalink', 500)->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->text('caption')->nullable();

            // Media metadata
            $table->timestamp('posted_at')->nullable();
            $table->string('shortcode', 50)->nullable(); // Instagram shortcode (URL da ishlatiladigan)

            // ========================================
            // STATISTIKA (Instagram Insights dan sinxronlanadi)
            // ========================================

            // Asosiy metrikalar
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('likes')->default(0);
            $table->unsignedBigInteger('comments')->default(0);
            $table->unsignedBigInteger('shares')->default(0);
            $table->unsignedBigInteger('saves')->default(0);

            // Insights (Reel/Video uchun)
            $table->unsignedBigInteger('reach')->default(0);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('plays')->default(0); // Video plays
            $table->unsignedBigInteger('replays')->default(0);

            // Hisoblangan metrikalar
            $table->decimal('engagement_rate', 8, 4)->default(0);
            $table->decimal('save_rate', 8, 4)->default(0);
            $table->decimal('share_rate', 8, 4)->default(0);

            // Performance scoring
            $table->unsignedTinyInteger('performance_score')->default(0); // 0-100
            $table->boolean('is_top_performer')->default(false);

            // ========================================
            // SINXRONIZATSIYA
            // ========================================
            $table->string('sync_status', 20)->default('pending'); // pending, synced, failed
            $table->timestamp('last_synced_at')->nullable();
            $table->text('sync_error')->nullable();
            $table->unsignedSmallInteger('sync_attempts')->default(0);

            // Linking metadata
            $table->string('link_type', 20)->default('auto'); // auto, manual
            $table->string('match_method', 30)->nullable(); // exact, fuzzy, date, manual
            $table->decimal('match_confidence', 5, 2)->nullable(); // 0.00 - 100.00

            $table->timestamps();
            $table->softDeletes();

            // Indekslar
            $table->index('business_id');
            $table->index('instagram_account_id');
            $table->index('content_calendar_id');
            $table->index('content_idea_id');
            $table->index('media_type');
            $table->index('posted_at');
            $table->index('sync_status');
            $table->index('is_top_performer');
            $table->index(['business_id', 'posted_at']);
        });

        // Foreign Keys
        Schema::table('instagram_content_links', function (Blueprint $table) {
            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->cascadeOnDelete();

            if (Schema::hasTable('instagram_accounts')) {
                $table->foreign('instagram_account_id')
                    ->references('id')
                    ->on('instagram_accounts')
                    ->nullOnDelete();
            }

            if (Schema::hasTable('content_calendar')) {
                $table->foreign('content_calendar_id')
                    ->references('id')
                    ->on('content_calendar')
                    ->nullOnDelete();
            }

            if (Schema::hasTable('content_ideas')) {
                $table->foreign('content_idea_id')
                    ->references('id')
                    ->on('content_ideas')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_content_links');
    }
};
