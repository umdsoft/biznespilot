<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class InstagramAutomationTemplate extends Model
{
    use HasUuid;
    protected $fillable = [
        'name',
        'description',
        'category',
        'icon',
        'nodes',
        'edges',
        'usage_count',
        'is_active',
    ];

    protected $casts = [
        'nodes' => 'array',
        'edges' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get default templates
     */
    public static function getDefaultTemplates(): array
    {
        return [
            // ========== LEAD MAGNET SHABLONLARI ==========
            [
                'id' => 1,
                'name' => 'Lead Magnet - Bepul PDF/Guide',
                'description' => 'Commentda kalit so\'z yozganlarga bepul material (PDF, guide, checklist) yuborish. Obuna va like shartlari bilan.',
                'category' => 'lead',
                'icon' => 'gift',
                'usage_count' => 456,
                'nodes' => [
                    [
                        'node_id' => 'tpl_trigger_1',
                        'node_type' => 'trigger_keyword_comment',
                        'position' => ['x' => 250, 'y' => 50],
                        'data' => ['keywords' => 'olish, kerak, yubor, guide, pdf, yuklab', 'media_id' => '__all__'],
                    ],
                    [
                        'node_id' => 'tpl_condition_1',
                        'node_type' => 'condition_is_follower',
                        'position' => ['x' => 250, 'y' => 200],
                        'data' => [],
                    ],
                    [
                        'node_id' => 'tpl_action_reply',
                        'node_type' => 'action_reply_comment',
                        'position' => ['x' => 80, 'y' => 350],
                        'data' => ['message' => 'DM ga yubordim! Tekshirib ko\'ring'],
                    ],
                    [
                        'node_id' => 'tpl_action_dm',
                        'node_type' => 'action_send_dm',
                        'position' => ['x' => 80, 'y' => 500],
                        'data' => ['message' => "Salom {name}!\n\nMana sizning bepul materialingiz:\nexample.com/guide-download\n\nYoqsa do'stlaringizga ham ulashing!"],
                    ],
                    [
                        'node_id' => 'tpl_action_link',
                        'node_type' => 'action_send_link',
                        'position' => ['x' => 80, 'y' => 650],
                        'data' => ['url' => 'https://example.com/guide', 'message' => 'Yuklab olish uchun bosing'],
                    ],
                    [
                        'node_id' => 'tpl_action_tag',
                        'node_type' => 'action_add_tag',
                        'position' => ['x' => 80, 'y' => 800],
                        'data' => ['tag' => 'lead-magnet-received'],
                    ],
                    [
                        'node_id' => 'tpl_action_no',
                        'node_type' => 'action_send_dm',
                        'position' => ['x' => 420, 'y' => 350],
                        'data' => ['message' => "Salom {name}!\n\nBepul materialni olish uchun:\n\n1. Bizga obuna bo'ling\n2. Postga like bosing\n3. Qayta comment qiling\n\nShundan so'ng avtomatik yuboramiz!"],
                    ],
                ],
                'edges' => [
                    ['edge_id' => 'tpl_e1', 'source_node_id' => 'tpl_trigger_1', 'target_node_id' => 'tpl_condition_1', 'source_handle' => null],
                    ['edge_id' => 'tpl_e2', 'source_node_id' => 'tpl_condition_1', 'target_node_id' => 'tpl_action_reply', 'source_handle' => 'yes'],
                    ['edge_id' => 'tpl_e3', 'source_node_id' => 'tpl_action_reply', 'target_node_id' => 'tpl_action_dm', 'source_handle' => null],
                    ['edge_id' => 'tpl_e4', 'source_node_id' => 'tpl_action_dm', 'target_node_id' => 'tpl_action_link', 'source_handle' => null],
                    ['edge_id' => 'tpl_e5', 'source_node_id' => 'tpl_action_link', 'target_node_id' => 'tpl_action_tag', 'source_handle' => null],
                    ['edge_id' => 'tpl_e6', 'source_node_id' => 'tpl_condition_1', 'target_node_id' => 'tpl_action_no', 'source_handle' => 'no'],
                ],
            ],
            [
                'id' => 2,
                'name' => 'Lead Magnet - DM orqali',
                'description' => 'DM da kalit so\'z yozganlarga bepul material yuborish. Oddiy va tez.',
                'category' => 'lead',
                'icon' => 'gift',
                'usage_count' => 389,
                'nodes' => [
                    [
                        'node_id' => 'tpl_trigger_1',
                        'node_type' => 'trigger_keyword_dm',
                        'position' => ['x' => 250, 'y' => 50],
                        'data' => ['keywords' => 'guide, pdf, olish, yuklab, bepul', 'exact_match' => 'contains'],
                    ],
                    [
                        'node_id' => 'tpl_action_1',
                        'node_type' => 'action_send_dm',
                        'position' => ['x' => 250, 'y' => 200],
                        'data' => ['message' => "Ajoyib tanlov {name}!\n\nMana sizning bepul materialingiz:"],
                    ],
                    [
                        'node_id' => 'tpl_action_2',
                        'node_type' => 'action_send_link',
                        'position' => ['x' => 250, 'y' => 350],
                        'data' => ['url' => 'https://example.com/free-guide', 'message' => 'Bepul yuklab oling!'],
                    ],
                    [
                        'node_id' => 'tpl_action_3',
                        'node_type' => 'action_delay',
                        'position' => ['x' => 250, 'y' => 500],
                        'data' => ['delay_type' => 'hours', 'delay_value' => 24],
                    ],
                    [
                        'node_id' => 'tpl_action_4',
                        'node_type' => 'action_send_dm',
                        'position' => ['x' => 250, 'y' => 650],
                        'data' => ['message' => "Salom {name}!\n\nKecha yuborgan materialni ko'rdingizmi?\n\nSavollaringiz bo'lsa yozing, yordam beraman!"],
                    ],
                    [
                        'node_id' => 'tpl_action_5',
                        'node_type' => 'action_add_tag',
                        'position' => ['x' => 250, 'y' => 800],
                        'data' => ['tag' => 'lead-warm'],
                    ],
                ],
                'edges' => [
                    ['edge_id' => 'tpl_e1', 'source_node_id' => 'tpl_trigger_1', 'target_node_id' => 'tpl_action_1', 'source_handle' => null],
                    ['edge_id' => 'tpl_e2', 'source_node_id' => 'tpl_action_1', 'target_node_id' => 'tpl_action_2', 'source_handle' => null],
                    ['edge_id' => 'tpl_e3', 'source_node_id' => 'tpl_action_2', 'target_node_id' => 'tpl_action_3', 'source_handle' => null],
                    ['edge_id' => 'tpl_e4', 'source_node_id' => 'tpl_action_3', 'target_node_id' => 'tpl_action_4', 'source_handle' => null],
                    ['edge_id' => 'tpl_e5', 'source_node_id' => 'tpl_action_4', 'target_node_id' => 'tpl_action_5', 'source_handle' => null],
                ],
            ],
            [
                'id' => 3,
                'name' => 'Lead Magnet - Chegirma kodi',
                'description' => 'Comment yozganlarga maxsus chegirma kodi yuborish va sotuvga yo\'naltirish',
                'category' => 'lead',
                'icon' => 'tag',
                'usage_count' => 278,
                'nodes' => [
                    [
                        'node_id' => 'tpl_trigger_1',
                        'node_type' => 'trigger_keyword_comment',
                        'position' => ['x' => 250, 'y' => 50],
                        'data' => ['keywords' => 'chegirma, skidka, discount, kod, promo', 'media_id' => '__all__'],
                    ],
                    [
                        'node_id' => 'tpl_condition_1',
                        'node_type' => 'condition_is_follower',
                        'position' => ['x' => 250, 'y' => 200],
                        'data' => [],
                    ],
                    [
                        'node_id' => 'tpl_action_reply',
                        'node_type' => 'action_reply_comment',
                        'position' => ['x' => 80, 'y' => 350],
                        'data' => ['message' => 'DM ni tekshiring - maxsus kod yubordim!'],
                    ],
                    [
                        'node_id' => 'tpl_action_dm',
                        'node_type' => 'action_send_dm',
                        'position' => ['x' => 80, 'y' => 500],
                        'data' => ['message' => "Tabriklaymiz {name}!\n\nSizning maxsus chegirma kodingiz:\nINSTA20\n\n20% chegirma faqat 48 soat!\n\nBuyurtma: example.com/shop"],
                    ],
                    [
                        'node_id' => 'tpl_action_tag',
                        'node_type' => 'action_add_tag',
                        'position' => ['x' => 80, 'y' => 650],
                        'data' => ['tag' => 'promo-sent'],
                    ],
                    [
                        'node_id' => 'tpl_action_no',
                        'node_type' => 'action_send_dm',
                        'position' => ['x' => 420, 'y' => 350],
                        'data' => ['message' => "Salom!\n\nMaxsus chegirma kodi olish uchun bizga obuna bo'ling va qayta comment yozing!\n\nObunachilarga 20% chegirma!"],
                    ],
                ],
                'edges' => [
                    ['edge_id' => 'tpl_e1', 'source_node_id' => 'tpl_trigger_1', 'target_node_id' => 'tpl_condition_1', 'source_handle' => null],
                    ['edge_id' => 'tpl_e2', 'source_node_id' => 'tpl_condition_1', 'target_node_id' => 'tpl_action_reply', 'source_handle' => 'yes'],
                    ['edge_id' => 'tpl_e3', 'source_node_id' => 'tpl_action_reply', 'target_node_id' => 'tpl_action_dm', 'source_handle' => null],
                    ['edge_id' => 'tpl_e4', 'source_node_id' => 'tpl_action_dm', 'target_node_id' => 'tpl_action_tag', 'source_handle' => null],
                    ['edge_id' => 'tpl_e5', 'source_node_id' => 'tpl_condition_1', 'target_node_id' => 'tpl_action_no', 'source_handle' => 'no'],
                ],
            ],

            // ========== SOTUV SHABLONLARI ==========
            [
                'id' => 4,
                'name' => 'Narx so\'roviga javob',
                'description' => 'DM orqali narx so\'raganlarga avtomatik narxnoma yuborish va obunani tekshirish',
                'category' => 'sales',
                'icon' => 'currency-dollar',
                'usage_count' => 356,
                'nodes' => [
                    [
                        'node_id' => 'tpl_trigger_1',
                        'node_type' => 'trigger_keyword_dm',
                        'position' => ['x' => 250, 'y' => 50],
                        'data' => ['keywords' => 'narx, narxi, qancha, price, baho, necha', 'exact_match' => 'contains'],
                    ],
                    [
                        'node_id' => 'tpl_condition_1',
                        'node_type' => 'condition_is_follower',
                        'position' => ['x' => 250, 'y' => 200],
                        'data' => [],
                    ],
                    [
                        'node_id' => 'tpl_action_1',
                        'node_type' => 'action_send_dm',
                        'position' => ['x' => 80, 'y' => 380],
                        'data' => ['message' => "Salom {name}!\n\nNarxlarimiz:\n\n- Standart paket: 100,000 so'm\n- Premium paket: 200,000 so'm\n- VIP paket: 350,000 so'm\n\nBatafsil: example.com/price\n\nQaysi biri qiziqtirdi?"],
                    ],
                    [
                        'node_id' => 'tpl_action_tag1',
                        'node_type' => 'action_add_tag',
                        'position' => ['x' => 80, 'y' => 530],
                        'data' => ['tag' => 'price-requested'],
                    ],
                    [
                        'node_id' => 'tpl_action_2',
                        'node_type' => 'action_send_dm',
                        'position' => ['x' => 420, 'y' => 380],
                        'data' => ['message' => "Salom!\n\nNarxlarimizni bilish uchun avval bizga obuna bo'ling!\n\nObunachilarga maxsus narxlar va chegirmalar mavjud.\n\nObuna bo'lgach qayta yozing!"],
                    ],
                ],
                'edges' => [
                    ['edge_id' => 'tpl_e1', 'source_node_id' => 'tpl_trigger_1', 'target_node_id' => 'tpl_condition_1', 'source_handle' => null],
                    ['edge_id' => 'tpl_e2', 'source_node_id' => 'tpl_condition_1', 'target_node_id' => 'tpl_action_1', 'source_handle' => 'yes'],
                    ['edge_id' => 'tpl_e3', 'source_node_id' => 'tpl_action_1', 'target_node_id' => 'tpl_action_tag1', 'source_handle' => null],
                    ['edge_id' => 'tpl_e4', 'source_node_id' => 'tpl_condition_1', 'target_node_id' => 'tpl_action_2', 'source_handle' => 'no'],
                ],
            ],
            [
                'id' => 5,
                'name' => 'Buyurtma qabul qilish',
                'description' => 'Buyurtma so\'raganlarga avtomatik javob va buyurtma jarayonini boshlash',
                'category' => 'sales',
                'icon' => 'shopping-cart',
                'usage_count' => 234,
                'nodes' => [
                    [
                        'node_id' => 'tpl_trigger_1',
                        'node_type' => 'trigger_keyword_dm',
                        'position' => ['x' => 250, 'y' => 50],
                        'data' => ['keywords' => 'buyurtma, zakaz, olmoqchiman, sotib, xarid', 'exact_match' => 'contains'],
                    ],
                    [
                        'node_id' => 'tpl_action_1',
                        'node_type' => 'action_send_dm',
                        'position' => ['x' => 250, 'y' => 200],
                        'data' => ['message' => "Ajoyib tanlov {name}!\n\nBuyurtma berish uchun quyidagilarni yuboring:\n\n1. Mahsulot nomi\n2. Miqdori\n3. Manzil\n4. Telefon raqam\n\nYoki saytimizda buyurtma bering:\nexample.com/order"],
                    ],
                    [
                        'node_id' => 'tpl_action_2',
                        'node_type' => 'action_add_tag',
                        'position' => ['x' => 250, 'y' => 350],
                        'data' => ['tag' => 'order-started'],
                    ],
                ],
                'edges' => [
                    ['edge_id' => 'tpl_e1', 'source_node_id' => 'tpl_trigger_1', 'target_node_id' => 'tpl_action_1', 'source_handle' => null],
                    ['edge_id' => 'tpl_e2', 'source_node_id' => 'tpl_action_1', 'target_node_id' => 'tpl_action_2', 'source_handle' => null],
                ],
            ],

            // ========== ENGAGEMENT SHABLONLARI ==========
            [
                'id' => 6,
                'name' => 'Yangi follower salomlash',
                'description' => 'Yangi obunachilarga 5 daqiqadan so\'ng xush kelibsiz xabari yuborish',
                'category' => 'engagement',
                'icon' => 'user-add',
                'usage_count' => 512,
                'nodes' => [
                    [
                        'node_id' => 'tpl_trigger_1',
                        'node_type' => 'trigger_new_follower',
                        'position' => ['x' => 250, 'y' => 50],
                        'data' => [],
                    ],
                    [
                        'node_id' => 'tpl_action_1',
                        'node_type' => 'action_delay',
                        'position' => ['x' => 250, 'y' => 200],
                        'data' => ['delay_type' => 'minutes', 'delay_value' => 5],
                    ],
                    [
                        'node_id' => 'tpl_action_2',
                        'node_type' => 'action_send_dm',
                        'position' => ['x' => 250, 'y' => 350],
                        'data' => ['message' => "Salom {name}!\n\nBizga obuna bo'lganingiz uchun katta rahmat!\n\nBiz [kompaniya tavsifi].\n\nSavollaringiz bo'lsa bemalol yozing - tez javob beramiz!"],
                    ],
                    [
                        'node_id' => 'tpl_action_3',
                        'node_type' => 'action_add_tag',
                        'position' => ['x' => 250, 'y' => 500],
                        'data' => ['tag' => 'welcomed'],
                    ],
                ],
                'edges' => [
                    ['edge_id' => 'tpl_e1', 'source_node_id' => 'tpl_trigger_1', 'target_node_id' => 'tpl_action_1', 'source_handle' => null],
                    ['edge_id' => 'tpl_e2', 'source_node_id' => 'tpl_action_1', 'target_node_id' => 'tpl_action_2', 'source_handle' => null],
                    ['edge_id' => 'tpl_e3', 'source_node_id' => 'tpl_action_2', 'target_node_id' => 'tpl_action_3', 'source_handle' => null],
                ],
            ],
            [
                'id' => 7,
                'name' => 'Commentga avtomatik DM',
                'description' => 'Postga comment qilganlarga avtomatik DM yuborish va tag qo\'shish',
                'category' => 'engagement',
                'icon' => 'chat',
                'usage_count' => 334,
                'nodes' => [
                    [
                        'node_id' => 'tpl_trigger_1',
                        'node_type' => 'trigger_keyword_comment',
                        'position' => ['x' => 250, 'y' => 50],
                        'data' => ['keywords' => '__all__', 'media_id' => '__all__'],
                    ],
                    [
                        'node_id' => 'tpl_action_1',
                        'node_type' => 'action_reply_comment',
                        'position' => ['x' => 250, 'y' => 200],
                        'data' => ['message' => 'Rahmat! DM ga qarang'],
                    ],
                    [
                        'node_id' => 'tpl_action_2',
                        'node_type' => 'action_send_dm',
                        'position' => ['x' => 250, 'y' => 350],
                        'data' => ['message' => "Salom {name}!\n\nComment qoldirganingiz uchun rahmat!\n\nSizga qanday yordam bera olaman?"],
                    ],
                    [
                        'node_id' => 'tpl_action_3',
                        'node_type' => 'action_add_tag',
                        'position' => ['x' => 250, 'y' => 500],
                        'data' => ['tag' => 'engaged'],
                    ],
                ],
                'edges' => [
                    ['edge_id' => 'tpl_e1', 'source_node_id' => 'tpl_trigger_1', 'target_node_id' => 'tpl_action_1', 'source_handle' => null],
                    ['edge_id' => 'tpl_e2', 'source_node_id' => 'tpl_action_1', 'target_node_id' => 'tpl_action_2', 'source_handle' => null],
                    ['edge_id' => 'tpl_e3', 'source_node_id' => 'tpl_action_2', 'target_node_id' => 'tpl_action_3', 'source_handle' => null],
                ],
            ],
            [
                'id' => 8,
                'name' => 'Story mention rahmat',
                'description' => 'Storyda mention qilganlarga rahmat aytish va ambassador tag qo\'shish',
                'category' => 'engagement',
                'icon' => 'at-symbol',
                'usage_count' => 189,
                'nodes' => [
                    [
                        'node_id' => 'tpl_trigger_1',
                        'node_type' => 'trigger_story_mention',
                        'position' => ['x' => 250, 'y' => 50],
                        'data' => [],
                    ],
                    [
                        'node_id' => 'tpl_action_1',
                        'node_type' => 'action_send_dm',
                        'position' => ['x' => 250, 'y' => 200],
                        'data' => ['message' => "Voy, katta rahmat {name}!\n\nStoryda ulashganingiz bizga juda katta motivatsiya beradi!\n\nMaxsus sovg'a sifatida sizga 10% chegirma:\nFRIEND10"],
                    ],
                    [
                        'node_id' => 'tpl_action_2',
                        'node_type' => 'action_add_tag',
                        'position' => ['x' => 250, 'y' => 350],
                        'data' => ['tag' => 'ambassador'],
                    ],
                ],
                'edges' => [
                    ['edge_id' => 'tpl_e1', 'source_node_id' => 'tpl_trigger_1', 'target_node_id' => 'tpl_action_1', 'source_handle' => null],
                    ['edge_id' => 'tpl_e2', 'source_node_id' => 'tpl_action_1', 'target_node_id' => 'tpl_action_2', 'source_handle' => null],
                ],
            ],
            [
                'id' => 9,
                'name' => 'Story replyga javob',
                'description' => 'Sizning storyingizga javob yozganlarga avtomatik xabar',
                'category' => 'engagement',
                'icon' => 'reply',
                'usage_count' => 167,
                'nodes' => [
                    [
                        'node_id' => 'tpl_trigger_1',
                        'node_type' => 'trigger_story_reply',
                        'position' => ['x' => 250, 'y' => 50],
                        'data' => [],
                    ],
                    [
                        'node_id' => 'tpl_action_1',
                        'node_type' => 'action_send_dm',
                        'position' => ['x' => 250, 'y' => 200],
                        'data' => ['message' => "Rahmat javobingiz uchun {name}!\n\nSizga qanday yordam bera olaman?"],
                    ],
                    [
                        'node_id' => 'tpl_action_2',
                        'node_type' => 'action_add_tag',
                        'position' => ['x' => 250, 'y' => 350],
                        'data' => ['tag' => 'story-engaged'],
                    ],
                ],
                'edges' => [
                    ['edge_id' => 'tpl_e1', 'source_node_id' => 'tpl_trigger_1', 'target_node_id' => 'tpl_action_1', 'source_handle' => null],
                    ['edge_id' => 'tpl_e2', 'source_node_id' => 'tpl_action_1', 'target_node_id' => 'tpl_action_2', 'source_handle' => null],
                ],
            ],

            // ========== AI SHABLONLARI ==========
            [
                'id' => 10,
                'name' => 'AI yordamchi chatbot',
                'description' => 'Barcha DM xabarlarga AI yordamida avtomatik javob berish',
                'category' => 'ai',
                'icon' => 'sparkles',
                'usage_count' => 278,
                'nodes' => [
                    [
                        'node_id' => 'tpl_trigger_1',
                        'node_type' => 'trigger_keyword_dm',
                        'position' => ['x' => 250, 'y' => 50],
                        'data' => ['keywords' => '__all__', 'exact_match' => 'contains'],
                    ],
                    [
                        'node_id' => 'tpl_action_1',
                        'node_type' => 'action_ai_response',
                        'position' => ['x' => 250, 'y' => 200],
                        'data' => [
                            'context' => 'Biz online do\'konmiz. Mahsulotlarimiz haqida savolarga do\'stona va professional tarzda javob bering. Narxlar, yetkazib berish va to\'lov usullari haqida ma\'lumot bering.',
                            'tone' => 'friendly',
                        ],
                    ],
                    [
                        'node_id' => 'tpl_action_2',
                        'node_type' => 'action_add_tag',
                        'position' => ['x' => 250, 'y' => 350],
                        'data' => ['tag' => 'ai-served'],
                    ],
                ],
                'edges' => [
                    ['edge_id' => 'tpl_e1', 'source_node_id' => 'tpl_trigger_1', 'target_node_id' => 'tpl_action_1', 'source_handle' => null],
                    ['edge_id' => 'tpl_e2', 'source_node_id' => 'tpl_action_1', 'target_node_id' => 'tpl_action_2', 'source_handle' => null],
                ],
            ],
        ];
    }
}
