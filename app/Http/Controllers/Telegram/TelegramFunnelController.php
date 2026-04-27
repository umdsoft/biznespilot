<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\TelegramBot;
use App\Models\TelegramFunnel;
use App\Models\TelegramFunnelStep;
use App\Models\TelegramUser;
use App\Services\Telegram\FunnelEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TelegramFunnelController extends Controller
{
    use HasCurrentBusiness;

    /**
     * List all funnels for a bot
     */
    public function index(Request $request, string $botId): InertiaResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $funnels = TelegramFunnel::where('telegram_bot_id', $bot->id)
            ->withCount('steps')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($funnel) => [
                'id' => $funnel->id,
                'name' => $funnel->name,
                'description' => $funnel->description,
                'is_active' => $funnel->is_active,
                'steps_count' => $funnel->steps_count,
                'created_at' => $funnel->created_at->format('d.m.Y'),
            ]);

        return Inertia::render('Business/Telegram/Funnels/Index', [
            'bot' => [
                'id' => $bot->id,
                'username' => $bot->bot_username,
                'first_name' => $bot->bot_first_name,
            ],
            'funnels' => $funnels,
            'panelType' => $this->detectPanelType($business),
        ]);
    }

    /**
     * Show funnel builder
     */
    public function show(Request $request, string $botId, string $funnelId): InertiaResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $funnel = TelegramFunnel::where('telegram_bot_id', $bot->id)
            ->where('id', $funnelId)
            ->with('steps')
            ->firstOrFail();

        $steps = $funnel->steps->map(fn ($step) => [
            'id' => $step->id,
            'name' => $step->name,
            'step_type' => $step->step_type,
            'content' => $step->content,
            'keyboard' => $step->keyboard,
            'input_type' => $step->input_type,
            'input_field' => $step->input_field,
            'validation' => $step->validation,
            'next_step_id' => $step->next_step_id,
            'action_type' => $step->action_type,
            'action_config' => $step->action_config,
            'condition' => $step->condition,
            'condition_true_step_id' => $step->condition_true_step_id,
            'condition_false_step_id' => $step->condition_false_step_id,
            'position_x' => $step->position_x,
            'position_y' => $step->position_y,
            'order' => $step->order,
            // Marketing features
            'subscribe_check' => $step->subscribe_check,
            'subscribe_true_step_id' => $step->subscribe_true_step_id,
            'subscribe_false_step_id' => $step->subscribe_false_step_id,
            'quiz' => $step->quiz,
            'ab_test' => $step->ab_test,
            'tag' => $step->tag,
            'trigger' => $step->trigger,
            'delay_seconds' => (int) floor(((int) $step->delay_ms) / 1000),
        ]);

        return Inertia::render('Business/Telegram/Funnels/Builder', [
            'bot' => [
                'id' => $bot->id,
                'username' => $bot->bot_username,
                'first_name' => $bot->bot_first_name,
            ],
            'funnel' => [
                'id' => $funnel->id,
                'name' => $funnel->name,
                'description' => $funnel->description,
                'is_active' => $funnel->is_active,
                'first_step_id' => $funnel->first_step_id,
                'completion_message' => $funnel->completion_message,
            ],
            'steps' => $steps,
            'panelType' => $this->detectPanelType($business),
        ]);
    }

    /**
     * Create new funnel
     */
    public function store(Request $request, string $botId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'template' => 'nullable|string|in:blank,lead_magnet,consultation,quiz,subscribe_gate,ab_offer',
        ]);

        $funnel = TelegramFunnel::create([
            'business_id' => $business->id,
            'telegram_bot_id' => $bot->id,
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->name).'-'.\Illuminate\Support\Str::random(6),
            'description' => $request->description,
            'is_active' => false,
        ]);

        // Create template steps if template selected
        $template = $request->input('template', 'blank');
        if ($template !== 'blank') {
            $this->createTemplateSteps($funnel, $template);
        }

        return response()->json([
            'success' => true,
            'funnel' => [
                'id' => $funnel->id,
                'name' => $funnel->name,
            ],
            'message' => 'Funnel yaratildi',
        ]);
    }

    /**
     * Create steps from template
     */
    protected function createTemplateSteps(TelegramFunnel $funnel, string $template): void
    {
        $templates = $this->getFunnelTemplates();

        if (! isset($templates[$template])) {
            return;
        }

        $templateData = $templates[$template];
        $stepIdMap = [];

        // First pass: create all steps
        foreach ($templateData['steps'] as $index => $stepData) {
            $step = TelegramFunnelStep::create([
                'funnel_id' => $funnel->id,
                'name' => $stepData['name'],
                'slug' => \Illuminate\Support\Str::slug($stepData['name']).'-'.\Illuminate\Support\Str::random(6),
                'step_type' => $stepData['step_type'],
                'content' => $stepData['content'] ?? ['type' => 'text', 'text' => ''],
                'keyboard' => $stepData['keyboard'] ?? null,
                'input_type' => $stepData['input_type'] ?? 'none',
                'input_field' => $stepData['input_field'] ?? null,
                'action_type' => $stepData['action_type'] ?? 'none',
                'action_config' => $stepData['action_config'] ?? null,
                'condition' => $stepData['condition'] ?? null,
                'subscribe_check' => $stepData['subscribe_check'] ?? null,
                'quiz' => $stepData['quiz'] ?? null,
                'ab_test' => $stepData['ab_test'] ?? null,
                'tag' => $stepData['tag'] ?? null,
                'position_x' => $stepData['position_x'] ?? 250,
                'position_y' => $stepData['position_y'] ?? ($index * 150),
                'order' => $index,
            ]);

            $stepIdMap[$stepData['temp_id'] ?? $index] = $step->id;
        }

        // Second pass: update step references
        foreach ($templateData['steps'] as $index => $stepData) {
            $stepId = $stepIdMap[$stepData['temp_id'] ?? $index];
            $updateData = [];

            if (isset($stepData['next_step_ref']) && isset($stepIdMap[$stepData['next_step_ref']])) {
                $updateData['next_step_id'] = $stepIdMap[$stepData['next_step_ref']];
            }

            if (isset($stepData['condition_true_step_ref']) && isset($stepIdMap[$stepData['condition_true_step_ref']])) {
                $updateData['condition_true_step_id'] = $stepIdMap[$stepData['condition_true_step_ref']];
            }

            if (isset($stepData['condition_false_step_ref']) && isset($stepIdMap[$stepData['condition_false_step_ref']])) {
                $updateData['condition_false_step_id'] = $stepIdMap[$stepData['condition_false_step_ref']];
            }

            if (isset($stepData['subscribe_true_step_ref']) && isset($stepIdMap[$stepData['subscribe_true_step_ref']])) {
                $updateData['subscribe_true_step_id'] = $stepIdMap[$stepData['subscribe_true_step_ref']];
            }

            if (isset($stepData['subscribe_false_step_ref']) && isset($stepIdMap[$stepData['subscribe_false_step_ref']])) {
                $updateData['subscribe_false_step_id'] = $stepIdMap[$stepData['subscribe_false_step_ref']];
            }

            // Handle quiz options
            if (isset($stepData['quiz']['options'])) {
                $quiz = $stepData['quiz'];
                foreach ($quiz['options'] as $i => $option) {
                    if (isset($option['next_step_ref']) && isset($stepIdMap[$option['next_step_ref']])) {
                        $quiz['options'][$i]['next_step_id'] = $stepIdMap[$option['next_step_ref']];
                    }
                }
                $updateData['quiz'] = $quiz;
            }

            // Handle A/B test variants
            if (isset($stepData['ab_test']['variants'])) {
                $abTest = $stepData['ab_test'];
                foreach ($abTest['variants'] as $i => $variant) {
                    if (isset($variant['next_step_ref']) && isset($stepIdMap[$variant['next_step_ref']])) {
                        $abTest['variants'][$i]['next_step_id'] = $stepIdMap[$variant['next_step_ref']];
                    }
                }
                $updateData['ab_test'] = $abTest;
            }

            if (! empty($updateData)) {
                TelegramFunnelStep::where('id', $stepId)->update($updateData);
            }
        }

        // Set first step
        $firstStepId = $stepIdMap[$templateData['first_step_ref'] ?? 0] ?? null;
        if ($firstStepId) {
            $funnel->update(['first_step_id' => $firstStepId]);
        }
    }

    /**
     * Get funnel templates
     */
    protected function getFunnelTemplates(): array
    {
        return [
            // 1. Lead Magnet - Bepul material + kontakt
            'lead_magnet' => [
                'first_step_ref' => 'welcome',
                'steps' => [
                    [
                        'temp_id' => 'welcome',
                        'name' => 'Xush kelibsiz',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "Assalomu alaykum, {first_name}! 👋\n\nSizga BEPUL [Material nomi] taqdim etamiz!\n\n📚 Bu materialda siz:\n• Birinchi foyda\n• Ikkinchi foyda\n• Uchinchi foyda\n\nolishingiz mumkin.",
                        ],
                        'keyboard' => [
                            'type' => 'inline',
                            'buttons' => [
                                [['text' => '📥 Bepul olish', 'action_type' => 'next_step']],
                            ],
                        ],
                        'next_step_ref' => 'ask_phone',
                        'position_x' => 250,
                        'position_y' => 0,
                    ],
                    [
                        'temp_id' => 'ask_phone',
                        'name' => 'Telefon so\'rash',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "📱 Materialni olish uchun telefon raqamingizni yuboring.\n\nBu orqali siz bilan bog'lanishimiz mumkin bo'ladi.",
                        ],
                        'keyboard' => [
                            'type' => 'reply',
                            'buttons' => [
                                [['text' => '📱 Telefon raqamni yuborish', 'action_type' => 'request_contact']],
                            ],
                        ],
                        'input_type' => 'phone',
                        'input_field' => 'phone',
                        'next_step_ref' => 'tag_lead',
                        'position_x' => 250,
                        'position_y' => 150,
                    ],
                    [
                        'temp_id' => 'tag_lead',
                        'name' => 'Lead tegi',
                        'step_type' => 'tag',
                        'tag' => [
                            'action' => 'add',
                            'tags' => ['lead_magnet', 'new_lead'],
                        ],
                        'next_step_ref' => 'send_material',
                        'position_x' => 250,
                        'position_y' => 300,
                    ],
                    [
                        'temp_id' => 'send_material',
                        'name' => 'Material yuborish',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "✅ Rahmat!\n\n📚 Mana sizning materialingiz:\n👉 [Material havola]\n\n💡 Qo'shimcha savollar bo'lsa, yozing!",
                        ],
                        'action_type' => 'create_lead',
                        'position_x' => 250,
                        'position_y' => 450,
                    ],
                ],
            ],

            // 2. Konsultatsiya - Bepul maslahat
            'consultation' => [
                'first_step_ref' => 'welcome',
                'steps' => [
                    [
                        'temp_id' => 'welcome',
                        'name' => 'Xush kelibsiz',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "Assalomu alaykum, {first_name}! 👋\n\n🎯 BEPUL konsultatsiya olishni xohlaysizmi?\n\nBiz sizga yordam beramiz:\n• Muammolaringizni hal qilish\n• To'g'ri yo'nalish tanlash\n• Natijaga erishish",
                        ],
                        'keyboard' => [
                            'type' => 'inline',
                            'buttons' => [
                                [['text' => '✅ Ha, konsultatsiya olmoqchiman', 'action_type' => 'next_step']],
                            ],
                        ],
                        'next_step_ref' => 'ask_name',
                        'position_x' => 250,
                        'position_y' => 0,
                    ],
                    [
                        'temp_id' => 'ask_name',
                        'name' => 'Ism so\'rash',
                        'step_type' => 'input',
                        'content' => [
                            'type' => 'text',
                            'text' => '📝 Ismingizni kiriting:',
                        ],
                        'input_type' => 'text',
                        'input_field' => 'name',
                        'next_step_ref' => 'ask_phone',
                        'position_x' => 250,
                        'position_y' => 150,
                    ],
                    [
                        'temp_id' => 'ask_phone',
                        'name' => 'Telefon so\'rash',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "📱 Telefon raqamingizni yuboring.\n\nMutaxassisimiz siz bilan bog'lanadi.",
                        ],
                        'keyboard' => [
                            'type' => 'reply',
                            'buttons' => [
                                [['text' => '📱 Telefon yuborish', 'action_type' => 'request_contact']],
                            ],
                        ],
                        'input_type' => 'phone',
                        'input_field' => 'phone',
                        'next_step_ref' => 'ask_question',
                        'position_x' => 250,
                        'position_y' => 300,
                    ],
                    [
                        'temp_id' => 'ask_question',
                        'name' => 'Savol so\'rash',
                        'step_type' => 'input',
                        'content' => [
                            'type' => 'text',
                            'text' => "❓ Qanday savol yoki muammongiz bor?\n\nQisqacha yozing:",
                        ],
                        'input_type' => 'text',
                        'input_field' => 'question',
                        'next_step_ref' => 'confirm',
                        'position_x' => 250,
                        'position_y' => 450,
                    ],
                    [
                        'temp_id' => 'confirm',
                        'name' => 'Tasdiqlash',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "✅ Rahmat, {name}!\n\n📞 Mutaxassisimiz 24 soat ichida siz bilan bog'lanadi.\n\n⏰ Ish vaqti: 9:00 - 18:00",
                        ],
                        'action_type' => 'create_lead',
                        'position_x' => 250,
                        'position_y' => 600,
                    ],
                ],
            ],

            // 3. Quiz Funnel - Segmentatsiya
            'quiz' => [
                'first_step_ref' => 'welcome',
                'steps' => [
                    [
                        'temp_id' => 'welcome',
                        'name' => 'Xush kelibsiz',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "Assalomu alaykum, {first_name}! 🎯\n\n3 ta savolga javob bering va o'zingizga mos taklif oling!",
                        ],
                        'keyboard' => [
                            'type' => 'inline',
                            'buttons' => [
                                [['text' => '🚀 Boshlash', 'action_type' => 'next_step']],
                            ],
                        ],
                        'next_step_ref' => 'quiz1',
                        'position_x' => 250,
                        'position_y' => 0,
                    ],
                    [
                        'temp_id' => 'quiz1',
                        'name' => '1-savol',
                        'step_type' => 'quiz',
                        'quiz' => [
                            'question' => '1️⃣ Sizning asosiy maqsadingiz nima?',
                            'options' => [
                                ['text' => '💰 Daromadni oshirish', 'next_step_ref' => 'quiz2'],
                                ['text' => '⏰ Vaqtni tejash', 'next_step_ref' => 'quiz2'],
                                ['text' => '📈 Biznesni o\'stirish', 'next_step_ref' => 'quiz2'],
                            ],
                            'save_answer_to' => 'goal',
                        ],
                        'position_x' => 250,
                        'position_y' => 150,
                    ],
                    [
                        'temp_id' => 'quiz2',
                        'name' => '2-savol',
                        'step_type' => 'quiz',
                        'quiz' => [
                            'question' => '2️⃣ Hozirgi holatingiz qanday?',
                            'options' => [
                                ['text' => '🌱 Yangi boshladim', 'next_step_ref' => 'quiz3'],
                                ['text' => '📊 Tajribam bor', 'next_step_ref' => 'quiz3'],
                                ['text' => '🏆 Professional', 'next_step_ref' => 'quiz3'],
                            ],
                            'save_answer_to' => 'level',
                        ],
                        'position_x' => 250,
                        'position_y' => 300,
                    ],
                    [
                        'temp_id' => 'quiz3',
                        'name' => '3-savol',
                        'step_type' => 'quiz',
                        'quiz' => [
                            'question' => '3️⃣ Byudjetingiz qanday?',
                            'options' => [
                                ['text' => '💵 Kam (1-5 mln)', 'next_step_ref' => 'result_basic'],
                                ['text' => '💰 O\'rtacha (5-20 mln)', 'next_step_ref' => 'result_standard'],
                                ['text' => '💎 Yuqori (20+ mln)', 'next_step_ref' => 'result_premium'],
                            ],
                            'save_answer_to' => 'budget',
                        ],
                        'position_x' => 250,
                        'position_y' => 450,
                    ],
                    [
                        'temp_id' => 'result_basic',
                        'name' => 'Natija - Basic',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "✅ Sizga BASIC paket mos keladi!\n\n📦 Paket tarkibi:\n• Asosiy funksiyalar\n• Email qo'llab-quvvatlash\n• Boshlang'ich o'quv materiallar\n\n💰 Narx: 1 500 000 so'm",
                        ],
                        'keyboard' => [
                            'type' => 'inline',
                            'buttons' => [
                                [['text' => '📞 Bog\'lanish', 'action_type' => 'next_step']],
                            ],
                        ],
                        'next_step_ref' => 'collect_contact',
                        'position_x' => 50,
                        'position_y' => 600,
                    ],
                    [
                        'temp_id' => 'result_standard',
                        'name' => 'Natija - Standard',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "✅ Sizga STANDARD paket mos keladi!\n\n📦 Paket tarkibi:\n• Barcha funksiyalar\n• Telegram qo'llab-quvvatlash\n• Video darslar\n• 1 ta konsultatsiya\n\n💰 Narx: 5 000 000 so'm",
                        ],
                        'keyboard' => [
                            'type' => 'inline',
                            'buttons' => [
                                [['text' => '📞 Bog\'lanish', 'action_type' => 'next_step']],
                            ],
                        ],
                        'next_step_ref' => 'collect_contact',
                        'position_x' => 250,
                        'position_y' => 600,
                    ],
                    [
                        'temp_id' => 'result_premium',
                        'name' => 'Natija - Premium',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "✅ Sizga PREMIUM paket mos keladi!\n\n📦 Paket tarkibi:\n• VIP funksiyalar\n• Shaxsiy menejer\n• Cheksiz konsultatsiyalar\n• Priority qo'llab-quvvatlash\n\n💰 Narx: 20 000 000 so'm",
                        ],
                        'keyboard' => [
                            'type' => 'inline',
                            'buttons' => [
                                [['text' => '📞 Bog\'lanish', 'action_type' => 'next_step']],
                            ],
                        ],
                        'next_step_ref' => 'collect_contact',
                        'position_x' => 450,
                        'position_y' => 600,
                    ],
                    [
                        'temp_id' => 'collect_contact',
                        'name' => 'Kontakt yig\'ish',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "📱 Telefon raqamingizni yuboring.\n\nMutaxassisimiz siz bilan bog'lanadi!",
                        ],
                        'keyboard' => [
                            'type' => 'reply',
                            'buttons' => [
                                [['text' => '📱 Telefon yuborish', 'action_type' => 'request_contact']],
                            ],
                        ],
                        'input_type' => 'phone',
                        'input_field' => 'phone',
                        'next_step_ref' => 'thank_you',
                        'position_x' => 250,
                        'position_y' => 750,
                    ],
                    [
                        'temp_id' => 'thank_you',
                        'name' => 'Rahmat',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "✅ Rahmat!\n\n📞 Tez orada siz bilan bog'lanamiz.\n\n⏰ Ish vaqti: 9:00 - 18:00",
                        ],
                        'action_type' => 'create_lead',
                        'position_x' => 250,
                        'position_y' => 900,
                    ],
                ],
            ],

            // 4. Subscribe Gate - Kanalga obuna
            'subscribe_gate' => [
                'first_step_ref' => 'check_subscribe',
                'steps' => [
                    [
                        'temp_id' => 'check_subscribe',
                        'name' => 'Obuna tekshirish',
                        'step_type' => 'subscribe_check',
                        'subscribe_check' => [
                            'channel_username' => 'your_channel',
                            'not_subscribed_message' => "⚠️ Bonus olish uchun kanalimizga obuna bo'ling!\n\n👇 Obuna bo'ling va \"Tekshirish\" tugmasini bosing.",
                            'subscribe_button_text' => "📢 Kanalga obuna bo'lish",
                        ],
                        'subscribe_true_step_ref' => 'subscribed',
                        'subscribe_false_step_ref' => 'not_subscribed',
                        'position_x' => 250,
                        'position_y' => 0,
                    ],
                    [
                        'temp_id' => 'not_subscribed',
                        'name' => 'Obuna emas',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "❌ Siz hali kanalga obuna bo'lmagansiz.\n\nBonus olish uchun obuna bo'ling!",
                        ],
                        'position_x' => 50,
                        'position_y' => 150,
                    ],
                    [
                        'temp_id' => 'subscribed',
                        'name' => 'Obuna bo\'lgan',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "✅ Ajoyib, {first_name}!\n\nSiz kanalga obuna bo'lgansiz! 🎉\n\nMana sizning BONUSINGIZ:\n👉 [Bonus havola]",
                        ],
                        'keyboard' => [
                            'type' => 'inline',
                            'buttons' => [
                                [['text' => '🎁 Yana bonus olish', 'action_type' => 'next_step']],
                            ],
                        ],
                        'next_step_ref' => 'ask_phone',
                        'position_x' => 450,
                        'position_y' => 150,
                    ],
                    [
                        'temp_id' => 'ask_phone',
                        'name' => 'Telefon so\'rash',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "📱 Yana ko'proq bonus olish uchun telefon raqamingizni yuboring!",
                        ],
                        'keyboard' => [
                            'type' => 'reply',
                            'buttons' => [
                                [['text' => '📱 Telefon yuborish', 'action_type' => 'request_contact']],
                            ],
                        ],
                        'input_type' => 'phone',
                        'input_field' => 'phone',
                        'next_step_ref' => 'tag_subscriber',
                        'position_x' => 450,
                        'position_y' => 300,
                    ],
                    [
                        'temp_id' => 'tag_subscriber',
                        'name' => 'Obunachi tegi',
                        'step_type' => 'tag',
                        'tag' => [
                            'action' => 'add',
                            'tags' => ['subscriber', 'bonus_received'],
                        ],
                        'next_step_ref' => 'final',
                        'position_x' => 450,
                        'position_y' => 450,
                    ],
                    [
                        'temp_id' => 'final',
                        'name' => 'Yakuniy',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "🎉 Tabriklaymiz!\n\nSiz VIP mijozlar ro'yxatiga qo'shildingiz.\n\n📬 Maxsus takliflar olasiz!",
                        ],
                        'action_type' => 'create_lead',
                        'position_x' => 450,
                        'position_y' => 600,
                    ],
                ],
            ],

            // 5. A/B Test Offer - Ikki taklif solishtiruv
            'ab_offer' => [
                'first_step_ref' => 'welcome',
                'steps' => [
                    [
                        'temp_id' => 'welcome',
                        'name' => 'Xush kelibsiz',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "Assalomu alaykum, {first_name}! 👋\n\nSiz uchun maxsus taklif tayyorladik!",
                        ],
                        'keyboard' => [
                            'type' => 'inline',
                            'buttons' => [
                                [['text' => '🎁 Taklifni ko\'rish', 'action_type' => 'next_step']],
                            ],
                        ],
                        'next_step_ref' => 'ab_test',
                        'position_x' => 250,
                        'position_y' => 0,
                    ],
                    [
                        'temp_id' => 'ab_test',
                        'name' => 'A/B Test',
                        'step_type' => 'ab_test',
                        'ab_test' => [
                            'variants' => [
                                ['name' => 'A', 'percentage' => 50, 'next_step_ref' => 'offer_a'],
                                ['name' => 'B', 'percentage' => 50, 'next_step_ref' => 'offer_b'],
                            ],
                        ],
                        'position_x' => 250,
                        'position_y' => 150,
                    ],
                    [
                        'temp_id' => 'offer_a',
                        'name' => 'Taklif A - Chegirma',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "🔥 MAXSUS TAKLIF!\n\n💰 50% CHEGIRMA!\n\nFaqat bugun amal qiladi.\n\n⏰ Qolgan vaqt: 23:59:59",
                        ],
                        'keyboard' => [
                            'type' => 'inline',
                            'buttons' => [
                                [['text' => '💰 50% chegirmadan foydalanish', 'action_type' => 'next_step']],
                            ],
                        ],
                        'next_step_ref' => 'collect_contact',
                        'position_x' => 50,
                        'position_y' => 300,
                    ],
                    [
                        'temp_id' => 'offer_b',
                        'name' => 'Taklif B - Bonus',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "🎁 MAXSUS TAKLIF!\n\n🎯 BEPUL BONUS PAKET!\n\n• 3 ta qo'shimcha modul\n• VIP guruhga kirish\n• Shaxsiy konsultatsiya\n\nFaqat bugun amal qiladi!",
                        ],
                        'keyboard' => [
                            'type' => 'inline',
                            'buttons' => [
                                [['text' => '🎁 Bonus paketni olish', 'action_type' => 'next_step']],
                            ],
                        ],
                        'next_step_ref' => 'collect_contact',
                        'position_x' => 450,
                        'position_y' => 300,
                    ],
                    [
                        'temp_id' => 'collect_contact',
                        'name' => 'Kontakt yig\'ish',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "📱 Telefon raqamingizni yuboring.\n\nTaklifni rasmiylashtirish uchun bog'lanamiz!",
                        ],
                        'keyboard' => [
                            'type' => 'reply',
                            'buttons' => [
                                [['text' => '📱 Telefon yuborish', 'action_type' => 'request_contact']],
                            ],
                        ],
                        'input_type' => 'phone',
                        'input_field' => 'phone',
                        'next_step_ref' => 'tag_hot',
                        'position_x' => 250,
                        'position_y' => 450,
                    ],
                    [
                        'temp_id' => 'tag_hot',
                        'name' => 'Hot lead tegi',
                        'step_type' => 'tag',
                        'tag' => [
                            'action' => 'add',
                            'tags' => ['hot_lead', 'offer_interested'],
                        ],
                        'next_step_ref' => 'thank_you',
                        'position_x' => 250,
                        'position_y' => 600,
                    ],
                    [
                        'temp_id' => 'thank_you',
                        'name' => 'Rahmat',
                        'step_type' => 'message',
                        'content' => [
                            'type' => 'text',
                            'text' => "✅ Ajoyib tanlov, {first_name}!\n\n📞 Mutaxassisimiz 15 daqiqa ichida qo'ng'iroq qiladi.\n\n⚡ Taklifingizni band qildik!",
                        ],
                        'action_type' => 'create_lead',
                        'position_x' => 250,
                        'position_y' => 750,
                    ],
                ],
            ],
        ];
    }

    /**
     * Update funnel
     */
    public function update(Request $request, string $botId, string $funnelId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $funnel = TelegramFunnel::where('telegram_bot_id', $bot->id)
            ->where('id', $funnelId)
            ->firstOrFail();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
            'first_step_id' => 'nullable|uuid|exists:telegram_funnel_steps,id',
            'completion_message' => 'nullable|string|max:4096',
        ]);

        $funnel->update($request->only([
            'name', 'description', 'is_active', 'first_step_id', 'completion_message',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Funnel yangilandi',
        ]);
    }

    /**
     * Toggle funnel active status
     */
    public function toggleActive(Request $request, string $botId, string $funnelId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $funnel = TelegramFunnel::where('telegram_bot_id', $bot->id)
            ->where('id', $funnelId)
            ->firstOrFail();

        $funnel->update(['is_active' => ! $funnel->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $funnel->is_active,
            'message' => $funnel->is_active ? 'Funnel faollashtirildi' : 'Funnel o\'chirildi',
        ]);
    }

    /**
     * Delete funnel
     */
    public function destroy(Request $request, string $botId, string $funnelId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $funnel = TelegramFunnel::where('telegram_bot_id', $bot->id)
            ->where('id', $funnelId)
            ->firstOrFail();

        $funnel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Funnel o\'chirildi',
        ]);
    }

    /**
     * Duplicate funnel
     */
    public function duplicate(Request $request, string $botId, string $funnelId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $funnel = TelegramFunnel::where('telegram_bot_id', $bot->id)
            ->where('id', $funnelId)
            ->with('steps')
            ->firstOrFail();

        // Create new funnel
        $newFunnel = $funnel->replicate();
        $newFunnel->name = $funnel->name.' (nusxa)';
        $newFunnel->is_active = false;
        $newFunnel->first_step_id = null;
        $newFunnel->save();

        // Map old step IDs to new step IDs
        $stepIdMap = [];

        // Duplicate steps
        foreach ($funnel->steps as $step) {
            $newStep = $step->replicate();
            $newStep->funnel_id = $newFunnel->id;
            $newStep->next_step_id = null; // Will be updated later
            $newStep->save();

            $stepIdMap[$step->id] = $newStep->id;
        }

        // Update next_step_id references
        foreach ($funnel->steps as $step) {
            if ($step->next_step_id && isset($stepIdMap[$step->next_step_id])) {
                TelegramFunnelStep::where('id', $stepIdMap[$step->id])
                    ->update(['next_step_id' => $stepIdMap[$step->next_step_id]]);
            }
        }

        // Update first_step_id
        if ($funnel->first_step_id && isset($stepIdMap[$funnel->first_step_id])) {
            $newFunnel->update(['first_step_id' => $stepIdMap[$funnel->first_step_id]]);
        }

        return response()->json([
            'success' => true,
            'funnel' => [
                'id' => $newFunnel->id,
                'name' => $newFunnel->name,
            ],
            'message' => 'Funnel nusxalandi',
        ]);
    }

    /**
     * Save funnel steps (bulk update from builder)
     */
    public function saveSteps(Request $request, string $botId, string $funnelId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $funnel = TelegramFunnel::where('telegram_bot_id', $bot->id)
            ->where('id', $funnelId)
            ->firstOrFail();

        $request->validate([
            'steps' => 'required|array',
            'steps.*.id' => 'nullable',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.step_type' => 'required|in:message,input,condition,action,delay,subscribe_check,quiz,ab_test,tag,trigger_keyword',
            'steps.*.content' => 'nullable|array',
            'steps.*.keyboard' => 'nullable|array',
            'steps.*.input_type' => 'nullable|in:none,text,email,phone,number,photo,location,any',
            'steps.*.input_field' => 'nullable|string|max:255',
            'steps.*.validation' => 'nullable|array',
            'steps.*.next_step_id' => 'nullable|string',
            'steps.*.action_type' => 'nullable|in:none,create_lead,update_user,handoff,send_notification,webhook',
            'steps.*.action_config' => 'nullable|array',
            'steps.*.condition' => 'nullable|array',
            'steps.*.condition_true_step_id' => 'nullable|string',
            'steps.*.condition_false_step_id' => 'nullable|string',
            'steps.*.position_x' => 'nullable|integer',
            'steps.*.position_y' => 'nullable|integer',
            'steps.*.order' => 'nullable|integer',
            // Marketing features
            'steps.*.subscribe_check' => 'nullable|array',
            'steps.*.subscribe_true_step_id' => 'nullable|string',
            'steps.*.subscribe_false_step_id' => 'nullable|string',
            'steps.*.quiz' => 'nullable|array',
            'steps.*.ab_test' => 'nullable|array',
            'steps.*.tag' => 'nullable|array',
            'steps.*.trigger' => 'nullable|array',
            'steps.*.delay_seconds' => 'nullable|integer|min:0|max:86400',
            'first_step_id' => 'nullable|string',
        ]);

        // Qo'shimcha domen tekshiruvlar — kontroller'ga kelgan saytdagi validator qolmaydi.
        foreach ($request->input('steps', []) as $idx => $step) {
            // A/B test: variantlar percentage yig'indisi 100 bo'lishi shart.
            if (($step['step_type'] ?? null) === 'ab_test' && ! empty($step['ab_test']['variants'])) {
                $sum = 0;
                foreach ($step['ab_test']['variants'] as $v) {
                    $sum += (int) ($v['percentage'] ?? 0);
                }
                if ($sum !== 100) {
                    return response()->json([
                        'success' => false,
                        'message' => "A/B test step #{$idx}: variantlar % yig'indisi 100 bo'lishi kerak (hozir {$sum}%)",
                    ], 422);
                }
            }
            // Quiz: kamida 2 variant va bo'sh bo'lmasligi kerak.
            if (($step['step_type'] ?? null) === 'quiz') {
                $options = $step['quiz']['options'] ?? [];
                if (count($options) < 2) {
                    return response()->json([
                        'success' => false,
                        'message' => "Quiz step #{$idx}: kamida 2 ta variant bo'lishi kerak",
                    ], 422);
                }
                foreach ($options as $oi => $opt) {
                    if (empty(trim((string) ($opt['text'] ?? '')))) {
                        return response()->json([
                            'success' => false,
                            'message' => "Quiz step #{$idx}: variant {$oi} matni bo'sh",
                        ], 422);
                    }
                }
                // correct_option_index mavjud bo'lsa, options ichida bo'lishi kerak.
                if (isset($step['quiz']['correct_option_index']) && $step['quiz']['correct_option_index'] !== null) {
                    $cIdx = (int) $step['quiz']['correct_option_index'];
                    if ($cIdx < 0 || $cIdx >= count($options)) {
                        return response()->json([
                            'success' => false,
                            'message' => "Quiz step #{$idx}: correct_option_index noto'g'ri",
                        ], 422);
                    }
                }
            }
            // Webhook: URL mavjud bo'lsa https? sxemada bo'lishi shart.
            if (($step['action_type'] ?? null) === 'webhook') {
                $url = $step['action_config']['url'] ?? null;
                if (! $url || ! preg_match('#^https?://#i', (string) $url)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Webhook step #{$idx}: to'g'ri URL kiriting (https://…)",
                    ], 422);
                }
            }
        }

        // HMAC secret maydonini encrypt qilamiz — JSON'da plaintext yotmasin.
        // Format: `enc:v1:<ciphertext>` — engine'da shu prefix tekshirilib decrypt bo'ladi.
        // Foydalanuvchi qayta kiritmasa, avval encrypted turgan qiymat o'zgarmaydi
        // (prefix bilan kelgan payload'ni 2-marta encrypt qilmaymiz).
        $stepsInput = $request->input('steps', []);
        foreach ($stepsInput as $i => $step) {
            if (($step['action_type'] ?? null) === 'webhook'
                && ! empty($step['action_config']['secret'])
            ) {
                $secret = (string) $step['action_config']['secret'];
                if (! str_starts_with($secret, 'enc:v1:')) {
                    try {
                        $stepsInput[$i]['action_config']['secret'] = 'enc:v1:' . \Illuminate\Support\Facades\Crypt::encryptString($secret);
                    } catch (\Throwable $e) {
                        Log::warning('Funnel webhook secret encrypt failed', ['step_idx' => $i]);
                    }
                }
            }
        }
        $request->merge(['steps' => $stepsInput]);

        $existingStepIds = $funnel->steps()->pluck('id')->toArray();
        $updatedStepIds = [];
        $tempIdMap = []; // Map temp IDs to real IDs

        // First pass: create/update steps and build ID map
        foreach ($request->steps as $index => $stepData) {
            $stepId = $stepData['id'] ?? null;
            $isNewStep = ! $stepId || ! in_array($stepId, $existingStepIds);

            // Map input types from builder to database enum
            $inputType = $stepData['input_type'] ?? 'none';
            if ($inputType === 'contact') {
                $inputType = 'phone';
            }
            if ($inputType === 'document') {
                $inputType = 'any';
            }
            if ($inputType === 'choice') {
                $inputType = 'any';
            }

            if ($isNewStep) {
                // Create new step
                $step = TelegramFunnelStep::create([
                    'funnel_id' => $funnel->id,
                    'name' => $stepData['name'],
                    'slug' => \Illuminate\Support\Str::slug($stepData['name']).'-'.\Illuminate\Support\Str::random(6),
                    'step_type' => $stepData['step_type'],
                    'content' => $stepData['content'] ?? ['type' => 'text', 'text' => ''],
                    'keyboard' => $stepData['keyboard'] ?? null,
                    'input_type' => $inputType,
                    'input_field' => $stepData['input_field'] ?? null,
                    'validation' => $stepData['validation'] ?? null,
                    'action_type' => $stepData['action_type'] ?? 'none',
                    'action_config' => $stepData['action_config'] ?? null,
                    'condition' => $stepData['condition'] ?? null,
                    'position_x' => $stepData['position_x'] ?? 0,
                    'position_y' => $stepData['position_y'] ?? $index * 150,
                    'order' => $stepData['order'] ?? $index,
                    'delay_ms' => isset($stepData['delay_seconds']) ? (int) $stepData['delay_seconds'] * 1000 : 0,
                    // Marketing features
                    'subscribe_check' => $stepData['subscribe_check'] ?? null,
                    'quiz' => $stepData['quiz'] ?? null,
                    'ab_test' => $stepData['ab_test'] ?? null,
                    'tag' => $stepData['tag'] ?? null,
                    'trigger' => $stepData['trigger'] ?? null,
                ]);

                if ($stepId) {
                    $tempIdMap[$stepId] = $step->id;
                }
                $updatedStepIds[] = $step->id;
            } else {
                // Update existing step
                $updatePayload = [
                    'name' => $stepData['name'],
                    'step_type' => $stepData['step_type'],
                    'content' => $stepData['content'] ?? ['type' => 'text', 'text' => ''],
                    'keyboard' => $stepData['keyboard'] ?? null,
                    'input_type' => $inputType,
                    'input_field' => $stepData['input_field'] ?? null,
                    'validation' => $stepData['validation'] ?? null,
                    'action_type' => $stepData['action_type'] ?? 'none',
                    'action_config' => $stepData['action_config'] ?? null,
                    'condition' => $stepData['condition'] ?? null,
                    'position_x' => $stepData['position_x'] ?? 0,
                    'position_y' => $stepData['position_y'] ?? $index * 150,
                    'order' => $stepData['order'] ?? $index,
                    // Marketing features
                    'subscribe_check' => $stepData['subscribe_check'] ?? null,
                    'quiz' => $stepData['quiz'] ?? null,
                    'ab_test' => $stepData['ab_test'] ?? null,
                    'tag' => $stepData['tag'] ?? null,
                    'trigger' => $stepData['trigger'] ?? null,
                ];
                if (array_key_exists('delay_seconds', $stepData)) {
                    $updatePayload['delay_ms'] = (int) ($stepData['delay_seconds'] ?? 0) * 1000;
                }
                TelegramFunnelStep::where('id', $stepId)->update($updatePayload);

                $tempIdMap[$stepId] = $stepId;
                $updatedStepIds[] = $stepId;
            }
        }

        // Shu funnel ichidagi haqiqiy step ID'lari to'plami — cross-funnel o'tishlarni
        // bloklash uchun ishlatiladi. Ushbu to'plamda yo'q bo'lgan har qanday next_step_id
        // yoki branch ID sanitizatsiya paytida null'ga aylanadi.
        $validFunnelStepIds = array_flip(array_values($updatedStepIds));
        $sanitizeRef = function ($ref) use ($tempIdMap, $validFunnelStepIds) {
            if (empty($ref)) {
                return null;
            }
            $mapped = $tempIdMap[$ref] ?? $ref;
            return isset($validFunnelStepIds[$mapped]) ? $mapped : null;
        };

        // Second pass: update next_step_id and branch references
        foreach ($request->steps as $stepData) {
            $stepId = isset($stepData['id']) ? ($tempIdMap[$stepData['id']] ?? $stepData['id']) : null;

            if (! $stepId) {
                continue;
            }

            $updateData = [];

            // Regular next_step_id
            if (isset($stepData['next_step_id']) && $stepData['next_step_id']) {
                $updateData['next_step_id'] = $sanitizeRef($stepData['next_step_id']);
            }

            // Condition branches
            if (isset($stepData['condition_true_step_id']) && $stepData['condition_true_step_id']) {
                $updateData['condition_true_step_id'] = $sanitizeRef($stepData['condition_true_step_id']);
            }
            if (isset($stepData['condition_false_step_id']) && $stepData['condition_false_step_id']) {
                $updateData['condition_false_step_id'] = $sanitizeRef($stepData['condition_false_step_id']);
            }

            // Subscribe check branches
            if (isset($stepData['subscribe_true_step_id']) && $stepData['subscribe_true_step_id']) {
                $updateData['subscribe_true_step_id'] = $sanitizeRef($stepData['subscribe_true_step_id']);
            }
            if (isset($stepData['subscribe_false_step_id']) && $stepData['subscribe_false_step_id']) {
                $updateData['subscribe_false_step_id'] = $sanitizeRef($stepData['subscribe_false_step_id']);
            }

            // Quiz option next_step_ids need to be remapped
            if (isset($stepData['quiz']) && ! empty($stepData['quiz']['options'])) {
                $quiz = $stepData['quiz'];
                foreach ($quiz['options'] as $i => $option) {
                    if (! empty($option['next_step_id'])) {
                        $quiz['options'][$i]['next_step_id'] = $sanitizeRef($option['next_step_id']);
                    }
                }
                // Correct/wrong step'lar ham shu funnel ichida bo'lishi kerak.
                if (! empty($quiz['correct_step_id'])) {
                    $quiz['correct_step_id'] = $sanitizeRef($quiz['correct_step_id']);
                }
                if (! empty($quiz['wrong_step_id'])) {
                    $quiz['wrong_step_id'] = $sanitizeRef($quiz['wrong_step_id']);
                }
                $updateData['quiz'] = $quiz;
            }

            // A/B Test variant next_step_ids need to be remapped
            if (isset($stepData['ab_test']) && ! empty($stepData['ab_test']['variants'])) {
                $abTest = $stepData['ab_test'];
                foreach ($abTest['variants'] as $i => $variant) {
                    if (! empty($variant['next_step_id'])) {
                        $abTest['variants'][$i]['next_step_id'] = $sanitizeRef($variant['next_step_id']);
                    }
                }
                $updateData['ab_test'] = $abTest;
            }

            if (! empty($updateData)) {
                TelegramFunnelStep::where('id', $stepId)->update($updateData);
            }
        }

        // Delete removed steps
        $stepsToDelete = array_diff($existingStepIds, $updatedStepIds);
        if (! empty($stepsToDelete)) {
            TelegramFunnelStep::whereIn('id', $stepsToDelete)->delete();
        }

        // Update funnel first_step_id
        if ($request->has('first_step_id')) {
            $firstStepId = $tempIdMap[$request->first_step_id] ?? $request->first_step_id;
            $funnel->update(['first_step_id' => $firstStepId]);
        }

        // Get updated steps
        $steps = $funnel->fresh()->steps->map(fn ($step) => [
            'id' => $step->id,
            'name' => $step->name,
            'step_type' => $step->step_type,
            'content' => $step->content,
            'keyboard' => $step->keyboard,
            'input_type' => $step->input_type,
            'input_field' => $step->input_field,
            'validation' => $step->validation,
            'next_step_id' => $step->next_step_id,
            'action_type' => $step->action_type,
            'action_config' => $step->action_config,
            'condition' => $step->condition,
            'condition_true_step_id' => $step->condition_true_step_id,
            'condition_false_step_id' => $step->condition_false_step_id,
            'position_x' => $step->position_x,
            'position_y' => $step->position_y,
            'order' => $step->order,
            // Marketing features
            'subscribe_check' => $step->subscribe_check,
            'subscribe_true_step_id' => $step->subscribe_true_step_id,
            'subscribe_false_step_id' => $step->subscribe_false_step_id,
            'quiz' => $step->quiz,
            'ab_test' => $step->ab_test,
            'tag' => $step->tag,
            'trigger' => $step->trigger,
        ]);

        return response()->json([
            'success' => true,
            'steps' => $steps,
            'first_step_id' => $funnel->fresh()->first_step_id,
            'message' => 'Funnel saqlandi',
        ]);
    }

    /**
     * Test-run the funnel against the authenticated operator's Telegram account.
     *
     * Looks up a TelegramUser record matching the operator's linked
     * telegram_chat_id (from the System Bot link flow) for this specific bot.
     * If one is not found, returns a clear error prompting the operator to
     * start the bot first.
     *
     * Security:
     *  - Verifies the business owns the bot and the bot owns the funnel
     *    (same pattern as every other method in this controller).
     *  - Temporarily flips is_active when needed so FunnelEngine's
     *    strict cross-tenant/activation checks still apply and the funnel
     *    state is left exactly as it was.
     */
    public function testRun(Request $request, string $botId, string $funnelId): JsonResponse
    {
        $user = $request->user();
        $business = $user->currentBusiness;

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 403);
        }

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $funnel = TelegramFunnel::where('telegram_bot_id', $bot->id)
            ->where('id', $funnelId)
            ->firstOrFail();

        // Operator must have linked their Telegram via system bot AND must
        // be a registered TelegramUser on THIS bot (i.e. they /started it).
        $operatorChatId = $user->telegram_chat_id ?? null;

        if (! $operatorChatId) {
            return response()->json([
                'success' => false,
                'code' => 'telegram_not_linked',
                'message' => "Telegramga bog'lanmagansiz — avval Telegram hisobingizni ulang",
                'connect_url' => '/settings/telegram',
            ], 422);
        }

        $telegramUser = TelegramUser::where('telegram_bot_id', $bot->id)
            ->where('telegram_id', $operatorChatId)
            ->first();

        if (! $telegramUser) {
            return response()->json([
                'success' => false,
                'code' => 'bot_not_started',
                'message' => "Bu botni oldin ishga tushiring (@{$bot->bot_username} botga /start yuboring)",
            ], 422);
        }

        $step = $funnel->firstStep();
        if (! $step) {
            return response()->json([
                'success' => false,
                'message' => "Funnelda qadam yo'q — avval qadam qo'shing",
            ], 422);
        }

        // Run funnel — temporarily flip is_active if needed so FunnelEngine's
        // safety gate passes, then restore original state.
        $wasActive = (bool) $funnel->is_active;
        try {
            if (! $wasActive) {
                $funnel->forceFill(['is_active' => true])->saveQuietly();
            }

            $engine = new FunnelEngineService($bot, $telegramUser);
            $engine->startFunnel($funnel->id);
        } catch (\Throwable $e) {
            Log::error('Funnel test-run failed', [
                'bot_id' => $bot->id,
                'funnel_id' => $funnel->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sinov yuborishda xatolik: '.$e->getMessage(),
            ], 500);
        } finally {
            if (! $wasActive) {
                $funnel->forceFill(['is_active' => false])->saveQuietly();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Testga botga yuborildi',
        ]);
    }

    /**
     * Store a builder media file on the public disk and return a stable URL.
     *
     * Xavfsizlik:
     *  - Faqat shu biznesning bot'iga bog'liq fayllar yuklanadi (bot_id scope).
     *  - MIME + size limits — `image/*` 5MB, `video/*` 30MB, `audio/*` 10MB, `application/*` 15MB.
     *  - Fayl nomi random hash bilan almashtiriladi — path traversal va conflict yo'q.
     *  - Tarif tekshiruvi (storage_gb) kerak bo'lsa SubscriptionGate orqali qo'shish mumkin.
     */
    public function uploadMedia(Request $request, string $botId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        if (! $business) {
            return response()->json(['success' => false, 'message' => 'Biznes topilmadi'], 403);
        }

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $request->validate([
            'file' => 'required|file|max:30720', // 30MB umumiy cheklov
            'kind' => 'nullable|in:photo,video,voice,video_note,document',
        ]);

        $file = $request->file('file');
        $kind = $request->input('kind', 'photo');

        // MIME allow-list — har qanday "document" ham cheksiz emas.
        // Kalit: kind → [MIME allow-list prefixes, explicit MIMEs, max size, MIME→ext map, human label]
        $mime = $file->getMimeType() ?? '';
        $maxMap = [
            'photo' => [
                ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                5 * 1024 * 1024,
                ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'],
                'rasm',
            ],
            'video' => [
                ['video/mp4', 'video/quicktime', 'video/webm'],
                30 * 1024 * 1024,
                ['video/mp4' => 'mp4', 'video/quicktime' => 'mov', 'video/webm' => 'webm'],
                'video',
            ],
            'voice' => [
                ['audio/ogg', 'audio/mpeg', 'audio/mp4', 'audio/webm', 'audio/wav', 'audio/x-wav'],
                10 * 1024 * 1024,
                ['audio/ogg' => 'ogg', 'audio/mpeg' => 'mp3', 'audio/mp4' => 'm4a', 'audio/webm' => 'weba', 'audio/wav' => 'wav', 'audio/x-wav' => 'wav'],
                'audio',
            ],
            'video_note' => [
                ['video/mp4', 'video/quicktime'],
                10 * 1024 * 1024,
                ['video/mp4' => 'mp4', 'video/quicktime' => 'mov'],
                'video_note',
            ],
            'document' => [
                [
                    'application/pdf', 'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/zip', 'application/x-zip-compressed',
                    'text/plain', 'text/csv',
                ],
                15 * 1024 * 1024,
                [
                    'application/pdf' => 'pdf',
                    'application/msword' => 'doc',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                    'application/vnd.ms-excel' => 'xls',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                    'application/zip' => 'zip',
                    'application/x-zip-compressed' => 'zip',
                    'text/plain' => 'txt',
                    'text/csv' => 'csv',
                ],
                'hujjat',
            ],
        ];
        $rule = $maxMap[$kind] ?? $maxMap['photo'];
        [$allowedMimes, $maxSize, $extMap, $label] = $rule;

        if (! in_array($mime, $allowedMimes, true)) {
            return response()->json([
                'success' => false,
                'message' => "Fayl turi qo'llab-quvvatlanmaydi: kelgan {$mime} (kerak: {$label})",
            ], 422);
        }
        if ($file->getSize() > $maxSize) {
            return response()->json([
                'success' => false,
                'message' => 'Fayl hajmi cheklovdan oshdi',
            ], 422);
        }

        // Fayl kengaytmasi — faqat MIME asosida, user input'ga ishonmaymiz
        // (`getClientOriginalExtension()` XSS/RCE vektoriga aylanishi mumkin:
        // `.php`, `.htaccess` va boshqa dangerous kengaytmalarni oladi).
        $ext = $extMap[$mime] ?? 'bin';
        $hash = bin2hex(random_bytes(16));
        $dir = "telegram/funnel/{$bot->id}";
        $stored = $file->storeAs($dir, "{$hash}.{$ext}", 'public');

        if (! $stored) {
            return response()->json(['success' => false, 'message' => 'Saqlashda xatolik'], 500);
        }

        $url = \Illuminate\Support\Facades\Storage::disk('public')->url($stored);

        return response()->json([
            'success' => true,
            'url' => $url,
            'path' => $stored,
            'size' => $file->getSize(),
            'mime' => $mime,
            'original_name' => $file->getClientOriginalName(),
            'file_id' => '', // Real file_id Telegram API uses lazy — client may save empty
        ]);
    }
}
