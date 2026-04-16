<?php

namespace App\Services\Marketing\Orchestrator;

/**
 * Priority ranker — marketer ertalab nimalarni birinchi qilishi kerak.
 *
 * Har bir ishni tartiblab, ustuvorligini aniqlaydi:
 *   - severity: critical / high / medium / low
 *   - impact: yuqori / o'rta / past
 *   - effort: oson / o'rta / qiyin
 */
class PriorityRanker
{
    public function rank(array $data, array $health): array
    {
        $priorities = [];

        // 1. Dream Buyer yo'q — kritik
        if (!$data['dream_buyer']['exists']) {
            $priorities[] = [
                'severity' => 'critical',
                'area' => 'setup',
                'title' => 'Ideal mijoz profili yaratilmagan',
                'description' => 'Marketing samarali bo\'lishi uchun "Ideal Mijoz Portreti" kerak. Bu barcha kontent va reklama asosi.',
                'action' => 'Bosh sahifa > Ideal Mijoz',
                'impact' => 'very_high',
                'effort' => 'medium',
                'priority_score' => 95,
            ];
        }

        // 2. Kanallar ulanmagan
        if ($data['channels']['active_count'] === 0) {
            $priorities[] = [
                'severity' => 'critical',
                'area' => 'setup',
                'title' => 'Marketing kanallari ulanmagan',
                'description' => 'Instagram, Telegram yoki boshqa kanal ulash — keyin kontent chiqara olasiz.',
                'action' => 'Integratsiyalar > Ijtimoiy Tarmoqlar',
                'impact' => 'very_high',
                'effort' => 'easy',
                'priority_score' => 90,
            ];
        }

        // 3. Style guide yo'q
        if (!$data['content']['has_style_guide'] && $data['channels']['active_count'] > 0) {
            $priorities[] = [
                'severity' => 'high',
                'area' => 'content',
                'title' => 'Brand style guide yo\'q',
                'description' => 'Har post turlicha ohangda chiqmoqda. Style guide brend ovozini belgilaydi.',
                'action' => 'Marketing > ContentAI > Style Guide',
                'impact' => 'high',
                'effort' => 'easy',
                'priority_score' => 70,
            ];
        }

        // 4. Kontent kam
        if ($data['content']['published'] < 10) {
            $priorities[] = [
                'severity' => 'high',
                'area' => 'content',
                'title' => '30 kunda ' . $data['content']['published'] . ' ta post kam',
                'description' => 'Haftasiga 3-5 ta post tavsiya etiladi. AI yordamida tezroq yaratishingiz mumkin.',
                'action' => 'Marketing > ContentAI > Yangi post',
                'impact' => 'high',
                'effort' => 'easy',
                'priority_score' => 75,
            ];
        }

        // 5. Kampaniya yo'q
        if ($data['campaigns']['active'] === 0 && $data['offers']['active'] > 0) {
            $priorities[] = [
                'severity' => 'high',
                'area' => 'campaigns',
                'title' => 'Faol kampaniya yo\'q',
                'description' => $data['offers']['active'] . ' ta faol taklif bor, lekin birorta kampaniya ishga tushmagan.',
                'action' => 'Marketing > Kampaniyalar > Yangi',
                'impact' => 'very_high',
                'effort' => 'medium',
                'priority_score' => 80,
            ];
        }

        // 6. Raqobatchi monitoring yo'q
        if ($data['competitors']['total'] > 0 && !$data['competitors']['monitoring_active']) {
            $priorities[] = [
                'severity' => 'medium',
                'area' => 'competitors',
                'title' => 'Raqobatchilar kuzatilmayapti',
                'description' => $data['competitors']['total'] . ' ta raqobatchi qo\'shilgan, lekin faoliyatlari yozib olinmayapti.',
                'action' => 'Marketing > Raqobatchilar > Kuzatuv yoqish',
                'impact' => 'medium',
                'effort' => 'easy',
                'priority_score' => 50,
            ];
        }

        // 7. ROAS past
        if ($data['kpi']['roas'] > 0 && $data['kpi']['roas'] < 1) {
            $priorities[] = [
                'severity' => 'critical',
                'area' => 'performance',
                'title' => 'ROAS zarar keltirmoqda (' . $data['kpi']['roas'] . 'x)',
                'description' => 'Xarajat daromaddan ko\'p. Darhol tekshirish kerak: qaysi kanal zarar?',
                'action' => 'Tahlil > Kanal ROAS',
                'impact' => 'very_high',
                'effort' => 'medium',
                'priority_score' => 95,
            ];
        }

        // 8. Engagement past
        if ($data['content']['avg_engagement'] > 0 && $data['content']['avg_engagement'] < 1.5) {
            $priorities[] = [
                'severity' => 'medium',
                'area' => 'content',
                'title' => 'Engagement past (' . $data['content']['avg_engagement'] . '%)',
                'description' => 'Idealda 3-5%. Top performerlaringizni tahlil qilib, naqsh topish kerak.',
                'action' => 'Marketing > Content > Tahlil',
                'impact' => 'medium',
                'effort' => 'medium',
                'priority_score' => 45,
            ];
        }

        // Saralash priority_score bo'yicha
        usort($priorities, fn($a, $b) => $b['priority_score'] - $a['priority_score']);

        return $priorities;
    }
}
