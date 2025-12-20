<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    public function run(): void
    {
        $businesses = \App\Models\Business::all();

        if ($businesses->isEmpty()) {
            $this->command->warn('No businesses found. Please run BusinessSeeder first.');
            return;
        }

        // Create demo customers for each business
        foreach ($businesses as $business) {
            $customerNames = [
                ['name' => 'Alisher Karimov', 'phone' => '+998901234567'],
                ['name' => 'Madina Rahimova', 'phone' => '+998902345678'],
                ['name' => 'Jasur Tursunov', 'phone' => '+998903456789'],
                ['name' => 'Dilnoza Abdullayeva', 'phone' => '+998904567890'],
                ['name' => 'Bekzod Narzullayev', 'phone' => '+998905678901'],
            ];

            foreach ($customerNames as $customerData) {
                \App\Models\Customer::firstOrCreate(
                    ['phone' => $customerData['phone'], 'business_id' => $business->id],
                    [
                        'uuid' => (string) \Illuminate\Support\Str::uuid(),
                        'name' => $customerData['name'],
                        'acquisition_source' => 'whatsapp',
                        'data' => json_encode(['tags' => ['demo', 'seed']]),
                    ]
                );
            }

            $this->command->info("Created 5 demo customers for business: {$business->name}");
        }
    }
}
