<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plans jadvaliga limits JSON ustunini qo'shish
 *
 * Bu migration barcha raqamli limitlarni bitta JSON ustuniga o'tkazadi
 * Bu admin panelda dinamik boshqaruvga imkon beradi
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Limits JSON ustunini qo'shish
            if (!Schema::hasColumn('plans', 'limits')) {
                $table->json('limits')->nullable()->after('features');
            }
        });

        // Mavjud limitlarni JSON ga ko'chirish
        $plans = \DB::table('plans')->get();

        foreach ($plans as $plan) {
            $limits = [
                'users' => $plan->team_member_limit ?? null,
                'branches' => $plan->business_limit ?? null,
                'monthly_leads' => $plan->lead_limit ?? null,
                'chatbot_channels' => $plan->chatbot_channel_limit ?? null,
                'telegram_bots' => $plan->telegram_bot_limit ?? null,
                'ai_call_minutes' => $plan->audio_minutes_limit ?? null,
                'ai_requests' => $plan->ai_requests_limit ?? null,
                'storage_mb' => $plan->storage_limit_mb ?? null,
                'instagram_dm' => $plan->instagram_dm_limit ?? null,
                'content_posts' => $plan->content_posts_limit ?? null,
                'extra_call_price' => null,
                'instagram_accounts' => null,
            ];

            // features ga boolean qiymatlarni qo'shish
            $existingFeatures = json_decode($plan->features ?? '{}', true) ?: [];
            $features = array_merge([
                'flow_builder' => true,
                'marketing_roi' => false,
                'hr_tasks' => false,
                'hr_bot' => false,
                'anti_fraud' => false,
                'api_access' => false,
                'amocrm' => $plan->has_amocrm ?? false,
                'instagram' => $plan->has_instagram ?? false,
            ], $existingFeatures);

            \DB::table('plans')
                ->where('id', $plan->id)
                ->update([
                    'limits' => json_encode($limits),
                    'features' => json_encode($features),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'limits')) {
                $table->dropColumn('limits');
            }
        });
    }
};
