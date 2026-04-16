<?php

namespace App\Services\Marketing\Orchestrator;

/**
 * Cross-module insights — qismlar orasidagi bog'liqliklarni topadi.
 *
 * Masalan:
 *   - "Kontent 0%, ammo sizda 3 ta kanal bor — foydalanmayapsiz"
 *   - "Raqobatchi yangi aksiya chiqargan, lekin siz javob bermadingiz"
 *   - "Dream Buyer yosh 25-35, lekin kontent 40+ ga mo'ljallangan"
 */
class CrossInsightsDetector
{
    public function detect(array $data): array
    {
        $insights = [];

        // Insight 1: Kanal bor lekin kontent yo'q
        if ($data['channels']['active_count'] > 0 && $data['content']['published'] === 0) {
            $insights[] = [
                'type' => 'underused_channels',
                'severity' => 'high',
                'title' => 'Kanallar bor, lekin kontent yo\'q',
                'message' => $data['channels']['active_count'] . ' ta kanal ulangan, ammo 30 kunda 0 post chiqqan.',
                'recommendation' => 'AI yordamida kontent rejasini yarating (Marketing > ContentAI)',
            ];
        }

        // Insight 2: Taklif bor, kampaniya yo'q
        if ($data['offers']['active'] > 0 && $data['campaigns']['active'] === 0) {
            $insights[] = [
                'type' => 'offers_without_campaigns',
                'severity' => 'medium',
                'title' => 'Takliflar reklama qilinmayapti',
                'message' => $data['offers']['active'] . ' ta faol taklif bor, lekin kampaniya ishga tushmagan.',
                'recommendation' => 'Har taklif uchun kampaniya yarating (Marketing > Kampaniyalar)',
            ];
        }

        // Insight 3: Dream Buyer lekin kontent mos emas
        if ($data['dream_buyer']['exists'] && $data['content']['avg_engagement'] < 1.5) {
            $insights[] = [
                'type' => 'content_mismatch',
                'severity' => 'medium',
                'title' => 'Kontent ideal mijozga mos kelmayapti',
                'message' => 'Engagement ' . $data['content']['avg_engagement'] . '% — ideal mijoz bilan uyg\'unlik past.',
                'recommendation' => 'Top postlarni tahlil qilib, nima ishlaganini Style Guide\'ga yozing',
            ];
        }

        // Insight 4: Raqobatchi bor, intelligence yo'q
        if ($data['competitors']['total'] > 0 && !$data['competitors']['monitoring_active']) {
            $insights[] = [
                'type' => 'blind_to_competitors',
                'severity' => 'medium',
                'title' => 'Raqobatchilarni "ko\'rmaysiz"',
                'message' => $data['competitors']['total'] . ' ta raqobatchi qo\'shilgan, lekin faoliyatlari kuzatilmayapti.',
                'recommendation' => 'Avtomatik monitoring yoqing — har hafta digest olasiz',
            ];
        }

        // Insight 5: Yuqori ROAS + kam sarflash
        if ($data['kpi']['roas'] > 3 && $data['kpi']['spend_30d'] < 1_000_000) {
            $insights[] = [
                'type' => 'scale_opportunity',
                'severity' => 'high',
                'title' => 'Masshtab oshirish imkoniyati!',
                'message' => 'ROAS ' . $data['kpi']['roas'] . 'x — ajoyib. Lekin sarflash kam.',
                'recommendation' => 'Ishlayotgan kampaniyalarni kengaytiring — budjet oshiring',
            ];
        }

        // Insight 6: Past ROAS — audit kerak
        if ($data['kpi']['roas'] > 0 && $data['kpi']['roas'] < 1 && $data['kpi']['spend_30d'] > 500_000) {
            $insights[] = [
                'type' => 'loss_making',
                'severity' => 'critical',
                'title' => 'Zarar keltiruvchi reklama',
                'message' => 'ROAS ' . $data['kpi']['roas'] . 'x, sarflash ' . number_format($data['kpi']['spend_30d']) . " so'm. Zarar keltirmoqda.",
                'recommendation' => 'Qaysi kanal zarar? Darhol to\'xtating yoki qayta sozlang',
            ];
        }

        // Insight 7: Setup to'liq, lekin performance past
        if ($data['dream_buyer']['exists'] && $data['channels']['active_count'] >= 2 && $data['content']['avg_engagement'] < 1) {
            $insights[] = [
                'type' => 'setup_ok_execution_poor',
                'severity' => 'high',
                'title' => 'Setup bor, ammo bajarilishi zaif',
                'message' => 'Hamma narsa sozlangan, lekin natija past. Muammo — ijrochilik yoki Style guide.',
                'recommendation' => 'Style Guide yarating va content reja tuzing',
            ];
        }

        return $insights;
    }
}
