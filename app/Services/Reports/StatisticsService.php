<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Business;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * StatisticsService - Visual Analytics for Telegram Bot
 *
 * "The Analyst" - Provides visual reports with:
 * - Progress bars
 * - Sales comparison (current vs last month)
 * - Marketing ROI by source
 * - Employee leaderboards
 */
class StatisticsService
{
    /**
     * Generate full sales statistics report.
     */
    public function generateSalesReport(Business $business): string
    {
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        // Current month stats
        $currentRevenue = $this->getMonthRevenue($business, $currentMonth, now());
        $currentCount = $this->getMonthOrderCount($business, $currentMonth, now());

        // Last month stats
        $lastRevenue = $this->getMonthRevenue($business, $lastMonth, $lastMonthEnd);
        $lastCount = $this->getMonthOrderCount($business, $lastMonth, $lastMonthEnd);

        // Growth calculation
        $revenueGrowth = $lastRevenue > 0
            ? round((($currentRevenue - $lastRevenue) / $lastRevenue) * 100, 1)
            : ($currentRevenue > 0 ? 100 : 0);

        $growthEmoji = $revenueGrowth > 0 ? '📈' : ($revenueGrowth < 0 ? '📉' : '➡️');
        $growthSign = $revenueGrowth > 0 ? '+' : '';

        // Average check
        $avgCheck = $currentCount > 0 ? $currentRevenue / $currentCount : 0;

        // Today's stats
        $todayRevenue = $this->getTodayRevenue($business);
        $todayCount = $this->getTodayOrderCount($business);

        // Progress bar for month target (assume target is last month * 1.1)
        $monthTarget = $lastRevenue * 1.1;
        $progressBar = $this->drawProgressBar($currentRevenue, max($monthTarget, 1));

        $report = "💰 <b>SAVDO STATISTIKASI</b>\n";
        $report .= "🏢 {$business->name}\n";
        $report .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        // Today
        $report .= "📅 <b>BUGUN</b>\n";
        $report .= "💵 Daromad: <b>" . $this->formatMoney($todayRevenue) . "</b>\n";
        $report .= "🛒 Buyurtmalar: <b>{$todayCount} ta</b>\n\n";

        // Current month
        $report .= "📆 <b>BU OY</b> (" . now()->format('F') . ")\n";
        $report .= "💵 Daromad: <b>" . $this->formatMoney($currentRevenue) . "</b>\n";
        $report .= "🛒 Buyurtmalar: <b>{$currentCount} ta</b>\n";
        $report .= "💳 O'rtacha chek: <b>" . $this->formatMoney($avgCheck) . "</b>\n";
        $report .= "{$progressBar}\n\n";

        // Comparison
        $report .= "{$growthEmoji} <b>O'SISH</b>\n";
        $report .= "O'tgan oyga nisbatan: <b>{$growthSign}{$revenueGrowth}%</b>\n";
        $report .= "O'tgan oy: " . $this->formatMoney($lastRevenue) . " ({$lastCount} ta)\n\n";

        // Top products/services (if available)
        $topProducts = $this->getTopProducts($business, $currentMonth);
        if ($topProducts->isNotEmpty()) {
            $report .= "🏆 <b>TOP MAHSULOTLAR</b>\n";
            foreach ($topProducts->take(3) as $index => $product) {
                $medal = $this->getMedal($index);
                $report .= "{$medal} {$product['name']}: " . $this->formatMoney($product['revenue']) . "\n";
            }
        }

        return $report;
    }

