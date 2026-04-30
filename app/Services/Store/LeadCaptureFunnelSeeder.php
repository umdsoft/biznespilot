<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\Store\TelegramStore;
use App\Models\TelegramBot;
use App\Models\TelegramFunnel;
use App\Models\TelegramFunnelStep;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * LeadCaptureFunnelSeeder
 *
 * 'leadcapture' turidagi store/bot uchun default funnel yaratadi:
 * /start → ism → telefon → izoh (ixtiyoriy) → Lead yaratish + Owner'ga DM → rahmat.
 *
 * Funnel'ni qachon seed qilamiz?
 *  - Store activate bo'lganda, agar bot.default_funnel_id hali bo'sh bo'lsa.
 *
 * Mavjud `create_lead` va `send_notification` action_type'lar ishlatamiz —
 * FunnelEngineService ularni allaqachon tushunadi (Lead.create + business
 * user'lariga System Bot orqali DM).
 */
class LeadCaptureFunnelSeeder
{
    /**
     * Seed default lead-capture funnel for the bot if not already set.
     * Idempotent: agar bot.default_funnel_id mavjud bo'lsa — hech nima qilmaydi.
     */
    public function seedForStore(TelegramStore $store): ?TelegramFunnel
    {
        if ($store->store_type !== 'leadcapture') {
            return null;
        }

        $bot = $store->telegramBot;
        if (! $bot) {
            Log::warning('[LeadCaptureFunnelSeeder] No bot linked to store', [
                'store_id' => $store->id,
            ]);
            return null;
        }

        // Idempotent: allaqachon default funnel bo'lsa — qaytaramiz, qayta yaratmaymiz
        if ($bot->default_funnel_id) {
            return TelegramFunnel::find($bot->default_funnel_id);
        }

        return DB::transaction(function () use ($bot, $store) {
            $funnel = TelegramFunnel::create([
                'business_id' => $bot->business_id,
                'telegram_bot_id' => $bot->id,
                'name' => 'Lead capture (default)',
                'slug' => 'lead-capture-' . Str::random(6),
                'type' => 'custom',
                'is_active' => true,
                'priority' => 100,
                'description' => 'Auto-generated default funnel for lead-capture bot. /start bosgan foydalanuvchidan ism+telefon yig\'adi va Lead modelda yozadi.',
                'completion_message' => null, // we add it as the last step explicitly
                'settings' => [
                    'auto_seeded' => true,
                    'seeded_for_store_type' => 'leadcapture',
                ],
            ]);

            $steps = $this->createSteps($funnel);

            // first_step_id ni o'rnatamiz — engine shu yerdan boshlaydi
            $funnel->update(['first_step_id' => $steps['welcome']->id]);

            // Bot uchun default funnelni shuni belgilaymiz
            $bot->update(['default_funnel_id' => $funnel->id]);

            Log::info('[LeadCaptureFunnelSeeder] Default funnel seeded', [
                'business_id' => $bot->business_id,
                'bot_id' => $bot->id,
                'funnel_id' => $funnel->id,
                'steps_count' => count($steps),
            ]);

            return $funnel;
        });
    }

    /**
     * Steps zanjirini yaratamiz va next_step_id'larni bog'laymiz.
     *
     * Tartib:
     *  welcome → ask_name → ask_phone → ask_message → create_lead → notify_owner → thank_you
     */
    protected function createSteps(TelegramFunnel $funnel): array
    {
        // Avval barcha step'larni yaratamiz (next_step_id null bilan), keyin bog'laymiz.
        $welcome = TelegramFunnelStep::create([
            'funnel_id' => $funnel->id,
            'name' => 'Welcome',
            'slug' => 'welcome',
            'order' => 1,
            'step_type' => 'message',
            'content' => [
                'type' => 'text',
                'text' => "Assalomu alaykum! 👋\n\nBu yerda kerakli ma'lumotlaringizni qoldirsangiz, biz tez orada siz bilan bog'lanamiz.",
            ],
        ]);

        $askName = TelegramFunnelStep::create([
            'funnel_id' => $funnel->id,
            'name' => 'Ask name',
            'slug' => 'ask-name',
            'order' => 2,
            'step_type' => 'input',
            'input_type' => 'text',
            'input_field' => 'name',
            'content' => [
                'type' => 'text',
                'text' => "Ismingizni yozing:",
            ],
            'validation' => [
                'required' => true,
                'min' => 2,
                'max' => 100,
            ],
        ]);

        $askPhone = TelegramFunnelStep::create([
            'funnel_id' => $funnel->id,
            'name' => 'Ask phone',
            'slug' => 'ask-phone',
            'order' => 3,
            'step_type' => 'input',
            'input_type' => 'phone',
            'input_field' => 'phone',
            'content' => [
                'type' => 'text',
                'text' => "Telefon raqamingizni yozing yoki tugma orqali yuboring:",
            ],
            'keyboard' => [
                'type' => 'reply',
                'buttons' => [
                    [['text' => '📱 Telefon yuborish', 'request_contact' => true]],
                ],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ],
            'validation' => [
                'required' => true,
            ],
        ]);

        $askMessage = TelegramFunnelStep::create([
            'funnel_id' => $funnel->id,
            'name' => 'Ask message',
            'slug' => 'ask-message',
            'order' => 4,
            'step_type' => 'input',
            'input_type' => 'text',
            'input_field' => 'message',
            'content' => [
                'type' => 'text',
                'text' => "Qanday savol yoki murojaat bor? (Ixtiyoriy — \"yo'q\" deb yozsangiz ham bo'ladi)",
            ],
            'validation' => [
                'required' => false,
                'max' => 1000,
            ],
        ]);

        $createLead = TelegramFunnelStep::create([
            'funnel_id' => $funnel->id,
            'name' => 'Create lead',
            'slug' => 'create-lead',
            'order' => 5,
            'step_type' => 'action',
            'action_type' => 'create_lead',
            'action_config' => [
                'name_field' => 'name',
                'phone_field' => 'phone',
                'email_field' => 'email', // bo'sh — ixtiyoriy
            ],
        ]);

        $notifyOwner = TelegramFunnelStep::create([
            'funnel_id' => $funnel->id,
            'name' => 'Notify owner',
            'slug' => 'notify-owner',
            'order' => 6,
            'step_type' => 'action',
            'action_type' => 'send_notification',
            'action_config' => [
                'message' => "🆕 Yangi lead botdan keldi!",
                'include_collected_data' => true, // ism/telefon/izoh xabarga qo'shiladi
            ],
        ]);

        $thankYou = TelegramFunnelStep::create([
            'funnel_id' => $funnel->id,
            'name' => 'Thank you',
            'slug' => 'thank-you',
            'order' => 7,
            'step_type' => 'message',
            'content' => [
                'type' => 'text',
                'text' => "Rahmat! ✅\n\nMa'lumotlaringizni qabul qildik. Tez orada siz bilan bog'lanamiz.",
            ],
        ]);

        // Endi next_step_id zanjirini bog'laymiz
        $welcome->update(['next_step_id' => $askName->id]);
        $askName->update(['next_step_id' => $askPhone->id]);
        $askPhone->update(['next_step_id' => $askMessage->id]);
        $askMessage->update(['next_step_id' => $createLead->id]);
        $createLead->update(['next_step_id' => $notifyOwner->id]);
        $notifyOwner->update(['next_step_id' => $thankYou->id]);
        // thankYou — terminal, next_step_id null → engine completeFunnel() chaqiradi

        return [
            'welcome' => $welcome,
            'ask_name' => $askName,
            'ask_phone' => $askPhone,
            'ask_message' => $askMessage,
            'create_lead' => $createLead,
            'notify_owner' => $notifyOwner,
            'thank_you' => $thankYou,
        ];
    }
}
