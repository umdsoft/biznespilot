<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Competitor;
use Illuminate\Database\Seeder;

class TestSwotDataSeeder extends Seeder
{
    private string $bid = 'aa9b1e35-020b-4ec1-b1ad-ef9f931cfac8';

    public function run(): void
    {
        $this->command->info('SWOT ma\'lumotlari yaratilmoqda...');

        $this->seedBusinessSwot();
        $this->seedCompetitorSwots();

        $this->command->info('SWOT ma\'lumotlari tayyor!');
    }

    private function item(string $text): array
    {
        return ['text' => $text, 'business_id' => $this->bid];
    }

    private function seedBusinessSwot(): void
    {
        $swot = [
            'strengths' => [
                $this->item('Professional jamoada 5+ yillik tajriba'),
                $this->item('Web va mobil ilovalarni to\'liq siklda ishlab chiqish qobiliyati'),
                $this->item('Mijozlarga individual yondashuv va tezkor javob berish'),
                $this->item('Raqobatbardosh narxlar siyosati'),
                $this->item('Zamonaviy texnologiyalar (Laravel, Vue, React Native) dan foydalanish'),
                $this->item('Kuchli portfolio va muvaffaqiyatli loyihalar tajribasi'),
            ],
            'weaknesses' => [
                $this->item('Marketing byudjeti cheklangan'),
                $this->item('Brend tanilishi hali past — bozorda yangi'),
                $this->item('Katta hajmli loyihalar uchun jamoa yetarli emas'),
                $this->item('Onlayn marketing strategiyasi yetarli darajada ishlab chiqilmagan'),
                $this->item('Mijozlarni qaytadan jalb qilish tizimi yo\'q'),
            ],
            'opportunities' => [
                $this->item('O\'zbekistonda raqamlashtirish tezlashmoqda — IT xizmatlarga talab oshmoqda'),
                $this->item('Telegram bot va avtomatlashtirish bozoriga kirib borish'),
                $this->item('IT Park rezidentligi orqali soliq imtiyozlaridan foydalanish'),
                $this->item('Kichik bizneslar uchun tayyor yechimlar (SaaS) yaratish'),
                $this->item('Xorijiy bozorlarga chiqish (outsourcing)'),
                $this->item('AI texnologiyalarini xizmatlarga integratsiya qilish'),
            ],
            'threats' => [
                $this->item('Yirik IT kompaniyalar (UzDev, IT Park) bilan raqobat'),
                $this->item('Freelancerlar past narxda xizmat ko\'rsatishi'),
                $this->item('Malakali dasturchilar uchun kadrlar raqobati kuchli'),
                $this->item('Iqtisodiy beqarorlik va valyuta kursi o\'zgarishi'),
                $this->item('Mijozlarning IT xizmatlarga bo\'lgan ishonchi hali past'),
            ],
        ];

        $business = Business::find($this->bid);
        $business->swot_data = $swot;
        $business->swot_updated_at = now();
        $business->save();

        $this->command->info('  ✓ Biznes SWOT: 6 kuchli, 5 zaif, 6 imkoniyat, 5 tahdid');
    }

