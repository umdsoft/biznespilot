<?php

namespace App\Console\Commands;

use App\Services\Agent\Memory\BusinessContextMemory;
use Illuminate\Console\Command;

/**
 * Muddati o'tgan kontekstlarni tozalash — har kuni ishga tushadi.
 * php artisan agent:cleanup-expired-context
 */
class CleanExpiredContext extends Command
{
    protected $signature = 'agent:cleanup-expired-context';
    protected $description = 'Muddati o\'tgan agent kontekstlarni tozalash';

    public function handle(BusinessContextMemory $memory): int
    {
        $deleted = $memory->cleanExpired();
        $this->info("Tozalandi: {$deleted} ta muddati o'tgan kontekst.");
        return self::SUCCESS;
    }
}
