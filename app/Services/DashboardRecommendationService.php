<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ContentPost;
use App\Models\Lead;
use App\Models\Store\StoreCustomer;
use App\Models\Store\StoreOrder;
use App\Models\Store\TelegramStore;
use App\Models\Task;
use Illuminate\Support\Collection;

class DashboardRecommendationService
{
    /**
     * Biznes egasi uchun amaliy tavfsiyalar generatsiya qilish (rule-based)
     *
     * @return array Max 5 ta tavfsiya
     */
    public function getRecommendations(Business $business, array $context): array
    {
        $recommendations = [];

        $storeIds = $context['store_ids'] ?? collect();

        // 1. Kutilayotgan buyurtmalar
        $pendingOrders = $context['pending_orders'] ?? 0;
        if ($pendingOrders > 0) {
            $recommendations[] = [
                'type' => 'warning',
                'icon' => 'clock',
                'message' => "Sizda {$pendingOrders} ta buyurtma kutilmoqda. Tezroq tasdiqlang!",
                'action_url' => '/business/store/orders',
                'action_text' => "Buyurtmalarga o'tish",
                'priority' => 1,
            ];
        }

        // 2. Javob berilmagan lidlar
        $unansweredLeads = $context['unanswered_leads'] ?? 0;
        if ($unansweredLeads > 0) {
            $recommendations[] = [
                'type' => 'warning',
                'icon' => 'users',
                'message' => "{$unansweredLeads} ta yangi lidga hali javob berilmagan.",
                'action_url' => '/business/sales',
                'action_text' => 'Lidlarni ko\'rish',
                'priority' => 2,
            ];
        }

        // 3. Bugun buyurtma yo'q (lekin kecha bor edi)
        $todayOrders = $context['today_orders'] ?? 0;
        $yesterdayOrders = $context['yesterday_orders'] ?? 0;
        if ($todayOrders === 0 && $yesterdayOrders > 0) {
            $recommendations[] = [
                'type' => 'info',
                'icon' => 'megaphone',
                'message' => "Bugun hali buyurtma yo'q. Kontent yoki reklama e'lon qiling.",
                'action_url' => '/business/marketing/content',
                'action_text' => 'Kontent yaratish',
                'priority' => 3,
            ];
        }

        // 4. Daromad o'sishi
        $todayRevenue = $context['today_revenue'] ?? 0;
        $yesterdayRevenue = $context['yesterday_revenue'] ?? 0;
        if ($yesterdayRevenue > 0 && $todayRevenue > 0) {
            $change = round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1);
            if ($change >= 20) {
                $recommendations[] = [
                    'type' => 'success',
                    'icon' => 'trending-up',
                    'message' => "Ajoyib! Daromad kechagiga nisbatan {$change}% oshdi!",
                    'action_url' => null,
                    'action_text' => null,
                    'priority' => 8,
                ];
            } elseif ($change <= -20) {
                $recommendations[] = [
                    'type' => 'warning',
                    'icon' => 'trending-down',
                    'message' => "Daromad kechagiga nisbatan " . abs($change) . "% tushdi. Sabab tahlil qiling.",
                    'action_url' => '/business/analytics',
                    'action_text' => 'Analitikani ko\'rish',
                    'priority' => 2,
                ];
            }
        }

        // 5. Kontent faollik (oxirgi post qachon joylashtirilgan)
        $lastPost = ContentPost::where('business_id', $business->id)
            ->where('status', 'published')
            ->latest('published_at')
            ->first();

        if ($lastPost) {
            $daysSincePost = (int) now()->diffInDays($lastPost->published_at);
            if ($daysSincePost > 3) {
                $recommendations[] = [
                    'type' => 'info',
                    'icon' => 'calendar',
                    'message' => "{$daysSincePost} kundan beri kontent joylashtirilmagan. Auditoriyangizni faol tuting!",
                    'action_url' => '/business/marketing/content-ai',
                    'action_text' => 'AI bilan kontent yaratish',
                    'priority' => 5,
                ];
            }
        }

        // 6. Yangi mijozlar (ijobiy)
        $newCustomersToday = $context['new_customers_today'] ?? 0;
        if ($newCustomersToday > 0) {
            $recommendations[] = [
                'type' => 'success',
                'icon' => 'user-plus',
                'message' => "Bugun {$newCustomersToday} ta yangi mijoz keldi!",
                'action_url' => null,
                'action_text' => null,
                'priority' => 9,
            ];
        }

        // 7. Bajarilmagan vazifalar
        $overdueTasks = $context['overdue_tasks'] ?? 0;
        if ($overdueTasks > 0) {
            $recommendations[] = [
                'type' => 'warning',
                'icon' => 'exclamation',
                'message' => "{$overdueTasks} ta vazifaning muddati o'tgan!",
                'action_url' => '/business/tasks',
                'action_text' => 'Vazifalarni ko\'rish',
                'priority' => 1,
            ];
        }

        // 8. Mijoz qaytishi (returning rate)
        if ($storeIds->isNotEmpty()) {
            $totalCustomers = StoreCustomer::whereIn('store_id', $storeIds)->count();
            $returningCustomers = StoreCustomer::whereIn('store_id', $storeIds)
                ->where('orders_count', '>=', 2)
                ->count();

            if ($totalCustomers >= 10) {
                $returningRate = round(($returningCustomers / $totalCustomers) * 100, 1);
                if ($returningRate < 20) {
                    $recommendations[] = [
                        'type' => 'info',
                        'icon' => 'refresh',
                        'message' => "Mijozlarning faqat {$returningRate}% qaytib kelmoqda. Chegirma yoki aksiya taklif qiling.",
                        'action_url' => '/business/store/settings',
                        'action_text' => "Do'kon sozlamalari",
                        'priority' => 6,
                    ];
                }
            }
        }

        // Priority bo'yicha tartiblash va max 5 ta olish
        usort($recommendations, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return array_slice(
            array_map(function ($r) {
                unset($r['priority']);

                return $r;
            }, $recommendations),
            0,
            5
        );
    }
}