    /**
     * Generate marketing ROI report.
     */
    public function generateMarketingReport(Business $business): string
    {
        $startDate = now()->subDays(30);

        // Get orders with attribution
        $orders = Order::where('business_id', $business->id)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('utm_source')
            ->get();

        $report = "📢 <b>MARKETING ROI</b>\n";
        $report .= "🏢 {$business->name}\n";
        $report .= "📅 Oxirgi 30 kun\n";
        $report .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        if ($orders->isEmpty()) {
            $report .= "📭 UTM belgilangan buyurtmalar topilmadi.\n\n";
            $report .= "💡 <i>Maslahat: Reklama havolalariga UTM parametrlarini qo'shing.</i>";
            return $report;
        }

        // Group by source
        $bySource = $orders->groupBy('utm_source');

        $totalRevenue = $orders->sum('total');
        $report .= "💰 Jami daromad: <b>" . $this->formatMoney($totalRevenue) . "</b>\n\n";

        $report .= "📊 <b>KANALLAR BO'YICHA:</b>\n\n";

        $sourceStats = $bySource->map(function ($group, $source) use ($totalRevenue) {
            $revenue = $group->sum('total');
            $count = $group->count();
            $share = $totalRevenue > 0 ? round(($revenue / $totalRevenue) * 100, 1) : 0;

            return [
                'source' => $source,
                'revenue' => $revenue,
                'count' => $count,
                'share' => $share,
            ];
        })->sortByDesc('revenue');

        foreach ($sourceStats as $stat) {
            $shareBar = $this->drawMiniBar($stat['share']);

            // Determine status emoji
            $statusEmoji = $stat['share'] >= 20 ? '✅' : ($stat['share'] >= 10 ? '🟡' : '⚠️');

            $report .= "{$statusEmoji} <b>{$stat['source']}</b>\n";
            $report .= "   💵 " . $this->formatMoney($stat['revenue']) . " ({$stat['count']} ta)\n";
            $report .= "   {$shareBar} {$stat['share']}%\n\n";
        }

        // Orders without attribution warning
        $totalOrders = Order::where('business_id', $business->id)
            ->where('created_at', '>=', $startDate)
            ->count();

        $withAttribution = $orders->count();
        $withoutAttribution = $totalOrders - $withAttribution;

        if ($withoutAttribution > 0) {
            $percentage = round(($withoutAttribution / $totalOrders) * 100);
            $report .= "⚠️ <b>Ogohlantirish:</b>\n";
            $report .= "{$withoutAttribution} ta buyurtma ({$percentage}%) UTM siz!\n";
        }

        return $report;
    }

    /**
     * Generate employee leaderboard.
     */
    public function generateEmployeeLeaderboard(Business $business): string
    {
        $startDate = now()->startOfMonth();

        $report = "🏆 <b>XODIMLAR REYTINGI</b>\n";
        $report .= "🏢 {$business->name}\n";
        $report .= "📅 " . now()->format('F Y') . "\n";
        $report .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        // Get users with their sales/orders
        $employees = $business->users()
            ->where('is_active', true)
            ->get();

        if ($employees->isEmpty()) {
            $report .= "👥 Xodimlar topilmadi.";
            return $report;
        }

        $leaderboard = $employees->map(function ($user) use ($business, $startDate) {
            // Count completed tasks
            $tasksCompleted = Task::where('business_id', $business->id)
                ->where('assigned_to', $user->id)
                ->where('status', 'completed')
                ->where('completed_at', '>=', $startDate)
                ->count();

            // Count won leads (if sales person)
            $leadsWon = Lead::where('business_id', $business->id)
                ->where('assigned_to', $user->id)
                ->where('status', 'won')
                ->where('converted_at', '>=', $startDate)
                ->count();

            // Calculate revenue from won leads
            $revenue = Lead::where('business_id', $business->id)
                ->where('assigned_to', $user->id)
                ->where('status', 'won')
                ->where('converted_at', '>=', $startDate)
                ->sum('estimated_value');

            return [
                'id' => $user->id,
                'name' => $user->name,
                'revenue' => $revenue,
                'leads_won' => $leadsWon,
                'tasks_completed' => $tasksCompleted,
                'score' => $revenue + ($leadsWon * 100000) + ($tasksCompleted * 50000),
            ];
        })->sortByDesc('score')->values();

        if ($leaderboard->sum('score') == 0) {
            $report .= "📊 Bu oyda hali natijalar yo'q.\n";
            return $report;
        }

        // Display leaderboard
        $report .= "💰 <b>DAROMAD BO'YICHA:</b>\n\n";

        foreach ($leaderboard->take(10) as $index => $employee) {
            $medal = $this->getMedal($index);
            $position = $index + 1;

            $report .= "{$medal} <b>{$employee['name']}</b>\n";
            $report .= "   💵 " . $this->formatMoney($employee['revenue']);

            if ($employee['leads_won'] > 0) {
                $report .= " ({$employee['leads_won']} lid)";
            }

            if ($employee['tasks_completed'] > 0) {
                $report .= "\n   ✅ {$employee['tasks_completed']} vazifa";
            }

            $report .= "\n\n";
        }

        // Summary stats
        $totalRevenue = $leaderboard->sum('revenue');
        $totalLeads = $leaderboard->sum('leads_won');
        $totalTasks = $leaderboard->sum('tasks_completed');

        $report .= "━━━━━━━━━━━━━━━━━━━━\n";
        $report .= "📊 <b>JAMOA JAMI:</b>\n";
        $report .= "💰 " . $this->formatMoney($totalRevenue) . "\n";
        $report .= "🎯 {$totalLeads} lid yutildi\n";
        $report .= "✅ {$totalTasks} vazifa bajarildi";

        return $report;
    }

