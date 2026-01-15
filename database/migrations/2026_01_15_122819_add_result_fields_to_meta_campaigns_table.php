<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meta_campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('total_leads')->default(0)->after('total_conversions');
            $table->unsignedBigInteger('total_purchases')->default(0)->after('total_leads');
            $table->unsignedBigInteger('total_messages')->default(0)->after('total_purchases');
            $table->unsignedBigInteger('total_link_clicks')->default(0)->after('total_messages');
            $table->unsignedBigInteger('total_video_views')->default(0)->after('total_link_clicks');
            $table->unsignedBigInteger('total_add_to_cart')->default(0)->after('total_video_views');
            $table->decimal('cost_per_lead', 10, 4)->default(0)->after('avg_ctr');
            $table->decimal('cost_per_purchase', 10, 4)->default(0)->after('cost_per_lead');
            $table->decimal('cost_per_message', 10, 4)->default(0)->after('cost_per_purchase');
        });
    }

    public function down(): void
    {
        Schema::table('meta_campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'total_leads',
                'total_purchases',
                'total_messages',
                'total_link_clicks',
                'total_video_views',
                'total_add_to_cart',
                'cost_per_lead',
                'cost_per_purchase',
                'cost_per_message',
            ]);
        });
    }
};
