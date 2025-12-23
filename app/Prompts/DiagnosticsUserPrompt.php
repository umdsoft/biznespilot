<?php

namespace App\Prompts;

class DiagnosticsUserPrompt
{
    /**
     * Build the user prompt for AI diagnostics
     */
    public static function build(array $businessData, array $benchmarks, array $successStories): string
    {
        $businessJson = json_encode($businessData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $benchmarksJson = json_encode($benchmarks, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $storiesJson = json_encode($successStories, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
## BIZNES MA'LUMOTLARI

{$businessJson}

### Soha benchmark ma'lumotlari:
{$benchmarksJson}

### O'xshash bizneslar muvaffaqiyat tarixi:
{$storiesJson}

---

## VAZIFA

Yuqoridagi ma'lumotlar asosida to'liq diagnostika qil.
MUHIM: Biznes nomini "{$businessData['business_name']}" ishlatib, shaxsiylashtirilgan tahlil ber.

FAQAT quyidagi JSON strukturasida javob ber:

PROMPT . self::getJsonStructure();
    }

    /**
     * Get the expected JSON structure template
     */
    private static function getJsonStructure(): string
    {
        return <<<'JSON'
{
    "overall_score": <0-100>,
    "status_level": "<critical|weak|medium|good|excellent>",
    "status_message": "<Biznes nomini ishlatib 2-3 gap bilan shaxsiy xulosa>",
    "industry_avg_score": <soha o'rtachasi>,

    "category_scores": {
        "marketing": <0-100>,
        "sales": <0-100>,
        "content": <0-100>,
        "funnel": <0-100>,
        "automation": <0-100>
    },

    "money_loss_analysis": {
        "monthly_loss": <oylik yo'qotish so'mda>,
        "yearly_loss": <yillik yo'qotish>,
        "daily_loss": <kunlik yo'qotish>,
        "breakdown": [
            {
                "problem": "<muammo nomi o'zbekcha>",
                "amount": <oylik zarar>,
                "category": "<marketing|sales|content|funnel>",
                "solution_module": "<module route>",
                "solution_title": "<module nomi o'zbekcha>"
            }
        ]
    },

    "roi_calculations": {
        "summary": {
            "total_investment": {
                "time_hours": <jami vaqt soatda>,
                "time_value_uzs": <vaqt qiymati so'mda>,
                "money_uzs": <to'g'ridan-to'g'ri xarajat>,
                "total_uzs": <jami investitsiya>
            },
            "total_monthly_return": <oylik kutilayotgan daromad>,
            "overall_roi_percent": <umumiy ROI foizi>,
            "payback_days": <qaytarilish kunlari>
        },
        "per_action": [
            {
                "id": <tartib raqami>,
                "action": "<harakat nomi o'zbekcha>",
                "priority": <1-10 ustuvorlik>,
                "investment": {
                    "time": "<vaqt (masalan '30 daqiqa')>",
                    "time_value": <vaqt qiymati so'mda>,
                    "money": <pul xarajati>,
                    "total": <jami investitsiya>
                },
                "expected_return": {
                    "metric": "<qaysi metrika yaxshilanadi>",
                    "improvement": "<foiz yoki raqam (masalan '+40%')>",
                    "monthly_gain": <oylik daromad o'sishi so'mda>,
                    "description": "<natija tavsifi o'zbekcha>"
                },
                "roi_percent": <ROI foizi>,
                "payback_days": <qaytarilish kunlari>,
                "module_route": "<modul route>",
                "difficulty": "<oson|o'rta|qiyin>",
                "verdict": "<JUDA SAMARALI ✅ yoki SAMARALI yoki O'RTA>"
            }
        ]
    },

    "cause_effect_matrix": [
        {
            "id": <tartib raqami>,
            "problem": "<muammo nomi o'zbekcha>",
            "current_impact": "<hozirgi ta'sir tavsifi>",
            "monthly_loss": <oylik yo'qotish so'mda>,
            "solution": {
                "action": "<nima qilish kerak>",
                "module": "<modul nomi>",
                "module_route": "<route>",
                "time": "<vaqt (masalan '30 daqiqa')>",
                "difficulty": "<oson|o'rta|qiyin>"
            },
            "expected_result": {
                "metric": "<metrika nomi>",
                "improvement": "<foiz (masalan '+40%')>",
                "monthly_gain": <oylik daromad o'sishi so'mda>
            },
            "roi_percent": <ROI foizi>,
            "payback_days": <qaytarilish kunlari>,
            "priority": <1-10 ustuvorlik>
        }
    ],

    "quick_strategies": {
        "marketing": {
            "target_audience": "<asosiy auditoriya tavsifi>",
            "content_frequency": {
                "instagram_posts": <haftalik postlar soni>,
                "instagram_stories": <kunlik stories soni>,
                "telegram_posts": <kunlik postlar soni>
            },
            "best_times": ["<eng yaxshi vaqtlar>"],
            "weekly_budget": <haftalik byudjet so'mda>,
            "expected_results": {
                "reach_increase": "<o'sish foizi>",
                "leads_increase": "<leadlar o'sishi>"
            }
        },
        "sales": {
            "current_conversion": <hozirgi konversiya foizi>,
            "target_conversion": <maqsad konversiya foizi>,
            "pricing_recommendation": {
                "basic": {"price": <narx so'mda>, "target_percent": <foiz>},
                "standard": {"price": <narx so'mda>, "target_percent": <foiz>},
                "premium": {"price": <narx so'mda>, "target_percent": <foiz>}
            },
            "top_objections": [
                {
                    "objection": "<e'tiroz matni>",
                    "response": "<javob matni>",
                    "success_rate": <muvaffaqiyat foizi>
                }
            ]
        },
        "advertising": {
            "monthly_budget": <oylik reklama byudjeti so'mda>,
            "channel_split": {
                "instagram": {"percent": <foiz>, "expected_leads": <leadlar soni>},
                "telegram": {"percent": <foiz>, "expected_leads": <leadlar soni>},
                "facebook": {"percent": <foiz>, "expected_leads": <leadlar soni>},
                "google": {"percent": <foiz>, "expected_leads": <leadlar soni>},
                "retargeting": {"percent": <foiz>, "expected_leads": <leadlar soni>}
            },
            "expected_roas": <kutilayotgan ROAS foizi>
        }
    },

    "ideal_customer_analysis": {
        "score": <0-100>,
        "completeness_percent": <to'ldirilganlik foizi>,
        "demographics": "<yosh, kasb, joylashuv haqida matn>",
        "pain_points": ["<asosiy muammolar>"],
        "desires": ["<xohishlar>"],
        "behavior": "<xulq-atvor tavsifi>",
        "channels": {"telegram": <foiz>, "instagram": <foiz>},
        "missing_fields": ["<to'ldirilmagan maydonlar>"],
        "recommendation": "<tavsiya o'zbekcha>"
    },

    "offer_strength": {
        "score": <0-100>,
        "value_score": <1-10>,
        "uniqueness_score": <1-10>,
        "urgency_score": <1-10>,
        "guarantee_score": <1-10>,
        "improvements": [
            "<yaxshilash tavsiyasi>"
        ]
    },

    "channels_analysis": {
        "channels": [
            {
                "name": "<kanal nomi>",
                "effectiveness": "<high|medium|low>",
                "recommendation": "<tavsiya o'zbekcha>",
                "score": <0-100>,
                "connected": <true|false>
            }
        ],
        "recommended_channels": ["<yangi kanal nomlari>"]
    },

    "funnel_analysis": {
        "overall_conversion": <umumiy konversiya foizi>,
        "stages": [
            {
                "name": "<bosqich nomi o'zbekcha>",
                "conversion_rate": <foizi>,
                "health": "<good|warning|bad>",
                "count": <soni>,
                "problem": "<muammo>",
                "solution": "<yechim>"
            }
        ],
        "bottlenecks": ["<muammoli joylar>"],
        "biggest_leak": {
            "stage": "<qaysi bosqichda>",
            "loss_percent": <yo'qotish foizi>,
            "estimated_loss": <taxminiy zarar so'mda>
        }
    },

    "automation_analysis": {
        "score": <0-100>,
        "chatbot_enabled": <true|false>,
        "followup_enabled": <true|false>,
        "lost_leads_percent": <yo'qotilgan leadlar foizi>,
        "recommendations": ["<tavsiyalar>"]
    },

    "risks": {
        "threats": [
            "<xavf tavsifi o'zbekcha>"
        ],
        "opportunities": [
            "<imkoniyat tavsifi o'zbekcha>"
        ]
    },

    "swot": {
        "strengths": ["<kuchli tomonlar>"],
        "weaknesses": ["<zaif tomonlar>"],
        "opportunities": ["<imkoniyatlar>"],
        "threats": ["<xavflar>"]
    },

    "action_plan": {
        "total_time_hours": <jami vaqt soatda>,
        "total_potential_savings": <jami tejash so'mda>,
        "steps": [
            {
                "order": <tartib raqami>,
                "title": "<qadam nomi o'zbekcha>",
                "module_route": "/onboarding/dream-buyer",
                "module_name": "<modul nomi>",
                "time_minutes": <vaqt daqiqada>,
                "impact_stars": <1-5>,
                "why": "<nima uchun kerak o'zbekcha>",
                "similar_business_result": "<o'xshash biznes natijasi>",
                "timeline": "<today|this_week|next_week>"
            }
        ]
    },

    "expected_results": {
        "now": {
            "score": <hozirgi ball>,
            "leads_weekly": <haftalik leadlar>,
            "conversion": <konversiya foizi>,
            "revenue_change": 0
        },
        "30_days": {
            "score": <ball>,
            "leads_weekly": <leadlar>,
            "conversion": <foiz>,
            "revenue_change": <o'zgarish foizi>
        },
        "60_days": {
            "score": <ball>,
            "leads_weekly": <leadlar>,
            "conversion": <foiz>,
            "revenue_change": <o'zgarish foizi>
        },
        "90_days": {
            "score": <ball>,
            "leads_weekly": <leadlar>,
            "conversion": <foiz>,
            "revenue_change": <o'zgarish foizi>
        }
    },

    "platform_recommendations": [
        {
            "module": "<modul nomi>",
            "reason": "<sabab o'zbekcha>",
            "priority": "<yuqori|o'rta|past>",
            "route": "<route>"
        }
    ],

    "recommended_videos": [
        {
            "title": "<video nomi o'zbekcha>",
            "duration": "<davomiyligi>",
            "url": "<route>",
            "related_module": "<tegishli modul>"
        }
    ]
}
JSON;
    }
}
