<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * BARCHA bo'limlar uchun 1 yillik test ma'lumotlar.
 * IT kompaniya: web/mobil dasturlash xizmatlari
 */
class TestFullBusinessSeeder extends Seeder
{
    private string $bid = 'aa9b1e35-020b-4ec1-b1ad-ef9f931cfac8';
    private string $uid = 'b090f3d5-173d-480b-8ea5-7140004d64f2';

    public function run(): void
    {
        $this->command->info('Barcha bo\'limlar uchun 1 yillik ma\'lumot yaratilmoqda...');

        $this->seedLeadSources();
        $this->seedOffers();
        $this->seedMarketingChannels();
        $this->seedKpiPlans();
        $this->seedKpiDailyEntries();
        $this->seedContentGenerations();
        $this->seedTodos();
        $this->seedTasks();
        $this->seedCallLogs();
        $this->seedJobPostings();

        $this->command->info('✅ Barcha ma\'lumotlar yaratildi!');
    }

    private function seedLeadSources(): void
    {
        $sources = ['Instagram', 'Telegram', 'Veb-sayt', 'Tavsiya', 'Google Ads', 'Facebook'];
        foreach ($sources as $name) {
            $this->safeInsert('lead_sources', ['business_id' => $this->bid, 'name' => $name], [
                'id' => Str::uuid(), 'business_id' => $this->bid, 'name' => $name, 'is_active' => true,
            ]);
        }
        $this->command->info('  ✓ Lead manbalari');
    }

    private function seedOffers(): void
    {
        $offers = [
            ['title' => 'Web-sayt yaratish', 'description' => 'Professional veb-sayt 15 kun ichida. Responsive dizayn, SEO optimizatsiya, admin panel.', 'price' => 5000000],
            ['title' => 'Mobil ilova ishlab chiqish', 'description' => 'iOS va Android uchun native yoki cross-platform ilova.', 'price' => 15000000],
            ['title' => 'Biznes avtomatlashtirish', 'description' => 'CRM, hisobotlar, jarayonlarni avtomatlashtirish.', 'price' => 8000000],
            ['title' => 'Telegram bot yaratish', 'description' => 'Maxsus funksiyali Telegram bot. To\'lov integratsiyasi bilan.', 'price' => 3000000],
            ['title' => 'Texnik qo\'llab-quvvatlash', 'description' => 'Oylik texnik xizmat va yangilanishlar.', 'price' => 1500000],
        ];
        foreach ($offers as $o) {
            $this->safeInsert('offers', ['business_id' => $this->bid, 'title' => $o['title']], array_merge($o, [
                'id' => Str::uuid(), 'business_id' => $this->bid, 'status' => 'active',
            ]));
        }
        $this->command->info('  ✓ 5 ta taklif');
    }

    private function seedMarketingChannels(): void
    {
        $channels = [
            ['type' => 'instagram', 'name' => 'Instagram @testbiznes', 'is_active' => true],
            ['type' => 'telegram', 'name' => 'Telegram @testbiznes_bot', 'is_active' => true],
            ['type' => 'facebook', 'name' => 'Facebook Page', 'is_active' => false],
        ];
        foreach ($channels as $ch) {
            $this->safeInsert('marketing_channels', ['business_id' => $this->bid, 'type' => $ch['type']], array_merge($ch, [
                'id' => Str::uuid(), 'business_id' => $this->bid,
            ]));
        }
        $this->command->info('  ✓ 3 ta marketing kanal');
    }

