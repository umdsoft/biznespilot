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
        Schema::table('meta_insights', function (Blueprint $table) {
            // Extended metrics from Meta API
            if (! Schema::hasColumn('meta_insights', 'link_clicks')) {
                $table->integer('link_clicks')->nullable()->after('clicks');
            }
            if (! Schema::hasColumn('meta_insights', 'cpp')) {
                $table->decimal('cpp', 15, 6)->nullable()->after('cpm');
            }
            if (! Schema::hasColumn('meta_insights', 'unique_ctr')) {
                $table->decimal('unique_ctr', 10, 6)->nullable()->after('ctr');
            }
            if (! Schema::hasColumn('meta_insights', 'post_engagement')) {
                $table->integer('post_engagement')->nullable()->after('spend');
            }
            if (! Schema::hasColumn('meta_insights', 'page_engagement')) {
                $table->integer('page_engagement')->nullable()->after('post_engagement');
            }
            if (! Schema::hasColumn('meta_insights', 'post_reactions')) {
                $table->integer('post_reactions')->nullable()->after('page_engagement');
            }
            if (! Schema::hasColumn('meta_insights', 'post_comments')) {
                $table->integer('post_comments')->nullable()->after('post_reactions');
            }
            if (! Schema::hasColumn('meta_insights', 'post_shares')) {
                $table->integer('post_shares')->nullable()->after('post_comments');
            }
            if (! Schema::hasColumn('meta_insights', 'post_saves')) {
                $table->integer('post_saves')->nullable()->after('post_shares');
            }
            if (! Schema::hasColumn('meta_insights', 'video_views')) {
                $table->integer('video_views')->nullable()->after('post_saves');
            }
            if (! Schema::hasColumn('meta_insights', 'video_views_p25')) {
                $table->integer('video_views_p25')->nullable()->after('video_views');
            }
            if (! Schema::hasColumn('meta_insights', 'video_views_p50')) {
                $table->integer('video_views_p50')->nullable()->after('video_views_p25');
            }
            if (! Schema::hasColumn('meta_insights', 'video_views_p75')) {
                $table->integer('video_views_p75')->nullable()->after('video_views_p50');
            }
            if (! Schema::hasColumn('meta_insights', 'video_views_p100')) {
                $table->integer('video_views_p100')->nullable()->after('video_views_p75');
            }
            if (! Schema::hasColumn('meta_insights', 'conversion_value')) {
                $table->decimal('conversion_value', 15, 2)->nullable()->after('conversions');
            }
            if (! Schema::hasColumn('meta_insights', 'action_values')) {
                $table->json('action_values')->nullable()->after('actions');
            }
            if (! Schema::hasColumn('meta_insights', 'cost_per_action_type')) {
                $table->json('cost_per_action_type')->nullable()->after('action_values');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meta_insights', function (Blueprint $table) {
            $table->dropColumn([
                'link_clicks', 'cpp', 'unique_ctr',
                'post_engagement', 'page_engagement', 'post_reactions',
                'post_comments', 'post_shares', 'post_saves',
                'video_views', 'video_views_p25', 'video_views_p50',
                'video_views_p75', 'video_views_p100',
                'conversion_value', 'action_values', 'cost_per_action_type',
            ]);
        });
    }
};
