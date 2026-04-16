<?php

namespace App\Services\Marketing\Orchestrator;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Smart Content Calendar — haftalik avtomatik reja generator.
 *
 * Manbalarni sinxronlaydi:
 *   - Top performer naqshlari (ContentFeedbackLoop dan)
 *   - Dream Buyer muammolari
 *   - Faol kampaniyalar
 *   - Mavsumiy hodisalar
 *   - Raqobatchi trendlar
 */
class SmartContentCalendar
{
    // Optimal post vaqtlari (ishlangan tahlillar asosida)
    private const OPTIMAL_TIMES = [
        'instagram' => ['10:00', '18:30', '21:00'],
        'telegram' => ['09:00', '12:30', '19:00'],
        'facebook' => ['11:00', '15:00', '20:00'],
    ];

    /**
     * Haftalik calendar generate qilish
     */
    public function generateWeek(string $businessId, ?string $startDate = null): array
    {
        try {
            $start = $startDate ? \Carbon\Carbon::parse($startDate) : now()->startOfWeek()->addWeek();
            $end = (clone $start)->addDays(6);

            $channels = DB::table('marketing_channels')
                ->where('business_id', $businessId)
                ->where('is_active', true)
                ->pluck('type')
                ->toArray();

            if (empty($channels)) {
                return ['success' => false, 'error' => 'Faol kanal yo\'q'];
            }

            // Kontent g'oyalari manbalari
            $ideas = $this->gatherContentIdeas($businessId);

            // 7 kunlik reja
            $plan = [];
            $dateIter = clone $start;

            for ($day = 0; $day < 7; $day++) {
                $posts = $this->planDayContent($dateIter, $channels, $ideas);
                $plan[] = [
                    'date' => $dateIter->toDateString(),
                    'day_name' => $dateIter->translatedFormat('l'),
                    'posts' => $posts,
                ];
                $dateIter->addDay();
            }

            return [
                'success' => true,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'total_posts' => array_sum(array_map(fn($d) => count($d['posts']), $plan)),
                'channels_used' => array_unique(array_merge(...array_map(fn($d) => array_column($d['posts'], 'channel'), $plan))),
                'plan' => $plan,
            ];
        } catch (\Exception $e) {
            Log::error('SmartContentCalendar xato', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Rejani content_calendar ga yozish
     */
    public function saveToCalendar(string $businessId, array $plan): array
    {
        $saved = 0;

        foreach ($plan as $day) {
            foreach ($day['posts'] as $post) {
                try {
                    $id = Str::uuid()->toString();
                    DB::table('content_calendar')->insert([
                        'id' => $id,
                        'uuid' => $id,
                        'business_id' => $businessId,
                        'title' => $post['title'],
                        'description' => $post['description'] ?? null,
                        'content_type' => $post['content_type'] ?? 'post',
                        'channel' => $post['channel'],
                        'scheduled_date' => $day['date'],
                        'scheduled_time' => $post['time'],
                        'scheduled_at' => $day['date'] . ' ' . $post['time'],
                        'status' => 'planned',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $saved++;
                } catch (\Exception $e) {
                    Log::warning('Calendar save xato', ['error' => $e->getMessage()]);
                }
            }
        }

        return ['success' => true, 'saved' => $saved];
    }

    /**
     * Manbalardan kontent g'oyalari yig'ish
     */
    private function gatherContentIdeas(string $businessId): array
    {
        $ideas = [];

        // 1. Mavjud content_ideas jadvalidan
        try {
            $existing = DB::table('content_ideas')
                ->where('business_id', $businessId)
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get(['title', 'description']);

            foreach ($existing as $idea) {
                $ideas[] = ['title' => $idea->title, 'source' => 'idea_db', 'description' => $idea->description];
            }
        } catch (\Exception $e) {}

        // 2. Faol takliflar — har biridan 1 post
        try {
            $offers = DB::table('offers')
                ->where('business_id', $businessId)
                ->where('status', 'active')
                ->limit(3)
                ->get(['name', 'description']);

            foreach ($offers as $offer) {
                $ideas[] = ['title' => 'Taklif: ' . $offer->name, 'source' => 'offer', 'description' => $offer->description];
            }
        } catch (\Exception $e) {}

        // 3. Dream Buyer muammolaridan
        try {
            $buyer = DB::table('dream_buyers')->where('business_id', $businessId)->first();
            if ($buyer && !empty($buyer->pains)) {
                $pains = is_string($buyer->pains) ? json_decode($buyer->pains, true) : $buyer->pains;
                if (is_array($pains)) {
                    foreach (array_slice($pains, 0, 3) as $pain) {
                        if (is_string($pain)) {
                            $ideas[] = ['title' => 'Muammo: ' . $pain, 'source' => 'dream_buyer'];
                        }
                    }
                }
            }
        } catch (\Exception $e) {}

        // 4. Default templates (agar yetarli bo'lmasa)
        if (count($ideas) < 10) {
            $defaults = [
                'Mijozlar guvohliklari (social proof)',
                'Xizmat narxlari va paketlar',
                'Foyda va natijalar haqida',
                'Mijoz tajribasi (case study)',
                'Savol-javoblar (FAQ)',
                'Biznes orqa tomoni (behind the scenes)',
                'Haftalik tavsiya va maslahatlar',
            ];
            foreach ($defaults as $d) {
                $ideas[] = ['title' => $d, 'source' => 'template'];
            }
        }

        return $ideas;
    }

    /**
     * Bir kun uchun kontent rejasi
     */
    private function planDayContent(\Carbon\Carbon $date, array $channels, array $ideas): array
    {
        // Dushanba, Chorshanba, Juma — 2 ta post
        // Qolgan kunlar — 1 ta post
        $dayOfWeek = $date->dayOfWeek;
        $postCount = in_array($dayOfWeek, [1, 3, 5]) ? 2 : 1;

        $posts = [];
        for ($i = 0; $i < $postCount && !empty($ideas); $i++) {
            $idea = array_shift($ideas);
            $channel = $channels[($dayOfWeek + $i) % count($channels)];
            $times = self::OPTIMAL_TIMES[$channel] ?? ['10:00', '18:00'];
            $time = $times[$i % count($times)];

            $posts[] = [
                'title' => $idea['title'],
                'description' => $idea['description'] ?? null,
                'source' => $idea['source'],
                'channel' => $channel,
                'time' => $time,
                'content_type' => $i === 0 ? 'post' : 'story',
            ];
        }

        return $posts;
    }
}
