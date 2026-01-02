<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'code' => 'cash',
                'name' => 'Naqd pul',
                'icon' => 'banknote',
                'sort_order' => 1,
            ],
            [
                'code' => 'card',
                'name' => 'Plastik karta',
                'icon' => 'credit-card',
                'sort_order' => 2,
            ],
            [
                'code' => 'transfer',
                'name' => "Pul o'tkazma",
                'icon' => 'arrow-right-left',
                'sort_order' => 3,
            ],
            [
                'code' => 'click',
                'name' => 'Click',
                'icon' => 'smartphone',
                'sort_order' => 4,
            ],
            [
                'code' => 'payme',
                'name' => 'Payme',
                'icon' => 'smartphone',
                'sort_order' => 5,
            ],
            [
                'code' => 'installment',
                'name' => "Bo'lib to'lash",
                'icon' => 'calendar',
                'sort_order' => 6,
            ],
            [
                'code' => 'credit',
                'name' => 'Nasiya',
                'icon' => 'clock',
                'sort_order' => 7,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                array_merge($method, ['is_active' => true])
            );
        }
    }
}