    /**
     * Draw ASCII progress bar.
     */
    public function drawProgressBar(float $current, float $max, int $length = 10): string
    {
        if ($max <= 0) {
            return str_repeat('░', $length) . ' 0%';
        }

        $percentage = min(100, ($current / $max) * 100);
        $filled = (int) round(($percentage / 100) * $length);
        $empty = $length - $filled;

        $bar = str_repeat('▓', $filled) . str_repeat('░', $empty);

        return $bar . ' ' . round($percentage) . '%';
    }

    /**
     * Draw mini progress bar for shares.
     */
    public function drawMiniBar(float $percentage, int $length = 6): string
    {
        $filled = (int) round(($percentage / 100) * $length);
        $empty = $length - $filled;

        return str_repeat('▓', $filled) . str_repeat('░', $empty);
    }

    /**
     * Get medal emoji by position.
     */
    protected function getMedal(int $index): string
    {
        return match ($index) {
            0 => '🥇',
            1 => '🥈',
            2 => '🥉',
            default => (string) ($index + 1) . '.',
        };
    }

    /**
     * Format money for display.
     */
    protected function formatMoney(float $amount): string
    {
        if ($amount >= 1000000000) {
            return number_format($amount / 1000000000, 1, '.', ' ') . ' mlrd';
        } elseif ($amount >= 1000000) {
            return number_format($amount / 1000000, 1, '.', ' ') . ' mln';
        }
        return number_format($amount, 0, '.', ' ') . " so'm";
    }

    /**
     * Get month revenue.
     */
    protected function getMonthRevenue(Business $business, Carbon $start, Carbon $end): float
    {
        return Order::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid')
            ->sum('total');
    }

    /**
     * Get month order count.
     */
    protected function getMonthOrderCount(Business $business, Carbon $start, Carbon $end): int
    {
        return Order::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid')
            ->count();
    }

    /**
     * Get today's revenue.
     */
    protected function getTodayRevenue(Business $business): float
    {
        return Order::where('business_id', $business->id)
            ->whereDate('created_at', now())
            ->where('payment_status', 'paid')
            ->sum('total');
    }

    /**
     * Get today's order count.
     */
    protected function getTodayOrderCount(Business $business): int
    {
        return Order::where('business_id', $business->id)
            ->whereDate('created_at', now())
            ->where('payment_status', 'paid')
            ->count();
    }

    /**
     * Get top products by revenue.
     */
    protected function getTopProducts(Business $business, Carbon $since): Collection
    {
        // This would need a products/services table
        // For now, return empty collection
        return collect();
    }
}
