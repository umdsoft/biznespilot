<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramFlowNode extends Model
{
    protected $fillable = [
        'automation_id',
        'node_id',
        'node_type',
        'data',
        'position',
    ];

    protected $casts = [
        'data' => 'array',
        'position' => 'array',
    ];

    // Node categories
    const CATEGORY_TRIGGER = 'trigger';
    const CATEGORY_CONDITION = 'condition';
    const CATEGORY_ACTION = 'action';

    // Node type definitions with metadata
    public static function getNodeTypes(): array
    {
        return [
            // Triggerlar
            'trigger_keyword_dm' => [
                'category' => 'trigger',
                'label' => 'DM kalit so\'z',
                'description' => 'DM da kalit so\'z yozilganda ishga tushadi',
                'icon' => 'chat',
                'color' => 'purple',
                'fields' => [
                    [
                        'name' => 'keywords',
                        'type' => 'text',
                        'label' => 'Qaysi xabarlar uchun?',
                        'has_all_option' => true,
                        'all_label' => 'Barcha DM xabarlar',
                        'placeholder' => 'narx, price, baho (vergul bilan)',
                        'help' => 'Vergul bilan ajrating: narx, price, baho',
                    ],
                    ['name' => 'exact_match', 'type' => 'select', 'label' => 'Mos kelish turi', 'options' => [
                        ['value' => 'contains', 'label' => 'Ichida bor'],
                        ['value' => 'exact', 'label' => 'To\'liq mos'],
                    ]],
                ],
            ],
            'trigger_keyword_comment' => [
                'category' => 'trigger',
                'label' => 'Comment kalit so\'z',
                'description' => 'Commentda kalit so\'z yozilganda',
                'icon' => 'comment',
                'color' => 'purple',
                'fields' => [
                    [
                        'name' => 'keywords',
                        'type' => 'text',
                        'label' => 'Qaysi commentlar uchun?',
                        'has_all_option' => true,
                        'all_label' => 'Barcha commentlar',
                        'placeholder' => 'narx, info (vergul bilan)',
                        'help' => 'Barcha commentlarga javob berish uchun "Barchasi" ni tanlang',
                    ],
                    [
                        'name' => 'media_id',
                        'type' => 'post_select',
                        'label' => 'Qaysi post/reels uchun?',
                        'help' => 'Barcha kontentga yoki aniq postga qo\'llash',
                    ],
                ],
            ],
            'trigger_story_mention' => [
                'category' => 'trigger',
                'label' => 'Story mention',
                'description' => 'Story\'da sizni mention qilganda',
                'icon' => 'story_mention',
                'color' => 'purple',
                'fields' => [],
            ],
            'trigger_story_reply' => [
                'category' => 'trigger',
                'label' => 'Story reply',
                'description' => 'Sizning story\'ingizga javob yozganda',
                'icon' => 'story_reply',
                'color' => 'purple',
                'fields' => [],
            ],
            'trigger_new_follower' => [
                'category' => 'trigger',
                'label' => 'Yangi follower',
                'description' => 'Yangi odam follow qilganda',
                'icon' => 'new_follower',
                'color' => 'purple',
                'fields' => [],
            ],

            // Shartlar
            'condition_is_follower' => [
                'category' => 'condition',
                'label' => 'Obunachimi?',
                'description' => 'Foydalanuvchi sizga obuna bo\'lganmi tekshiradi',
                'icon' => 'is_follower',
                'color' => 'yellow',
                'has_branches' => true,
                'fields' => [],
            ],
            'condition_liked_post' => [
                'category' => 'condition',
                'label' => 'Like bosdimi?',
                'description' => 'Ma\'lum postga like bosganmi tekshiradi',
                'icon' => 'liked_post',
                'color' => 'yellow',
                'has_branches' => true,
                'fields' => [
                    [
                        'name' => 'media_id',
                        'type' => 'post_select',
                        'label' => 'Qaysi post/reels uchun?',
                        'help' => 'Like bosilganligini tekshirish uchun post tanlang',
                    ],
                ],
            ],
            'condition_saved_post' => [
                'category' => 'condition',
                'label' => 'Saqladimi?',
                'description' => 'Ma\'lum postni saqlaganmi tekshiradi',
                'icon' => 'saved_post',
                'color' => 'yellow',
                'has_branches' => true,
                'fields' => [
                    [
                        'name' => 'media_id',
                        'type' => 'post_select',
                        'label' => 'Qaysi post/reels uchun?',
                        'help' => 'Saqlanganligini tekshirish uchun post tanlang',
                    ],
                ],
            ],
            'condition_has_tag' => [
                'category' => 'condition',
                'label' => 'Tagi bormi?',
                'description' => 'Foydalanuvchida ma\'lum tag bormi',
                'icon' => 'has_tag',
                'color' => 'yellow',
                'has_branches' => true,
                'fields' => [
                    ['name' => 'tag', 'type' => 'text', 'label' => 'Tag nomi', 'placeholder' => 'Masalan: vip'],
                ],
            ],
            'condition_time_passed' => [
                'category' => 'condition',
                'label' => 'Vaqt o\'tdimi?',
                'description' => 'Oxirgi xabardan beri vaqt o\'tganmi',
                'icon' => 'time_passed',
                'color' => 'yellow',
                'has_branches' => true,
                'fields' => [
                    ['name' => 'hours', 'type' => 'number', 'label' => 'Soatlar soni', 'placeholder' => '24'],
                ],
            ],

            // Harakatlar
            'action_send_dm' => [
                'category' => 'action',
                'label' => 'DM yuborish',
                'description' => 'Matn xabari yuborish',
                'icon' => 'send_dm',
                'color' => 'green',
                'fields' => [
                    ['name' => 'message', 'type' => 'textarea', 'label' => 'Xabar matni', 'placeholder' => 'Salom {name}! Sizga qanday yordam bera olaman?', 'help' => '{name}, {username} o\'zgaruvchilaridan foydalaning'],
                ],
            ],
            'action_send_media' => [
                'category' => 'action',
                'label' => 'Media yuborish',
                'description' => 'Rasm yoki video yuborish',
                'icon' => 'send_media',
                'color' => 'green',
                'fields' => [
                    ['name' => 'media_url', 'type' => 'text', 'label' => 'Media URL', 'placeholder' => 'https://...'],
                    ['name' => 'caption', 'type' => 'textarea', 'label' => 'Izoh (ixtiyoriy)', 'placeholder' => 'Media haqida qisqacha'],
                ],
            ],
            'action_send_link' => [
                'category' => 'action',
                'label' => 'Link yuborish',
                'description' => 'Havola yuborish',
                'icon' => 'send_link',
                'color' => 'green',
                'fields' => [
                    ['name' => 'url', 'type' => 'text', 'label' => 'URL', 'placeholder' => 'https://example.com'],
                    ['name' => 'message', 'type' => 'textarea', 'label' => 'Xabar', 'placeholder' => 'Mana sizga link!'],
                ],
            ],
            'action_add_tag' => [
                'category' => 'action',
                'label' => 'Tag qo\'shish',
                'description' => 'Foydalanuvchiga tag biriktirish',
                'icon' => 'add_tag',
                'color' => 'green',
                'fields' => [
                    ['name' => 'tag', 'type' => 'text', 'label' => 'Tag nomi', 'placeholder' => 'Masalan: interested'],
                ],
            ],
            'action_remove_tag' => [
                'category' => 'action',
                'label' => 'Tag olib tashlash',
                'description' => 'Foydalanuvchidan tagni olib tashlash',
                'icon' => 'remove_tag',
                'color' => 'green',
                'fields' => [
                    ['name' => 'tag', 'type' => 'text', 'label' => 'Tag nomi', 'placeholder' => 'O\'chiriladigan tag'],
                ],
            ],
            'action_delay' => [
                'category' => 'action',
                'label' => 'Kutish',
                'description' => 'Ma\'lum vaqt kutish',
                'icon' => 'delay',
                'color' => 'green',
                'fields' => [
                    ['name' => 'delay_type', 'type' => 'select', 'label' => 'Vaqt turi', 'options' => [
                        ['value' => 'seconds', 'label' => 'Soniya'],
                        ['value' => 'minutes', 'label' => 'Daqiqa'],
                        ['value' => 'hours', 'label' => 'Soat'],
                    ]],
                    ['name' => 'delay_value', 'type' => 'number', 'label' => 'Qiymat', 'placeholder' => '5'],
                ],
            ],
            'action_reply_comment' => [
                'category' => 'action',
                'label' => 'Commentga javob',
                'description' => 'Commentga javob yozish',
                'icon' => 'reply_comment',
                'color' => 'green',
                'fields' => [
                    ['name' => 'message', 'type' => 'textarea', 'label' => 'Javob matni', 'placeholder' => 'Rahmat! DM ga yozing!'],
                ],
            ],
            'action_ai_response' => [
                'category' => 'action',
                'label' => 'AI javob',
                'description' => 'Sun\'iy intellekt yordamida javob',
                'icon' => 'ai_response',
                'color' => 'green',
                'fields' => [
                    ['name' => 'context', 'type' => 'textarea', 'label' => 'AI uchun kontekst', 'placeholder' => 'Biz kiyim do\'konimiz. Narxlar 50,000 - 500,000 so\'m oralig\'ida.'],
                    ['name' => 'tone', 'type' => 'select', 'label' => 'Ohang', 'options' => [
                        ['value' => 'friendly', 'label' => 'Do\'stona'],
                        ['value' => 'professional', 'label' => 'Professional'],
                        ['value' => 'casual', 'label' => 'Oddiy'],
                    ]],
                ],
            ],
        ];
    }

    public function automation(): BelongsTo
    {
        return $this->belongsTo(InstagramAutomation::class, 'automation_id');
    }

    public function getNodeMeta(): array
    {
        $types = self::getNodeTypes();
        return $types[$this->node_type] ?? [];
    }

    public function getCategory(): string
    {
        $meta = $this->getNodeMeta();
        return $meta['category'] ?? 'unknown';
    }

    public function isTrigger(): bool
    {
        return $this->getCategory() === 'trigger';
    }

    public function isCondition(): bool
    {
        return $this->getCategory() === 'condition';
    }

    public function isAction(): bool
    {
        return $this->getCategory() === 'action';
    }

    public function hasBranches(): bool
    {
        $meta = $this->getNodeMeta();
        return $meta['has_branches'] ?? false;
    }
}