    private function seedKpiPlans(): void
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = [
                'id' => Str::uuid(),
                'business_id' => $this->bid,
                'month' => $date->month,
                'year' => $date->year,
                'revenue_target' => rand(30, 80) * 1000000,
                'leads_target' => rand(20, 50),
                'conversion_target' => rand(15, 35),
                'created_at' => $date->startOfMonth(),
                'updated_at' => $date->startOfMonth(),
            ];
        }
        foreach ($months as $m) {
            $this->safeInsert('kpi_plans', ['business_id' => $this->bid, 'month' => $m['month'], 'year' => $m['year']], $m);
        }
        $this->command->info('  ✓ 12 oylik KPI rejalar');
    }

    private function seedKpiDailyEntries(): void
    {
        $count = 0;
        for ($day = 365; $day >= 0; $day--) {
            $date = now()->subDays($day);
            if ($date->isWeekend() && rand(0, 3) > 0) continue; // dam olish kunlari kamroq

            $revenue = rand(0, 5) * 1000000 + rand(0, 999999);
            $leads = rand(0, 4);
            $deals = rand(0, 2);

            $this->safeInsert('kpi_daily_entries', ['business_id' => $this->bid, 'date' => $date->format('Y-m-d')], [
                'id' => Str::uuid(),
                'business_id' => $this->bid,
                'date' => $date->format('Y-m-d'),
                'revenue' => $revenue,
                'new_leads' => $leads,
                'new_deals' => $deals,
                'calls_made' => rand(2, 15),
                'meetings_held' => rand(0, 3),
                'proposals_sent' => rand(0, 2),
                'created_at' => $date,
                'updated_at' => $date,
            ]);
            $count++;
        }
        $this->command->info("  ✓ {$count} kunlik KPI yozuvlari");
    }

    private function seedContentGenerations(): void
    {
        $topics = [
            'Web-sayt yaratish afzalliklari', 'Telegram bot nima uchun kerak?', 'Mobil ilova vs veb-sayt',
            'IT outsourcing trendi', 'UX dizayn asoslari', 'SEO nima va nima uchun kerak?',
            'E-commerce platformalar', 'Startup uchun MVP', 'Kiberbezopaslik asoslari',
            'Cloud xizmatlar', 'AI biznesda', 'Avtomatlashtirish afzalliklari',
        ];
        $types = ['instagram_post', 'telegram_post', 'blog_article', 'carousel'];
        $count = 0;

        for ($day = 180; $day >= 0; $day -= rand(1, 4)) {
            $date = now()->subDays($day);
            $this->safeInsert('content_generations', null, [
                'id' => Str::uuid(),
                'business_id' => $this->bid,
                'user_id' => $this->uid,
                'type' => $types[array_rand($types)],
                'topic' => $topics[array_rand($topics)],
                'content' => 'Test kontent — ' . $topics[array_rand($topics)],
                'status' => rand(0, 10) > 2 ? 'completed' : 'draft',
                'model_used' => 'haiku',
                'tokens_used' => rand(200, 800),
                'created_at' => $date,
                'updated_at' => $date,
            ]);
            $count++;
        }
        $this->command->info("  ✓ {$count} ta kontent");
    }

    private function seedTodos(): void
    {
        $todos = [
            'Instagram kontentni rejalashtirish', 'Yangi mijozga taklifnoma yuborish',
            'Telegram bot yangilash', 'Haftalik hisobot tayyorlash',
            'Raqobatchilarni tekshirish', 'SEO audit o\'tkazish',
            'Mijoz bilan uchrashuv', 'Yangi xodim suhbati',
            'Server yangilash', 'Marketing byudjet rejasi',
            'Oylik KPI tahlili', 'Jamoaviy yig\'ilish o\'tkazish',
        ];

        foreach ($todos as $i => $title) {
            $daysAgo = rand(0, 30);
            $this->safeInsert('todos', null, [
                'id' => Str::uuid(),
                'business_id' => $this->bid,
                'user_id' => $this->uid,
                'title' => $title,
                'status' => $i < 6 ? 'completed' : ($i < 9 ? 'in_progress' : 'pending'),
                'priority' => ['high', 'medium', 'low'][rand(0, 2)],
                'due_date' => now()->addDays(rand(-5, 14)),
                'created_at' => now()->subDays($daysAgo),
                'updated_at' => now()->subDays(rand(0, $daysAgo)),
            ]);
        }
        $this->command->info('  ✓ 12 ta vazifa');
    }

    private function seedTasks(): void
    {
        $tasks = [
            'Landing page dizayni', 'API integratsiya', 'Database optimizatsiya',
            'Mobil ilova testing', 'Xavfsizlik auditi', 'Yangi modul ishlab chiqish',
        ];
        foreach ($tasks as $i => $title) {
            $this->safeInsert('tasks', null, [
                'id' => Str::uuid(),
                'business_id' => $this->bid,
                'user_id' => $this->uid,
                'title' => $title,
                'description' => "Test vazifa: {$title}",
                'status' => $i < 3 ? 'done' : ($i < 5 ? 'in_progress' : 'todo'),
                'priority' => ['high', 'medium', 'low'][rand(0, 2)],
                'due_date' => now()->addDays(rand(-10, 20)),
                'created_at' => now()->subDays(rand(5, 60)),
                'updated_at' => now(),
            ]);
        }
        $this->command->info('  ✓ 6 ta task');
    }

    private function seedCallLogs(): void
    {
        $count = 0;
        for ($day = 180; $day >= 0; $day -= rand(1, 3)) {
            $date = now()->subDays($day)->addHours(rand(9, 18));
            $duration = rand(30, 600);
            $direction = rand(0, 1) ? 'inbound' : 'outbound';
            $status = rand(0, 10) > 2 ? 'completed' : (rand(0, 1) ? 'missed' : 'failed');

            $this->safeInsert('call_logs', null, [
                'id' => Str::uuid(),
                'business_id' => $this->bid,
                'user_id' => $this->uid,
                'provider' => 'sipuni',
                'direction' => $direction,
                'from_number' => '+998' . rand(90, 99) . rand(1000000, 9999999),
                'to_number' => '+998' . rand(90, 99) . rand(1000000, 9999999),
                'status' => $status,
                'duration' => $status === 'completed' ? $duration : 0,
                'started_at' => $date,
                'ended_at' => $date->copy()->addSeconds($duration),
                'created_at' => $date,
                'updated_at' => $date,
            ]);
            $count++;
        }
        $this->command->info("  ✓ {$count} ta qo'ng'iroq");
    }

    private function seedJobPostings(): void
    {
        $postings = [
            ['title' => 'Frontend Developer', 'department' => 'Engineering', 'description' => 'React/Vue.js tajriba', 'salary_min' => 5000000, 'salary_max' => 12000000],
            ['title' => 'Backend Developer', 'department' => 'Engineering', 'description' => 'Laravel/Node.js tajriba', 'salary_min' => 6000000, 'salary_max' => 15000000],
            ['title' => 'UI/UX Designer', 'department' => 'Design', 'description' => 'Figma, Adobe XD', 'salary_min' => 4000000, 'salary_max' => 9000000],
            ['title' => 'Project Manager', 'department' => 'Management', 'description' => 'IT loyihalar boshqaruvi', 'salary_min' => 7000000, 'salary_max' => 15000000],
        ];

        foreach ($postings as $p) {
            $id = Str::uuid();
            $this->safeInsert('job_postings', ['business_id' => $this->bid, 'title' => $p['title']], array_merge($p, [
                'id' => $id,
                'slug' => Str::slug($p['title']) . '-' . Str::random(6),
                'business_id' => $this->bid,
                'status' => 'open',
                'is_public' => true,
                'employment_type' => 'full_time',
                'location' => 'Toshkent',
                'openings' => rand(1, 3),
                'posted_by' => $this->uid,
                'posted_date' => now()->subDays(rand(5, 30)),
            ]));

            // Har bir vakansiyaga 3-8 ta ariza
            for ($j = 0; $j < rand(3, 8); $j++) {
                $names = ['Sardor', 'Nilufar', 'Otabek', 'Malika', 'Jasur', 'Gulnora', 'Bobur', 'Shahlo'];
                $surnames = ['Toshmatov', 'Karimova', 'Umarov', 'Rahimov', 'Aliyeva', 'Qodirov', 'Ismoilova'];
                $name = $names[array_rand($names)] . ' ' . $surnames[array_rand($surnames)];
                $stages = ['new', 'screening', 'interview_scheduled', 'interview_done', 'assessment', 'offer', 'hired', 'rejected'];

                $this->safeInsert('job_applications', null, [
                    'id' => Str::uuid(),
                    'business_id' => $this->bid,
                    'job_posting_id' => $id,
                    'candidate_name' => $name,
                    'candidate_email' => Str::slug($name, '.') . rand(1, 50) . '@gmail.com',
                    'candidate_phone' => '+998' . rand(90, 99) . rand(1000000, 9999999),
                    'status' => 'new',
                    'pipeline_stage' => $stages[array_rand($stages)],
                    'rating' => rand(1, 5),
                    'years_of_experience' => rand(1, 8),
                    'applied_at' => now()->subDays(rand(1, 30)),
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now(),
                ]);
            }
        }
        $this->command->info('  ✓ 4 ta vakansiya + arizalar');
    }

    private function safeInsert(string $table, ?array $uniqueKey, array $data): void
    {
        try {
            $data['created_at'] = $data['created_at'] ?? now();
            $data['updated_at'] = $data['updated_at'] ?? now();

            if ($uniqueKey) {
                DB::table($table)->updateOrInsert($uniqueKey, $data);
            } else {
                DB::table($table)->insert($data);
            }
        } catch (\Exception $e) {
            // Skip silently — column mismatch or constraint
        }
    }
}
