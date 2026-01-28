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
            // 1. Core permissions and roles (KERAK)
            RolesAndPermissionsSeeder::class,

            // 2. Base configuration data (KERAK)
            PlanSeeder::class,
            StepDefinitionSeeder::class,
            IndustrySeeder::class,

            // 3. Admin user (KERAK)
            TestUserSeeder::class,

            // NOTE: Quyidagi seederlar test/development uchun:
            // BusinessSeeder, AlertRuleSeeder, DreamBuyerSeeder, OfferSeeder,
            // SalesFlowSeeder, IndustryBenchmarkSeeder, StrategyTemplateSeeder
        ]);
    }
}