    private function seedCompetitorSwots(): void
    {
        $swots = [
            'UzDev Solutions' => [
                'strengths' => [
                    $this->item('Bozorda 10+ yillik tajriba va kuchli brend'),
                    $this->item('Katta jamoasi (50+ dasturchi)'),
                    $this->item('Yirik korporativ mijozlar bazasi'),
                    $this->item('Xalqaro loyihalarda ishtirok tajribasi'),
                ],
                'weaknesses' => [
                    $this->item('Narxlari bozor o\'rtachasidan 2-3 baravar yuqori'),
                    $this->item('Kichik loyihalarga e\'tibor bermaydi'),
                    $this->item('Loyiha muddatlari ko\'pincha cho\'ziladi'),
                    $this->item('Mijoz bilan aloqa sekin — byurokratik jarayonlar'),
                ],
                'opportunities' => [
                    $this->item('Ularning qoldirgan kichik mijozlarini jalb qilish'),
                    $this->item('Tezkor xizmat ko\'rsatish orqali farqlanish'),
                    $this->item('Narx bo\'yicha raqobat ustunligini ta\'kidlash'),
                ],
                'threats' => [
                    $this->item('Agar kichik biznes segmentiga kirsa, kuchli raqib bo\'ladi'),
                    $this->item('Ularning brend kuchi bizning mijozlarni tortishi mumkin'),
                    $this->item('Narxlarni pasaytirish strategiyasini qo\'llashi mumkin'),
                ],
            ],
            'IT Park Residents' => [
                'strengths' => [
                    $this->item('IT Park ekotizimi va davlat qo\'llab-quvvatlashi'),
                    $this->item('Soliq imtiyozlari va grant imkoniyatlari'),
                    $this->item('Zamonaviy ofis va infratuzilma'),
                    $this->item('Networking va hamkorlik imkoniyatlari keng'),
                ],
                'weaknesses' => [
                    $this->item('Bir nechta kompaniya sifatida, yagona strategiya yo\'q'),
                    $this->item('Sifat nazorati turlicha — ba\'zilari past sifatda ishlaydi'),
                    $this->item('Ko\'pchilik startaplar — barqaror emas'),
                ],
                'opportunities' => [
                    $this->item('IT Park bilan hamkorlik orqali o\'sish'),
                    $this->item('Rezidentlik orqali imtiyozlardan foydalanish'),
                    $this->item('Startaplar bilan subpudrat ishlash'),
                ],
                'threats' => [
                    $this->item('IT Park rezidentlari sonining tez o\'sishi raqobatni kuchaytiradi'),
                    $this->item('Davlat loyihalarini monopolizatsiya qilishi mumkin'),
                ],
            ],
            'Najot Talim' => [
                'strengths' => [
                    $this->item('O\'zbekistonda eng taniqli IT ta\'lim brendi'),
                    $this->item('Keng bitiruvchilar tarmog\'i — potentsial kadrlar bazasi'),
                    $this->item('Kuchli ijtimoiy tarmoqlar marketingi'),
                    $this->item('Ta\'lim + loyiha modeli orqali arzon ishchi kuchi'),
                ],
                'weaknesses' => [
                    $this->item('Asosiy fokusi ta\'lim, dasturlash xizmati ikkinchi darajali'),
                    $this->item('Loyiha sifati ba\'zan past — talabalar ishlaydi'),
                    $this->item('Murakkab enterprise loyihalar tajribasi kam'),
                ],
                'opportunities' => [
                    $this->item('Ularning bitiruvchilarini yollash — tayyor kadrlar'),
                    $this->item('Sifat farqi orqali premium segmentda ishlash'),
                    $this->item('Hamkorlik — biz loyiha, ular kadr tayyorlash'),
                ],
                'threats' => [
                    $this->item('Arzon narxda xizmat ko\'rsatib mijozlarni tortishi'),
                    $this->item('Marketing kuchi yuqori — brendni tezda kuchaytirishi mumkin'),
                    $this->item('IT xizmatlar bozorining ta\'lim segmentidan kengayishi'),
                ],
            ],
        ];

        $count = 0;
        $competitors = Competitor::where('business_id', $this->bid)->get();

        foreach ($competitors as $comp) {
            if (isset($swots[$comp->name])) {
                $comp->swot_data = $swots[$comp->name];
                $comp->swot_analyzed_at = now();
                $comp->save();
                $count++;
                $s = $swots[$comp->name];
                $this->command->info("  ✓ {$comp->name}: " . count($s['strengths']) . " kuchli, " . count($s['weaknesses']) . " zaif, " . count($s['opportunities']) . " imkoniyat, " . count($s['threats']) . " tahdid");
            }
        }
        $this->command->info("  ✓ Jami {$count} ta raqobatchi SWOT to'ldirildi");
    }
}
