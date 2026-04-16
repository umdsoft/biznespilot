<?php

namespace App\Services\Marketing\Orchestrator;

/**
 * Marketing health score — 0-100 ball.
 *
 * 5 ta soha:
 *   - Setup (25%): Dream Buyer, Style Guide, Channels
 *   - Content (25%): Haftalik post, engagement
 *   - Offers (15%): Faol takliflar
 *   - Campaigns (15%): Faol kampaniyalar
 *   - Performance (20%): ROAS, revenue
 */
class HealthCalculator
{
    public function calculate(array $data): array
    {
        $setup = $this->calculateSetup($data);
        $content = $this->calculateContent($data);
        $offers = $this->calculateOffers($data);
        $campaigns = $this->calculateCampaigns($data);
        $performance = $this->calculatePerformance($data);

        $overall = (int) round(
            $setup * 0.25
            + $content * 0.25
            + $offers * 0.15
            + $campaigns * 0.15
            + $performance * 0.20
        );

        return [
            'overall' => $overall,
            'grade' => $this->getGrade($overall),
            'setup' => $setup,
            'content' => $content,
            'offers' => $offers,
            'campaigns' => $campaigns,
            'performance' => $performance,
            'weak_areas' => $this->identifyWeakAreas($setup, $content, $offers, $campaigns, $performance),
        ];
    }

    private function calculateSetup(array $data): int
    {
        $score = 0;
        if ($data['dream_buyer']['exists']) $score += 40;
        if ($data['content']['has_style_guide']) $score += 20;
        if ($data['channels']['active_count'] >= 2) $score += 40;
        elseif ($data['channels']['active_count'] >= 1) $score += 20;

        return min(100, $score);
    }

    private function calculateContent(array $data): int
    {
        $published = $data['content']['published'] ?? 0;
        $engagement = $data['content']['avg_engagement'] ?? 0;

        // 30 kunda 15+ post — 50 ball
        $quantityScore = min(50, $published * 3);

        // Engagement — 50 ball (5% = max)
        $engagementScore = min(50, $engagement * 10);

        return (int) round($quantityScore + $engagementScore);
    }

    private function calculateOffers(array $data): int
    {
        $active = $data['offers']['active'] ?? 0;
        if ($active >= 3) return 100;
        if ($active >= 2) return 75;
        if ($active >= 1) return 50;
        return 0;
    }

    private function calculateCampaigns(array $data): int
    {
        $active = $data['campaigns']['active'] ?? 0;
        if ($active >= 2) return 100;
        if ($active >= 1) return 60;
        return 0;
    }

    private function calculatePerformance(array $data): int
    {
        $roas = $data['kpi']['roas'] ?? 0;
        if ($roas >= 3) return 100;
        if ($roas >= 2) return 75;
        if ($roas >= 1) return 50;
        if ($roas > 0) return 25;
        return 0;
    }

    private function identifyWeakAreas(int $setup, int $content, int $offers, int $campaigns, int $performance): array
    {
        $weak = [];
        $areas = [
            'setup' => $setup, 'content' => $content, 'offers' => $offers,
            'campaigns' => $campaigns, 'performance' => $performance,
        ];

        foreach ($areas as $area => $score) {
            if ($score < 50) $weak[] = ['area' => $area, 'score' => $score];
        }

        usort($weak, fn($a, $b) => $a['score'] - $b['score']);
        return $weak;
    }

    private function getGrade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 75 => 'B',
            $score >= 60 => 'C',
            $score >= 40 => 'D',
            default => 'F',
        };
    }
}
