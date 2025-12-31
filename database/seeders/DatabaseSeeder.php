<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
                // 1. Core permissions and roles
            RolesAndPermissionsSeeder::class,

                // 2. Base configuration data
            PlanSeeder::class,
            StepDefinitionSeeder::class,
            IndustrySeeder::class,

                // 3. Industry-related reference data
            IndustryBenchmarkSeeder::class,
            StrategyTemplateSeeder::class,

                // 4. User and Business setup
            TestUserSeeder::class,
            BusinessSeeder::class,

                // 5. Business-dependent seeders
            AlertRuleSeeder::class,
            DreamBuyerSeeder::class,
            OfferSeeder::class,

            // NOTE: The following seeders need schema updates:
            // CustomerDataSeeder, LeadSeeder, CampaignSeeder, ConversationSeeder
        ]);
    }
}
