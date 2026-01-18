<?php

namespace Database\Seeders;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CustomerDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();

        if ($customers->isEmpty()) {
            $this->command->warn('No customers found.');

            return;
        }

        foreach ($customers as $customer) {
            $this->updateCustomerFinancialData($customer);
        }

        $this->command->info('Customer financial data updated successfully!');
    }

    /**
     * Update customer with realistic financial data
     */
    protected function updateCustomerFinancialData(Customer $customer): void
    {
        // Generate random but realistic data
        $totalOrders = rand(1, 20);

        // Different customer segments
        $segment = rand(1, 100);

        if ($segment <= 20) {
            // High value customers (20%)
            $avgOrderValue = rand(1000000, 5000000);
            $totalOrders = rand(10, 20);
        } elseif ($segment <= 60) {
            // Medium value customers (40%)
            $avgOrderValue = rand(300000, 1000000);
            $totalOrders = rand(5, 10);
        } else {
            // Low value customers (40%)
            $avgOrderValue = rand(100000, 300000);
            $totalOrders = rand(1, 5);
        }

        $totalSpent = $avgOrderValue * $totalOrders;

        // LTV is typically 1.2-2x of total spent (based on retention and future purchases)
        // Cap at 99,999,999 to fit decimal(10,2)
        $ltv = min($totalSpent * (rand(12, 20) / 10), 99999999);

        // Last purchase date - some recent, some old (for churn analysis)
        $daysAgo = $this->getLastPurchaseDaysAgo($totalOrders);
        $lastPurchaseAt = Carbon::now()->subDays($daysAgo);

        // Acquisition date - some time before first purchase
        $acquisitionDaysAgo = $daysAgo + rand(5, 30);
        $acquisitionDate = Carbon::now()->subDays($acquisitionDaysAgo);

        // Update customer data
        $customer->update([
            'ltv' => $ltv,
            'total_spent' => $totalSpent,
            'total_orders' => $totalOrders,
            'last_purchase_at' => $lastPurchaseAt,
            'acquisition_date' => $acquisitionDate,
            'city' => $this->getRandomCity(),
            'country' => 'Uzbekistan',
        ]);

        // Update some customers to different statuses
        if ($daysAgo > 90) {
            // Customers with no purchase in 90+ days might be at risk or churned
            if (rand(1, 100) > 70) {
                $customer->update(['status' => 'inactive']);
            }
            if (rand(1, 100) > 90) {
                $customer->update(['status' => 'churned']);
            }
        }
    }

    /**
     * Get last purchase days ago based on total orders
     */
    protected function getLastPurchaseDaysAgo(int $totalOrders): int
    {
        // More orders = more likely to be recent customer
        if ($totalOrders >= 10) {
            return rand(1, 30); // Active customers
        } elseif ($totalOrders >= 5) {
            return rand(15, 60); // Regular customers
        } else {
            return rand(30, 120); // Occasional or at-risk customers
        }
    }

    /**
     * Get random city in Uzbekistan
     */
    protected function getRandomCity(): string
    {
        $cities = [
            'Toshkent',
            'Samarqand',
            'Buxoro',
            'Andijon',
            'Namangan',
            'Farg\'ona',
            'Qo\'qon',
            'Nukus',
            'Urganch',
            'Qarshi',
            'Guliston',
            'Jizzax',
            'Navoiy',
            'Termiz',
        ];

        return $cities[array_rand($cities)];
    }
}
