<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeCompetitorData;
use App\Models\Business;
use App\Models\Competitor;
use Illuminate\Console\Command;

class MonitorCompetitorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'competitors:monitor
                            {--business= : Monitor competitors for specific business ID}
                            {--competitor= : Monitor specific competitor ID}
                            {--all : Monitor all active competitors with auto_monitor enabled}
                            {--force : Force monitoring regardless of check_frequency_hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor competitors and collect metrics from social media platforms';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Raqobatchilarni kuzatish boshlandi...');

        if ($competitorId = $this->option('competitor')) {
            return $this->monitorSingleCompetitor($competitorId);
        }

        if ($businessId = $this->option('business')) {
            return $this->monitorBusinessCompetitors($businessId);
        }

        if ($this->option('all')) {
            return $this->monitorAllCompetitors();
        }

        // Show help if no option provided
        $this->showOptions();

        return Command::SUCCESS;
    }

    /**
     * Monitor single competitor
     */
    protected function monitorSingleCompetitor(string $competitorId): int
    {
        $competitor = Competitor::find($competitorId);

        if (! $competitor) {
            $this->error("Raqobatchi topilmadi: {$competitorId}");

            return Command::FAILURE;
        }

        $this->info("Kuzatilmoqda: {$competitor->name}");

        ScrapeCompetitorData::dispatch($competitor->id);

        $this->info("Job ishga tushirildi. Queue worker'ni tekshiring.");

        return Command::SUCCESS;
    }

    /**
     * Monitor all competitors for a business
     */
    protected function monitorBusinessCompetitors(string $businessId): int
    {
        $business = Business::find($businessId);

        if (! $business) {
            $this->error("Biznes topilmadi: {$businessId}");

            return Command::FAILURE;
        }

        $competitors = Competitor::where('business_id', $businessId)
            ->where('status', 'active')
            ->get();

        $this->info("Biznes: {$business->name}");
        $this->info("Raqobatchilar soni: {$competitors->count()}");

        if ($competitors->isEmpty()) {
            $this->warn('Faol raqobatchilar topilmadi.');

            return Command::SUCCESS;
        }

        ScrapeCompetitorData::dispatch(null, $businessId);

        $this->info("Job ishga tushirildi. Queue worker'ni tekshiring.");

        return Command::SUCCESS;
    }

    /**
     * Monitor all competitors across all businesses
     */
    protected function monitorAllCompetitors(): int
    {
        $query = Competitor::where('status', 'active')
            ->where('auto_monitor', true);

        if (! $this->option('force')) {
            // Only monitor if it's time based on check_frequency_hours
            $query->where(function ($q) {
                $q->whereNull('last_checked_at')
                    ->orWhereRaw('TIMESTAMPDIFF(HOUR, last_checked_at, NOW()) >= check_frequency_hours');
            });
        }

        $competitors = $query->get();

        $this->info("Kuzatilishi kerak bo'lgan raqobatchilar: {$competitors->count()}");

        if ($competitors->isEmpty()) {
            $this->info('Hozircha kuzatish kerak emas.');

            return Command::SUCCESS;
        }

        // Display table
        $this->table(
            ['ID', 'Nom', 'Biznes ID', 'So\'nggi tekshiruv', 'Frequency (soat)'],
            $competitors->map(fn ($c) => [
                $c->id,
                $c->name,
                $c->business_id,
                $c->last_checked_at?->format('Y-m-d H:i') ?? 'Hech qachon',
                $c->check_frequency_hours,
            ])
        );

        if ($this->confirm('Davom etsinmi?', true)) {
            ScrapeCompetitorData::dispatch();

            $this->info("Job ishga tushirildi. Queue worker'ni tekshiring.");
        }

        return Command::SUCCESS;
    }

    /**
     * Show available options
     */
    protected function showOptions(): void
    {
        $this->line('');
        $this->info('Foydalanish:');
        $this->line('');
        $this->line('  php artisan competitors:monitor --competitor=<ID>   Bitta raqobatchini kuzatish');
        $this->line('  php artisan competitors:monitor --business=<ID>     Biznesning barcha raqobatchilarini kuzatish');
        $this->line('  php artisan competitors:monitor --all               Barcha faol raqobatchilarni kuzatish');
        $this->line('  php artisan competitors:monitor --all --force       Vaqtdan qat\'iy nazar kuzatish');
        $this->line('');
    }
}
