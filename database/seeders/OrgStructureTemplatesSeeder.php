<?php

namespace Database\Seeders;

use App\Models\BusinessType;
use App\Models\DepartmentTemplate;
use App\Models\PositionTemplate;
use Illuminate\Database\Seeder;

class OrgStructureTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Business Types yaratish
        $this->createBusinessTypes();

        // 2. Statik Department Templates (har bir biznes uchun)
        $this->createStaticDepartments();

        // 3. Dinamik Department Templates (biznes turiga qarab)
        $this->createDynamicDepartments();

        // 4. Position Templates (har bir department uchun)
        $this->createPositionTemplates();
    }

    private function createBusinessTypes()
    {
        $types = [
            [
                'code' => 'education',
                'name_uz' => 'O\'quv Markazi',
                'name_ru' => 'Учебный центр',
                'name_en' => 'Education Center',
                'icon' => 'AcademicCapIcon',
                'color' => '#10B981',
                'description_uz' => 'O\'quv markazlari, kurslar, treninglar',
                'order' => 1,
            ],
            [
                'code' => 'healthcare',
                'name_uz' => 'Klinika / Tibbiyot',
                'name_ru' => 'Клиника',
                'name_en' => 'Healthcare',
                'icon' => 'HeartIcon',
                'color' => '#EF4444',
                'description_uz' => 'Klinikalar, shifokorlar, tibbiyot xizmatlari',
                'order' => 2,
            ],
            [
                'code' => 'construction',
                'name_uz' => 'Qurilish',
                'name_ru' => 'Строительство',
                'name_en' => 'Construction',
                'icon' => 'BuildingIcon',
                'color' => '#F97316',
                'description_uz' => 'Qurilish kompaniyalari',
                'order' => 3,
            ],
            [
                'code' => 'retail',
                'name_uz' => 'Savdo / Do\'kon',
                'name_ru' => 'Розничная торговля',
                'name_en' => 'Retail',
                'icon' => 'ShoppingBagIcon',
                'color' => '#8B5CF6',
                'description_uz' => 'Do\'konlar, savdo markazlari',
                'order' => 4,
            ],
            [
                'code' => 'restaurant',
                'name_uz' => 'Restoran / Kafe',
                'name_ru' => 'Ресторан',
                'name_en' => 'Restaurant',
                'icon' => 'FireIcon',
                'color' => '#EF4444',
                'description_uz' => 'Restoran, kafe, ovqatlanish',
                'order' => 5,
            ],
            [
                'code' => 'software',
                'name_uz' => 'IT / Dasturlash',
                'name_ru' => 'IT / Программирование',
                'name_en' => 'Software/IT',
                'icon' => 'CodeIcon',
                'color' => '#3B82F6',
                'description_uz' => 'Dasturiy ta\'minot, IT kompaniyalar',
                'order' => 6,
            ],
        ];

        foreach ($types as $type) {
            BusinessType::create($type);
        }
    }

    private function createStaticDepartments()
    {
        $staticDepartments = [
            [
                'code' => 'hr',
                'name_uz' => 'HR bo\'limi',
                'name_ru' => 'HR отдел',
                'name_en' => 'HR Department',
                'icon' => 'UsersIcon',
                'color' => '#9333EA',
                'type' => 'static',
                'yqm_description' => 'Tashkilotda to\'g\'ri odamlar to\'g\'ri joyda ishlaydi va rivojlanadi',
                'order' => 1,
            ],
            [
                'code' => 'finance',
                'name_uz' => 'Moliya bo\'limi',
                'name_ru' => 'Финансовый отдел',
                'name_en' => 'Finance Department',
                'icon' => 'BanknotesIcon',
                'color' => '#F59E0B',
                'type' => 'static',
                'yqm_description' => 'To\'g\'ri va o\'z vaqtida moliyaviy hisobotlar, daromad boshqaruvi',
                'order' => 2,
            ],
            [
                'code' => 'marketing',
                'name_uz' => 'Marketing bo\'limi',
                'name_ru' => 'Отдел маркетинга',
                'name_en' => 'Marketing Department',
                'icon' => 'MegaphoneIcon',
                'color' => '#3B82F6',
                'type' => 'static',
                'yqm_description' => 'Sifatli lidlar oqimi va brendni rivojlantirish',
                'order' => 3,
            ],
            [
                'code' => 'sales',
                'name_uz' => 'Sotuv bo\'limi',
                'name_ru' => 'Отдел продаж',
                'name_en' => 'Sales Department',
                'icon' => 'ShoppingCartIcon',
                'color' => '#DC2626',
                'type' => 'static',
                'yqm_description' => 'To\'langan shartnomalar va mijozlar bilan uzoq muddatli munosabatlar',
                'order' => 4,
            ],
            [
                'code' => 'tech_support',
                'name_uz' => 'Texnik bo\'lim',
                'name_ru' => 'Техническая поддержка',
                'name_en' => 'Technical Support',
                'icon' => 'WrenchIcon',
                'color' => '#6366F1',
                'type' => 'static',
                'yqm_description' => 'Barcha tizimlar ishlaydi, texnik muammolar tez hal bo\'ladi',
                'order' => 5,
            ],
        ];

        foreach ($staticDepartments as $dept) {
            DepartmentTemplate::create($dept);
        }
    }

    private function createDynamicDepartments()
    {
        // Education (O'quv Markazi)
        $education = BusinessType::where('code', 'education')->first();
        if ($education) {
            DepartmentTemplate::create([
                'code' => 'education',
                'name_uz' => 'O\'quv bo\'limi',
                'name_ru' => 'Учебный отдел',
                'name_en' => 'Education Department',
                'icon' => 'AcademicCapIcon',
                'color' => '#10B981',
                'type' => 'dynamic',
                'business_type_id' => $education->id,
                'yqm_description' => 'O\'quvchilar bilim va ko\'nikmalarni egallaydi, natijalarga erishadi',
                'order' => 10,
            ]);
        }

        // Healthcare (Klinika)
        $healthcare = BusinessType::where('code', 'healthcare')->first();
        if ($healthcare) {
            DepartmentTemplate::create([
                'code' => 'medical',
                'name_uz' => 'Shifokorlar bo\'limi',
                'name_ru' => 'Медицинский отдел',
                'name_en' => 'Medical Department',
                'icon' => 'HeartIcon',
                'color' => '#EF4444',
                'type' => 'dynamic',
                'business_type_id' => $healthcare->id,
                'yqm_description' => 'Bemorlar sog\'aydi va sog\'lig\'i yaxshilanadi',
                'order' => 10,
            ]);
        }

        // Construction
        $construction = BusinessType::where('code', 'construction')->first();
        if ($construction) {
            DepartmentTemplate::create([
                'code' => 'construction_ops',
                'name_uz' => 'Qurilish bo\'limi',
                'name_ru' => 'Строительный отдел',
                'name_en' => 'Construction Operations',
                'icon' => 'BuildingIcon',
                'color' => '#F97316',
                'type' => 'dynamic',
                'business_type_id' => $construction->id,
                'yqm_description' => 'Loyihalar muddatida, sifatli va xavfsiz quriladi',
                'order' => 10,
            ]);
        }

        // Restaurant
        $restaurant = BusinessType::where('code', 'restaurant')->first();
        if ($restaurant) {
            DepartmentTemplate::create([
                'code' => 'kitchen',
                'name_uz' => 'Oshxona',
                'name_ru' => 'Кухня',
                'name_en' => 'Kitchen',
                'icon' => 'FireIcon',
                'color' => '#EF4444',
                'type' => 'dynamic',
                'business_type_id' => $restaurant->id,
                'yqm_description' => 'Mazali va o\'z vaqtida tayyorlangan ovqatlar',
                'order' => 10,
            ]);
        }

        // Software
        $software = BusinessType::where('code', 'software')->first();
        if ($software) {
            DepartmentTemplate::create([
                'code' => 'development',
                'name_uz' => 'Dasturlash bo\'limi',
                'name_ru' => 'Отдел разработки',
                'name_en' => 'Development Department',
                'icon' => 'CodeIcon',
                'color' => '#3B82F6',
                'type' => 'dynamic',
                'business_type_id' => $software->id,
                'yqm_description' => 'Ishlaydigan va sifatli dasturiy mahsulotlar',
                'order' => 10,
            ]);
        }
    }

    private function createPositionTemplates()
    {
        // HR Department Positions
        $hr = DepartmentTemplate::where('code', 'hr')->first();
        if ($hr) {
            $this->createHRPositions($hr->id);
        }

        // Marketing Department Positions
        $marketing = DepartmentTemplate::where('code', 'marketing')->first();
        if ($marketing) {
            $this->createMarketingPositions($marketing->id);
        }

        // Sales Department Positions
        $sales = DepartmentTemplate::where('code', 'sales')->first();
        if ($sales) {
            $this->createSalesPositions($sales->id);
        }

        // Education Department Positions
        $education = DepartmentTemplate::where('code', 'education')->first();
        if ($education) {
            $this->createEducationPositions($education->id);
        }
    }

    private function createHRPositions($deptId)
    {
        $positions = [
            [
                'code' => 'hr_head',
                'title_uz' => 'HR bo\'lim boshlig\'i',
                'title_ru' => 'Руководитель HR',
                'title_en' => 'HR Head',
                'level' => 1,
                'reports_to' => 'Direktor',
                'yqm_primary' => 'Tashkilotda samarali jamoa tizimi ishlaydi',
                'yqm_description' => 'HR strategiyasi amalga oshiriladi, xodimlar qoniqish darajasi yuqori',
                'yqm_metrics' => [
                    ['metric' => 'Xodimlar qoniqishi', 'target' => '85%+', 'frequency' => 'quarterly'],
                    ['metric' => 'Staff retention', 'target' => '90%+', 'frequency' => 'yearly'],
                ],
                'order' => 1,
            ],
            [
                'code' => 'hr_manager',
                'title_uz' => 'HR menejer',
                'title_ru' => 'HR менеджер',
                'title_en' => 'HR Manager',
                'level' => 2,
                'reports_to' => 'HR bo\'lim boshlig\'i',
                'yqm_primary' => 'Xodimlar to\'g\'ri tanlangan va rivojlantiriladi',
                'yqm_description' => 'Recruiting, onboarding va training jarayonlari samarali',
                'yqm_metrics' => [
                    ['metric' => 'Time to hire', 'target' => '< 30 kun', 'frequency' => 'monthly'],
                    ['metric' => 'Training completion', 'target' => '95%+', 'frequency' => 'quarterly'],
                ],
                'order' => 2,
            ],
        ];

        foreach ($positions as $position) {
            $position['department_template_id'] = $deptId;
            PositionTemplate::create($position);
        }
    }

    private function createMarketingPositions($deptId)
    {
        $positions = [
            [
                'code' => 'marketing_head',
                'title_uz' => 'Marketing bo\'lim boshlig\'i',
                'title_ru' => 'Руководитель маркетинга',
                'title_en' => 'Marketing Head',
                'level' => 1,
                'reports_to' => 'Direktor',
                'yqm_primary' => 'Barqaror lidlar oqimi va ROI > 200%',
                'yqm_description' => 'Marketing strategiyasi ishlaydi, brend kuchaymoqda',
                'yqm_metrics' => [
                    ['metric' => 'Oylik lidlar', 'target' => '200+', 'frequency' => 'monthly'],
                    ['metric' => 'Marketing ROI', 'target' => '200%+', 'frequency' => 'monthly'],
                ],
                'order' => 1,
            ],
            [
                'code' => 'marketing_manager',
                'title_uz' => 'Marketing menejer',
                'title_ru' => 'Маркетинг менеджер',
                'title_en' => 'Marketing Manager',
                'level' => 2,
                'reports_to' => 'Marketing bo\'lim boshlig\'i',
                'yqm_primary' => 'Oyiga 50+ sifatli lid',
                'yqm_description' => 'Kampaniyalar muvaffaqiyatli, lidlar sifatli',
                'yqm_metrics' => [
                    ['metric' => 'Lidlar soni', 'target' => '50+', 'frequency' => 'monthly'],
                    ['metric' => 'Lid konversiyasi', 'target' => '15%+', 'frequency' => 'monthly'],
                ],
                'order' => 2,
            ],
            [
                'code' => 'smm_specialist',
                'title_uz' => 'SMM mutaxassis',
                'title_ru' => 'SMM специалист',
                'title_en' => 'SMM Specialist',
                'level' => 3,
                'reports_to' => 'Marketing menejer',
                'yqm_primary' => 'Ijtimoiy tarmoqlarda faol auditoriya va engagement',
                'yqm_description' => 'Kontent rejasi bajariladi, engagement o\'sadi',
                'yqm_metrics' => [
                    ['metric' => 'Engagement rate', 'target' => '5%+', 'frequency' => 'monthly'],
                    ['metric' => 'Followers o\'sishi', 'target' => '10%', 'frequency' => 'monthly'],
                ],
                'order' => 3,
            ],
        ];

        foreach ($positions as $position) {
            $position['department_template_id'] = $deptId;
            PositionTemplate::create($position);
        }
    }

    private function createSalesPositions($deptId)
    {
        $positions = [
            [
                'code' => 'sales_head',
                'title_uz' => 'Sotuv bo\'lim boshlig\'i',
                'title_ru' => 'Руководитель отдела продаж',
                'title_en' => 'Sales Head',
                'level' => 1,
                'reports_to' => 'Direktor',
                'yqm_primary' => 'Oylik sotuv maqsadiga erishish',
                'yqm_description' => 'Sotuv jarayoni optimallashtirilgan, conversion yuqori',
                'yqm_metrics' => [
                    ['metric' => 'Oylik sotuv', 'target' => '100% + maqsad', 'frequency' => 'monthly'],
                    ['metric' => 'Conversion rate', 'target' => '25%+', 'frequency' => 'monthly'],
                ],
                'order' => 1,
            ],
            [
                'code' => 'sales_manager',
                'title_uz' => 'Sotuv menejeri',
                'title_ru' => 'Менеджер по продажам',
                'title_en' => 'Sales Manager',
                'level' => 2,
                'reports_to' => 'Sotuv bo\'lim boshlig\'i',
                'yqm_primary' => 'Oyiga 10+ yopilgan shartnoma',
                'yqm_description' => 'Mijozlar bilan munosabat, shartnomalar tuzish',
                'yqm_metrics' => [
                    ['metric' => 'Yopilgan deals', 'target' => '10+', 'frequency' => 'monthly'],
                    ['metric' => 'Average deal size', 'target' => 'Plan', 'frequency' => 'monthly'],
                ],
                'order' => 2,
            ],
        ];

        foreach ($positions as $position) {
            $position['department_template_id'] = $deptId;
            PositionTemplate::create($position);
        }
    }

    private function createEducationPositions($deptId)
    {
        $positions = [
            [
                'code' => 'education_head',
                'title_uz' => 'O\'quv bo\'lim boshlig\'i',
                'title_ru' => 'Руководитель учебного отдела',
                'title_en' => 'Education Head',
                'level' => 1,
                'reports_to' => 'Direktor',
                'yqm_primary' => 'O\'quvchilar natijalarga erishadi, NPS > 70',
                'yqm_description' => 'O\'quv sifati yuqori, o\'quvchilar mamnun',
                'yqm_metrics' => [
                    ['metric' => 'NPS', 'target' => '70+', 'frequency' => 'quarterly'],
                    ['metric' => 'Kurs tugallash', 'target' => '85%+', 'frequency' => 'monthly'],
                ],
                'order' => 1,
            ],
            [
                'code' => 'teacher',
                'title_uz' => 'O\'qituvchi / Trener',
                'title_ru' => 'Преподаватель',
                'title_en' => 'Teacher/Trainer',
                'level' => 2,
                'reports_to' => 'O\'quv bo\'lim boshlig\'i',
                'yqm_primary' => 'O\'quvchilar dars materialini o\'zlashtirishadi',
                'yqm_description' => 'Darslar sifatli, o\'quvchilar feedback ijobiy',
                'yqm_metrics' => [
                    ['metric' => 'O\'quvchi bahosi', 'target' => '4.5+/5', 'frequency' => 'monthly'],
                    ['metric' => 'Darsga qatnashish', 'target' => '90%+', 'frequency' => 'monthly'],
                ],
                'order' => 2,
            ],
        ];

        foreach ($positions as $position) {
            $position['department_template_id'] = $deptId;
            PositionTemplate::create($position);
        }
    }
}
