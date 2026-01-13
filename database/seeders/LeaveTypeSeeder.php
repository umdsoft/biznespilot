<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all businesses
        $businesses = DB::table('businesses')->pluck('id');

        foreach ($businesses as $businessId) {
            // Check if business already has leave types
            $exists = LeaveType::where('business_id', $businessId)->exists();

            if (!$exists) {
                // Create default leave types for this business
                LeaveType::createDefaultTypes($businessId);

                $this->command->info("Created default leave types for business: {$businessId}");
            } else {
                $this->command->info("Business {$businessId} already has leave types, skipping...");
            }
        }

        $this->command->info('Leave types seeded successfully!');
    }
}
