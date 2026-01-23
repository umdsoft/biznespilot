<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Lead;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('business.business.create');
        }

        // Cache key for this business
        $cacheKey = "finance_dashboard_{$business->id}";

        // Get stats with caching (5 minutes)
        $stats = Cache::remember($cacheKey, 300, function () use ($business) {
            return $this->calculateStats($business);
        });

        // Recent transactions (not cached for freshness)
        $recentTransactions = $this->getRecentTransactions($business);

        // Pending invoices (payment transactions)
        $pendingInvoices = $this->getPendingInvoices($business);

        return Inertia::render('Finance/Dashboard', [
            'stats' => $stats,
            'recentTransactions' => $recentTransactions,
            'pendingInvoices' => $pendingInvoices,
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }

    /**
     * Calculate financial stats from real data
     */
    private function calculateStats($business): array
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Revenue from won leads
        $totalRevenue = Lead::where('business_id', $business->id)
            ->where('status', 'won')
            ->sum('estimated_value');

        $thisMonthRevenue = Lead::where('business_id', $business->id)
            ->where('status', 'won')
            ->where('converted_at', '>=', $startOfMonth)
            ->sum('estimated_value');

        $lastMonthRevenue = Lead::where('business_id', $business->id)
            ->where('status', 'won')
            ->whereBetween('converted_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('estimated_value');

        $revenueGrowth = $lastMonthRevenue > 0
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        // Payment transactions
        $completedPayments = PaymentTransaction::where('business_id', $business->id)
            ->where('status', PaymentTransaction::STATUS_COMPLETED)
            ->sum('amount');

        $thisMonthPayments = PaymentTransaction::where('business_id', $business->id)
            ->where('status', PaymentTransaction::STATUS_COMPLETED)
            ->where('paid_at', '>=', $startOfMonth)
            ->sum('amount');

        // Pending payments
        $pendingPaymentsCount = PaymentTransaction::where('business_id', $business->id)
            ->where('status', PaymentTransaction::STATUS_PENDING)
            ->count();

        $pendingPaymentsAmount = PaymentTransaction::where('business_id', $business->id)
            ->where('status', PaymentTransaction::STATUS_PENDING)
            ->sum('amount');

        // Overdue payments (expired and still pending)
        $overduePayments = PaymentTransaction::where('business_id', $business->id)
            ->whereIn('status', [PaymentTransaction::STATUS_PENDING, PaymentTransaction::STATUS_PROCESSING])
            ->where('expires_at', '<', $now)
            ->count();

        // Total payment transactions
        $totalTransactions = PaymentTransaction::where('business_id', $business->id)->count();

        // Profit margin estimate (using default 30% if no specific data)
        $profitMargin = 30.0;
        $estimatedProfit = $totalRevenue * ($profitMargin / 100);
        $thisMonthProfit = $thisMonthRevenue * ($profitMargin / 100);

        // Cash flow (based on completed payments)
        $lastWeekInflow = PaymentTransaction::where('business_id', $business->id)
            ->where('status', PaymentTransaction::STATUS_COMPLETED)
            ->where('paid_at', '>=', $now->copy()->subWeek())
            ->sum('amount');

        return [
            'revenue' => [
                'total' => (float) $totalRevenue,
                'this_month' => (float) $thisMonthRevenue,
                'growth' => $revenueGrowth,
            ],
            'expenses' => [
                'total' => 0, // No expense model yet
                'this_month' => 0,
                'growth' => 0,
            ],
            'profit' => [
                'total' => (float) $estimatedProfit,
                'this_month' => (float) $thisMonthProfit,
                'margin' => $profitMargin,
            ],
            'invoices' => [
                'total' => $totalTransactions,
                'pending' => $pendingPaymentsCount,
                'overdue' => $overduePayments,
                'amount_pending' => (float) $pendingPaymentsAmount,
            ],
            'cashflow' => [
                'balance' => (float) $completedPayments,
                'inflow' => (float) $lastWeekInflow,
                'outflow' => 0, // No expense tracking yet
            ],
        ];
    }

    /**
     * Get recent transactions
     */
    private function getRecentTransactions($business): array
    {
        return PaymentTransaction::where('business_id', $business->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($tx) => [
                'id' => $tx->id,
                'type' => $tx->status === PaymentTransaction::STATUS_COMPLETED ? 'income' : 'pending',
                'description' => $tx->description ?? "To'lov #{$tx->order_id}",
                'amount' => (float) $tx->amount,
                'date' => $tx->created_at->format('Y-m-d'),
                'status' => $tx->status,
                'status_label' => $tx->status_label,
            ])
            ->toArray();
    }

    /**
     * Get pending invoices (payment transactions)
     */
    private function getPendingInvoices($business): array
    {
        return PaymentTransaction::where('business_id', $business->id)
            ->whereIn('status', [PaymentTransaction::STATUS_PENDING, PaymentTransaction::STATUS_PROCESSING])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($tx) => [
                'id' => $tx->id,
                'number' => $tx->order_id,
                'client' => $tx->lead?->name ?? $tx->lead?->phone ?? 'Noma\'lum',
                'amount' => (float) $tx->amount,
                'due_date' => $tx->expires_at?->format('Y-m-d') ?? '-',
                'status' => $tx->is_expired ? 'overdue' : 'pending',
            ])
            ->toArray();
    }

    public function apiStats()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json([
                'tasks_count' => 0,
                'unread_count' => 0,
            ]);
        }

        // Get actual counts
        $tasksCount = \App\Models\Task::where('business_id', $business->id)
            ->where('status', '!=', 'completed')
            ->count();

        $unreadCount = \App\Models\InboxMessage::where('business_id', $business->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'tasks_count' => $tasksCount,
            'unread_count' => $unreadCount,
        ]);
    }
}
