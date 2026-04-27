<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Content Calendar avtomatik aniqlash tizimi uchun maydonlar.
 *
 * - auto_hashtag       — sistema generatsiya qilgan unique hashtag (#brand_topic_a3f9)
 * - match_method       — qaysi yo'l bilan match topildi: direct/hashtag/watermark/fuzzy/manual
 * - match_score        — fuzzy uchun aniqlik darajasi (0..1)
 * - matched_post_text  — debug uchun: qaysi matn bilan match qilindi
 * - matched_post_id    — qaysi tashqi post bilan match qilindi (TelegramChannelPost.id va h.k.)
 * - matched_at         — match topilgan vaqt
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_calendar', function (Blueprint $table) {
            $table->string('auto_hashtag', 80)->nullable()->after('hashtags');
            $table->string('match_method', 20)->nullable()->after('auto_hashtag');
            $table->decimal('match_score', 4, 3)->nullable()->after('match_method');
            $table->string('matched_post_id', 64)->nullable()->after('match_score');
            $table->text('matched_post_text')->nullable()->after('matched_post_id');
            $table->timestamp('matched_at')->nullable()->after('matched_post_text');

            $table->index(['business_id', 'auto_hashtag']);
            $table->index(['business_id', 'status', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::table('content_calendar', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'auto_hashtag']);
            $table->dropIndex(['business_id', 'status', 'scheduled_date']);
            $table->dropColumn([
                'auto_hashtag',
                'match_method',
                'match_score',
                'matched_post_id',
                'matched_post_text',
                'matched_at',
            ]);
        });
    }
};
