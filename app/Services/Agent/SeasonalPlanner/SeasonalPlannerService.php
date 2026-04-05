<?php

namespace App\Services\Agent\SeasonalPlanner;

use App\Services\AI\AIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Mavsumiy rejalashtiruvchi — O'zbekiston bayramlari va mavsumiy kampaniyalar.
 * Har bayramdan 2-3 hafta oldin reja tayyorlaydi.
 *
 * Gibrid: kalendar va shablon (bepul) + Haiku bilan shaxsiylashtirish
 */
class SeasonalPlannerService
{
    public function __construct(
        private AIService $aiService,
    ) {}

    /**
     * Keyingi 30 kun ichidagi bayramlarni tekshirish
     */
    public function checkUpcomingEvents(string $businessId): array
    {
        try {
            $industry = DB::table('businesses')->where('id', $businessId)->value('industry_code');
            if (!$industry) {
                return ['success' => true, 'events' => [], 'message' => 'Soha aniqlanmagan'];
            }

            // Keyingi 30 kun ichidagi bayramlar
            $today = now();
            $events = DB::table('local_calendar')
                ->where('is_active', true)
                ->get()
                ->filter(function ($event) use ($today, $industry) {
                    // Bu bayram shu sohaga ta'sir qiladimi
                    $industries = json_decode($event->impact_industries, true) ?? [];
                    if (!in_array($industry, $industries)) return false;

                    // Sanani aniqlash
                    $eventDate = $this->resolveEventDate($event);
                    if (!$eventDate) return false;

                    $daysUntil = $today->diffInDays($eventDate, false);
                    return $daysUntil >= 0 && $daysUntil <= 30;
                })
                ->map(function ($event) use ($today) {
                    $eventDate = $this->resolveEventDate($event);
                    return [
                        'name' => $event->event_name,
                        'type' => $event->event_type,
                        'date' => $eventDate?->format('Y-m-d'),
                        'days_until' => $today->diffInDays($eventDate, false),
                        'preparation_days' => $event->preparation_days,
                        'impact' => $event->impact_description,
                        'needs_preparation' => $today->diffInDays($eventDate, false) <= $event->preparation_days,
                    ];
                })
                ->values()
                ->toArray();

            return ['success' => true, 'events' => $events];

        } catch (\Exception $e) {
            Log::error('SeasonalPlanner: xatolik', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Bayram uchun kampaniya reja yaratish
     */
    public function generateCampaignPlan(string $businessId, array $event): array
    {
        try {
            $business = DB::table('businesses')->where('id', $businessId)->first(['name', 'industry_code', 'category']);

            $prompt = "Bayram: {$event['name']} ({$event['date']})\n"
                . "Biznes: {$business->name} (soha: {$business->industry_code})\n"
                . "Ta'sir: {$event['impact']}\n"
                . "Tayyorlanish uchun {$event['days_until']} kun qoldi.\n\n"
                . "3 bosqichli kampaniya reja tuzing:\n"
                . "1. Oldindan tayyorgarlik (kontent, aksiya)\n"
                . "2. Bayram kunlari (asosiy aksiya)\n"
                . "3. Bayramdan keyin (yakunlash, qayta xarid)";

            $response = $this->aiService->ask(
                prompt: $prompt,
                systemPrompt: "Sen O'zbekiston bozori uchun marketing strategisan. Bayram kampaniya rejasini tuz. O'zbek tilida, aniq va amaliy. Har bosqich uchun: nima qilish, qachon, qaysi kanalda.",
                preferredModel: 'haiku',
                maxTokens: 800,
                businessId: $businessId,
                agentType: 'seasonal_planner',
            );

            return [
                'success' => true,
                'event' => $event['name'],
                'plan' => $response->content,
                'tokens' => $response->tokensInput + $response->tokensOutput,
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Bayram sanasini aniqlash
     */
    private function resolveEventDate(object $event): ?\Carbon\Carbon
    {
        // Aniq sana belgilangan bo'lsa
        if ($event->year_date) {
            return \Carbon\Carbon::parse($event->year_date);
        }

        // Qat'iy sana bo'lsa (masalan, 03-08 = 8-mart)
        if ($event->fixed_date) {
            $year = now()->year;
            return \Carbon\Carbon::createFromFormat('Y-m-d', "{$year}-{$event->fixed_date}");
        }

        return null;
    }
}
