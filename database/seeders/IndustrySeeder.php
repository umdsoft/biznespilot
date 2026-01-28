<?php

namespace Database\Seeders;

use App\Models\Industry;
use Illuminate\Database\Seeder;

class IndustrySeeder extends Seeder
{
    public function run(): void
    {
        $industries = [
            [
                'name_uz' => 'E-tijorat',
                'name_en' => 'E-commerce',
                'slug' => 'e-commerce',
                'icon' => 'shopping-cart',
                'sort_order' => 1,
                'sub_industries' => [
                    ['name_uz' => 'Kiyim-kechak', 'name_en' => 'Fashion & Apparel', 'slug' => 'fashion'],
                    ['name_uz' => 'Elektronika', 'name_en' => 'Electronics', 'slug' => 'electronics'],
                    ['name_uz' => 'Oziq-ovqat', 'name_en' => 'Food & Grocery', 'slug' => 'food-grocery'],
                    ['name_uz' => 'Go\'zallik', 'name_en' => 'Beauty & Cosmetics', 'slug' => 'beauty'],
                ],
            ],
            [
                'name_uz' => 'Ta\'lim',
                'name_en' => 'Education',
                'slug' => 'education',
                'icon' => 'academic-cap',
                'sort_order' => 2,
                'sub_industries' => [
                    ['name_uz' => 'Onlayn kurslar', 'name_en' => 'Online Courses', 'slug' => 'online-courses'],
                    ['name_uz' => 'Tillar', 'name_en' => 'Language Learning', 'slug' => 'languages'],
                    ['name_uz' => 'Repetitorlik', 'name_en' => 'Tutoring', 'slug' => 'tutoring'],
                ],
            ],
            [
                'name_uz' => 'Xizmatlar',
                'name_en' => 'Services',
                'slug' => 'services',
                'icon' => 'briefcase',
                'sort_order' => 3,
                'sub_industries' => [
                    ['name_uz' => 'Konsalting', 'name_en' => 'Consulting', 'slug' => 'consulting'],
                    ['name_uz' => 'Marketing agentlik', 'name_en' => 'Marketing Agency', 'slug' => 'marketing-agency'],
                    ['name_uz' => 'IT xizmatlar', 'name_en' => 'IT Services', 'slug' => 'it-services'],
                    ['name_uz' => 'Yuridik xizmatlar', 'name_en' => 'Legal Services', 'slug' => 'legal'],
                ],
            ],
            [
                'name_uz' => 'Sog\'liqni saqlash',
                'name_en' => 'Healthcare',
                'slug' => 'healthcare',
                'icon' => 'heart',
                'sort_order' => 4,
                'sub_industries' => [
                    ['name_uz' => 'Klinikalar', 'name_en' => 'Clinics', 'slug' => 'clinics'],
                    ['name_uz' => 'Fitness', 'name_en' => 'Fitness & Gym', 'slug' => 'fitness'],
                    ['name_uz' => 'Salomatlik mahsulotlari', 'name_en' => 'Health Products', 'slug' => 'health-products'],
                ],
            ],
            [
                'name_uz' => 'Ko\'chmas mulk',
                'name_en' => 'Real Estate',
                'slug' => 'real-estate',
                'icon' => 'home',
                'sort_order' => 5,
                'sub_industries' => [
                    ['name_uz' => 'Turar-joy', 'name_en' => 'Residential', 'slug' => 'residential'],
                    ['name_uz' => 'Tijorat binolari', 'name_en' => 'Commercial', 'slug' => 'commercial'],
                    ['name_uz' => 'Ijaraga berish', 'name_en' => 'Rental', 'slug' => 'rental'],
                ],
            ],
            [
                'name_uz' => 'Restoran va ovqatlanish',
                'name_en' => 'Food & Restaurant',
                'slug' => 'food-restaurant',
                'icon' => 'cake',
                'sort_order' => 6,
                'sub_industries' => [
                    ['name_uz' => 'Restoranlar', 'name_en' => 'Restaurants', 'slug' => 'restaurants'],
                    ['name_uz' => 'Yetkazib berish', 'name_en' => 'Food Delivery', 'slug' => 'food-delivery'],
                    ['name_uz' => 'Katering', 'name_en' => 'Catering', 'slug' => 'catering'],
                ],
            ],
            [
                'name_uz' => 'Avtomobil',
                'name_en' => 'Automotive',
                'slug' => 'automotive',
                'icon' => 'truck',
                'sort_order' => 7,
                'sub_industries' => [
                    ['name_uz' => 'Avtosalon', 'name_en' => 'Car Dealership', 'slug' => 'car-dealership'],
                    ['name_uz' => 'Avtoservis', 'name_en' => 'Auto Service', 'slug' => 'auto-service'],
                    ['name_uz' => 'Ehtiyot qismlar', 'name_en' => 'Auto Parts', 'slug' => 'auto-parts'],
                ],
            ],
            [
                'name_uz' => 'Sayohat va turizm',
                'name_en' => 'Travel & Tourism',
                'slug' => 'travel-tourism',
                'icon' => 'globe',
                'sort_order' => 8,
                'sub_industries' => [
                    ['name_uz' => 'Turoperator', 'name_en' => 'Tour Operator', 'slug' => 'tour-operator'],
                    ['name_uz' => 'Mehmonxona', 'name_en' => 'Hotels', 'slug' => 'hotels'],
                    ['name_uz' => 'Transport', 'name_en' => 'Transportation', 'slug' => 'transportation'],
                ],
            ],
            [
                'name_uz' => 'Qurilish',
                'name_en' => 'Construction',
                'slug' => 'construction',
                'icon' => 'office-building',
                'sort_order' => 9,
                'sub_industries' => [
                    ['name_uz' => 'Qurilish kompaniya', 'name_en' => 'Construction Company', 'slug' => 'construction-company'],
                    ['name_uz' => 'Qurilish materiallari', 'name_en' => 'Building Materials', 'slug' => 'building-materials'],
                    ['name_uz' => 'Dizayn', 'name_en' => 'Interior Design', 'slug' => 'interior-design'],
                ],
            ],
            [
                'name_uz' => 'Boshqa',
                'name_en' => 'Other',
                'slug' => 'other',
                'icon' => 'dots-horizontal',
                'sort_order' => 99,
                'sub_industries' => [],
            ],
        ];

        foreach ($industries as $industryData) {
            $subIndustries = $industryData['sub_industries'] ?? [];
            unset($industryData['sub_industries']);

            $industry = Industry::updateOrCreate(
                ['slug' => $industryData['slug']],
                $industryData
            );

            foreach ($subIndustries as $subIndustryData) {
                Industry::updateOrCreate(
                    ['slug' => $subIndustryData['slug']],
                    [
                        'parent_id' => $industry->id,
                        'name_uz' => $subIndustryData['name_uz'],
                        'name_en' => $subIndustryData['name_en'],
                        'slug' => $subIndustryData['slug'],
                        'icon' => $industry->icon,
                        'sort_order' => 0,
                    ]
                );
            }
        }
    }
}
