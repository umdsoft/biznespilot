<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\SalesScript;
use Illuminate\Database\Seeder;

/**
 * Har biznes uchun standart skript yaratish (agar hali yo'q bo'lsa).
 *
 * Run: php artisan db:seed --class=DefaultSalesScriptSeeder
 */
class DefaultSalesScriptSeeder extends Seeder
{
    public function run(): void
    {
        $businesses = Business::all();
        $created = 0;
        $skipped = 0;

        foreach ($businesses as $business) {
            $hasDefault = SalesScript::forBusiness($business->id)
                ->where('is_default', true)
                ->exists();

            if ($hasDefault) {
                $skipped++;
                continue;
            }

            SalesScript::create([
                'business_id' => $business->id,
                'name' => 'Standart sotuv skripti',
                'description' => 'Avtomatik yaratilgan standart skript. 7 bosqichli O\'zbek tilidagi sotuv suhbati.',
                'script_type' => 'general',
                'stages' => SalesScript::getDefaultTemplate(),
                'global_required_phrases' => ['assalomu alaykum', 'rahmat'],
                'global_forbidden_phrases' => ['bilmayman', 'yo\'q', 'ishonmayman'],
                'ideal_duration_min' => 120,
                'ideal_duration_max' => 600,
                'ideal_talk_ratio_min' => 30,
                'ideal_talk_ratio_max' => 60,
                'is_active' => true,
                'is_default' => true,
            ]);

            $created++;
        }

        $this->command->info("Standart skriptlar: {$created} yaratildi, {$skipped} allaqachon bor edi");
    }
}
