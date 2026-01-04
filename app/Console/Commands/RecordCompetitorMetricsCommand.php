<?php

namespace App\Console\Commands;

use App\Models\Competitor;
use App\Services\CompetitorMonitoringService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RecordCompetitorMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'competitors:record-metrics
                            {competitor : Competitor ID}
                            {--date= : Date for metrics (default: today)}
                            {--instagram-followers= : Instagram followers count}
                            {--instagram-posts= : Instagram posts count}
                            {--instagram-engagement= : Instagram engagement rate (0-100)}
                            {--telegram-members= : Telegram channel members}
                            {--facebook-followers= : Facebook followers}
                            {--tiktok-followers= : TikTok followers}
                            {--youtube-subscribers= : YouTube subscribers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually record competitor metrics';

    /**
     * Execute the console command.
     */
    public function handle(CompetitorMonitoringService $service): int
    {
        $competitorId = $this->argument('competitor');
        $competitor = Competitor::find($competitorId);

        if (!$competitor) {
            $this->error("Raqobatchi topilmadi: {$competitorId}");
            return Command::FAILURE;
        }

        $this->info("Raqobatchi: {$competitor->name}");

        // Collect metrics from options
        $metrics = [];

        if ($value = $this->option('instagram-followers')) {
            $metrics['instagram_followers'] = (int) $value;
        }
        if ($value = $this->option('instagram-posts')) {
            $metrics['instagram_posts'] = (int) $value;
        }
        if ($value = $this->option('instagram-engagement')) {
            $metrics['instagram_engagement_rate'] = (float) $value;
        }
        if ($value = $this->option('telegram-members')) {
            $metrics['telegram_members'] = (int) $value;
        }
        if ($value = $this->option('facebook-followers')) {
            $metrics['facebook_followers'] = (int) $value;
        }
        if ($value = $this->option('tiktok-followers')) {
            $metrics['tiktok_followers'] = (int) $value;
        }
        if ($value = $this->option('youtube-subscribers')) {
            $metrics['youtube_subscribers'] = (int) $value;
        }

        if (empty($metrics)) {
            $this->warn("Hech qanday metrika ko'rsatilmadi. Kamida bitta metrikani kiriting.");
            $this->line('');
            $this->line('Masalan:');
            $this->line('  php artisan competitors:record-metrics abc123 --instagram-followers=15000');
            return Command::FAILURE;
        }

        $date = $this->option('date') ? Carbon::parse($this->option('date')) : null;

        $metric = $service->recordManualMetrics($competitor, $metrics, $date);

        $this->info("Metrikalar saqlandi:");
        $this->table(
            ['Metrika', 'Qiymat'],
            collect($metrics)->map(fn ($value, $key) => [$key, $value])->values()->toArray()
        );

        if ($metric->follower_growth_rate) {
            $this->info("O'sish tezligi: {$metric->follower_growth_rate}%");
        }

        return Command::SUCCESS;
    }
}
