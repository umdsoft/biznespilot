<?php

namespace Database\Seeders;

use App\Models\ContentIdea;
use App\Models\ContentIdeaCollection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContentIdeasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ==================== COLLECTIONS (To'plamlar) ====================
        $collections = $this->createCollections();

        // ==================== UNIVERSAL IDEAS (Barcha bizneslar uchun) ====================
        $this->createUniversalIdeas($collections);

        // ==================== SEASONAL IDEAS (Mavsumiy) ====================
        $this->createSeasonalIdeas($collections);

        // ==================== CATEGORY-SPECIFIC IDEAS ====================
        $this->createPromotionIdeas($collections);
        $this->createEngagementIdeas($collections);
        $this->createEducationalIdeas($collections);

        $this->command->info('Content Ideas seeded successfully!');
    }

    /**
     * Create collections
     */
    protected function createCollections(): array
    {
        $collections = [
            [
                'name' => 'Kundalik Postlar',
                'description' => 'Har kun foydalanish mumkin bo\'lgan g\'oyalar',
                'icon' => '📅',
                'color' => '#3B82F6',
                'is_global' => true,
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Aksiyalar va Chegirmalar',
                'description' => 'Sotuv oshirish uchun aksiya g\'oyalari',
                'icon' => '🏷️',
                'color' => '#EF4444',
                'is_global' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Bayramlar',
                'description' => 'Bayram va muhim sanalar uchun g\'oyalar',
                'icon' => '🎉',
                'color' => '#F59E0B',
                'is_global' => true,
                'is_featured' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Engagement Boosters',
                'description' => 'Auditoriya faolligini oshirish',
                'icon' => '💬',
                'color' => '#10B981',
                'is_global' => true,
                'is_featured' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Behind the Scenes',
                'description' => 'Sahna ortidagi kontentlar',
                'icon' => '🎬',
                'color' => '#8B5CF6',
                'is_global' => true,
                'is_featured' => false,
                'sort_order' => 5,
            ],
            [
                'name' => 'Ta\'limiy Kontentlar',
                'description' => 'Foydali ma\'lumot va maslahatlar',
                'icon' => '📚',
                'color' => '#06B6D4',
                'is_global' => true,
                'is_featured' => false,
                'sort_order' => 6,
            ],
        ];

        $result = [];
        foreach ($collections as $data) {
            $collection = ContentIdeaCollection::create($data);
            $result[$data['name']] = $collection;
        }

        return $result;
    }

    /**
     * Universal ideas for all businesses
     */
    protected function createUniversalIdeas(array $collections): void
    {
        $ideas = [
            // ==================== DAILY POST IDEAS ====================
            [
                'title' => 'Dushanba motivatsiyasi',
                'description' => 'Hafta boshida auditoriyani ilhomlantiring. Motivatsion iqtibos yoki muvaffaqiyat hikoyasi ulashing.',
                'example_content' => "Yangi hafta - yangi imkoniyatlar! 🚀\n\nHar bir dushanba - bu o'z maqsadlaringizga bir qadam yaqinlashish imkoniyati.\n\nBu hafta qanday maqsadlaringiz bor? 👇",
                'key_points' => ['Motivatsion iqtibos', 'Haftalik maqsad', 'Savol'],
                'suggested_emojis' => ['🚀', '💪', '✨', '🎯'],
                'content_type' => 'post',
                'purpose' => 'inspire',
                'category' => 'motivation',
                'quality_score' => 75,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Kundalik Postlar',
            ],
            [
                'title' => 'Juma kutlovi',
                'description' => 'Juma kuni auditoriyani tabriklab, dam olish kunlari rejalarini so\'rang.',
                'example_content' => "Juma muborak! 🌙\n\nBir haftalik mehnat ortda qoldi.\nSizga yaxshi dam olish tilaymiz!\n\nDam olish kunlaringizda nima rejalaringiz bor?",
                'key_points' => ['Tabrik', 'Dam olish tilagi', 'Savol'],
                'suggested_emojis' => ['🌙', '☀️', '🎉', '😊'],
                'content_type' => 'post',
                'purpose' => 'engage',
                'category' => 'holiday',
                'quality_score' => 70,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Kundalik Postlar',
            ],
            [
                'title' => 'Mijoz sharhi/guvohnoma',
                'description' => 'Mamnun mijozlarning sharhlarini ulashib, ishonchni mustahkamlang.',
                'example_content' => "Mijozlarimizning fikri biz uchun muhim! 💝\n\n⭐⭐⭐⭐⭐\n\"{Mijoz sharhi}\"\n- {Mijoz ismi}\n\nBizga ishonganingiz uchun rahmat! 🙏",
                'key_points' => ['Haqiqiy sharh', 'Reyting', 'Minnatdorchilik'],
                'suggested_emojis' => ['💝', '⭐', '🙏', '❤️'],
                'content_type' => 'post',
                'purpose' => 'sell',
                'category' => 'customer_story',
                'quality_score' => 85,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Kundalik Postlar',
            ],
            [
                'title' => 'Mahsulot/Xizmat taqdimoti',
                'description' => 'Yangi yoki mashhur mahsulotni batafsil taqdim eting.',
                'example_content' => "🔥 Eng ko'p so'raladigan mahsulotimiz!\n\n✅ {Xususiyat 1}\n✅ {Xususiyat 2}\n✅ {Xususiyat 3}\n\n💰 Narxi: {narx} so'm\n📦 Yetkazib berish bepul!\n\nBuyurtma uchun: DM yoki bio'dagi link 👆",
                'key_points' => ['Xususiyatlar', 'Narx', 'CTA'],
                'suggested_emojis' => ['🔥', '✅', '💰', '📦'],
                'content_type' => 'post',
                'purpose' => 'sell',
                'category' => 'product',
                'quality_score' => 80,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Kundalik Postlar',
            ],
            [
                'title' => 'Jamoa a\'zosi tanishtiruv',
                'description' => 'Jamoa a\'zolarini tanishtirib, brendga insoniylik qo\'shing.',
                'example_content' => "Jamoamiz bilan tanishing! 👋\n\n{Ism} - {Lavozim}\n\n🎯 Vazifasi: {qisqa tavsif}\n❤️ Sevimli ishi: {hobby}\n💬 Hayotiy shiori: \"{iqtibos}\"\n\nJamoamizdagi eng yaxshi narsalardan biri - bu ajoyib odamlar! 🌟",
                'key_points' => ['Ism va lavozim', 'Shaxsiy ma\'lumot', 'Iliq ohang'],
                'suggested_emojis' => ['👋', '🎯', '❤️', '🌟'],
                'content_type' => 'post',
                'purpose' => 'engage',
                'category' => 'behind_scenes',
                'quality_score' => 75,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Behind the Scenes',
            ],

            // ==================== ENGAGEMENT IDEAS ====================
            [
                'title' => 'Bu yoki U? Poll',
                'description' => 'Ikki variant orasida tanlash - engagement oshirish uchun klassik usul.',
                'example_content' => "Bu yoki U? 🤔\n\n☕ Choy\nyo'ki\n☕ Qahva\n\nJavobingizni izohlarda yozing! 👇",
                'key_points' => ['Ikki variant', 'Oddiy savol', 'Izoh so\'rash'],
                'suggested_emojis' => ['🤔', '☕', '👇', '❓'],
                'content_type' => 'story',
                'purpose' => 'engage',
                'category' => 'question',
                'quality_score' => 80,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Engagement Boosters',
            ],
            [
                'title' => 'Gap to\'ldiring challenge',
                'description' => 'Auditoriyaga jumlani to\'ldirishni taklif qiling.',
                'example_content' => "Gap to'ldiring! ✍️\n\n\"Men eng baxtli bo'laman qachonki...\"\n\n_______________\n\nEng yaxshi javoblarni story'da ulashamiz! 🎁",
                'key_points' => ['Ochiq jumla', 'Bo\'sh joy', 'Rag\'bat'],
                'suggested_emojis' => ['✍️', '🎁', '💭', '✨'],
                'content_type' => 'post',
                'purpose' => 'engage',
                'category' => 'question',
                'quality_score' => 85,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Engagement Boosters',
            ],
            [
                'title' => 'Raqamlar bilan gap',
                'description' => 'Qiziqarli statistika yoki raqamlar ulashing.',
                'example_content' => "Raqamlar gapiradi! 📊\n\n🔢 5000+ - mamnun mijozlar\n📦 10000+ - yetkazilgan buyurtmalar\n⭐ 4.9 - o'rtacha reyting\n🎂 3 - faoliyat yillari\n\nIshonchingiz uchun rahmat! ❤️",
                'key_points' => ['Aniq raqamlar', 'Yutuqlar', 'Vizual format'],
                'suggested_emojis' => ['📊', '🔢', '⭐', '❤️'],
                'content_type' => 'carousel',
                'purpose' => 'sell',
                'category' => 'news',
                'quality_score' => 75,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Kundalik Postlar',
            ],
            [
                'title' => 'Do\'stingizni belgilang',
                'description' => 'Auditoriyani do\'stlarini belgilashga chaqirish - organic reach oshiradi.',
                'example_content' => "Do'stingizni belgilang! 👇\n\n{Mavzu} bo'yicha eng yaxshi bilimdon do'stingiz kim?\n\nUni belgilang va birga o'rganamiz! 📚\n\n#do'stlar #{mavzu}",
                'key_points' => ['Belgilash chaqiriq', 'Mavzu bog\'lash', 'Hashtaglar'],
                'suggested_emojis' => ['👇', '📚', '🤝', '💡'],
                'content_type' => 'post',
                'purpose' => 'engage',
                'category' => 'question',
                'quality_score' => 70,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Engagement Boosters',
            ],

            // ==================== EDUCATIONAL IDEAS ====================
            [
                'title' => '5 ta maslahat formati',
                'description' => 'Raqamlangan maslahatlar ro\'yxati - oson o\'qiladi va saqlanadi.',
                'example_content' => "5 ta maslahat: {Mavzu} 📝\n\n1️⃣ {Maslahat 1}\n2️⃣ {Maslahat 2}\n3️⃣ {Maslahat 3}\n4️⃣ {Maslahat 4}\n5️⃣ {Maslahat 5}\n\nQaysi biri sizga eng foydali? 👇\n\n💾 Saqlang va do'stlaringiz bilan ulashing!",
                'key_points' => ['Raqamlangan ro\'yxat', 'Qisqa maslahatlar', 'Saqlash chaqirig\'i'],
                'suggested_emojis' => ['📝', '1️⃣', '💾', '👇'],
                'content_type' => 'carousel',
                'purpose' => 'educate',
                'category' => 'tips',
                'quality_score' => 90,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Ta\'limiy Kontentlar',
            ],
            [
                'title' => 'Xato va To\'g\'ri taqqoslash',
                'description' => 'Keng tarqalgan xatolarni to\'g\'ri yo\'l bilan solishtiring.',
                'example_content' => "❌ XATO: {xato usul}\n\n✅ TO'G'RI: {to'g'ri usul}\n\nKo'pchilik bu xatoni qiladi!\n\nSiz qanday qilasiz? 🤔",
                'key_points' => ['Xato misol', 'To\'g\'ri misol', 'Savol'],
                'suggested_emojis' => ['❌', '✅', '🤔', '💡'],
                'content_type' => 'post',
                'purpose' => 'educate',
                'category' => 'tips',
                'quality_score' => 85,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Ta\'limiy Kontentlar',
            ],
            [
                'title' => 'Step-by-step qo\'llanma',
                'description' => 'Bosqichma-bosqich ko\'rsatma - tutorial format.',
                'example_content' => "Qanday qilish kerak: {Mavzu} 📋\n\n📍 1-qadam: {tavsif}\n📍 2-qadam: {tavsif}\n📍 3-qadam: {tavsif}\n📍 4-qadam: {tavsif}\n\n✅ Tayyor!\n\nSavollar bo'lsa - yozing! 💬",
                'key_points' => ['Aniq qadamlar', 'Vizual bo\'lish', 'Yordam taklifi'],
                'suggested_emojis' => ['📋', '📍', '✅', '💬'],
                'content_type' => 'carousel',
                'purpose' => 'educate',
                'category' => 'educational',
                'quality_score' => 88,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Ta\'limiy Kontentlar',
            ],
        ];

        foreach ($ideas as $data) {
            $collectionName = $data['collection'] ?? null;
            unset($data['collection']);

            $idea = ContentIdea::create($data);

            if ($collectionName && isset($collections[$collectionName])) {
                $collections[$collectionName]->addIdea($idea);
            }
        }
    }

    /**
     * Seasonal ideas
     */
    protected function createSeasonalIdeas(array $collections): void
    {
        $seasonalIdeas = [
            // Yangi yil
            [
                'title' => 'Yangi yil tabrik posti',
                'description' => 'Yangi yil munosabati bilan mijozlarni tabriqlash.',
                'example_content' => "🎄 Yangi yil muborak! 🎄\n\nHurmatli mijozlarimiz!\n\n2025-yilda bizga ishonganingiz uchun rahmat!\n2026-yilda ham birga bo'lamiz!\n\nSizga:\n✨ Sog'lik\n✨ Baxt\n✨ Muvaffaqiyat\n\ntilaymiz! 🥂",
                'key_points' => ['Tabrik', 'Minnatdorchilik', 'Tilaklar'],
                'suggested_emojis' => ['🎄', '🎅', '🎁', '✨', '🥂'],
                'content_type' => 'post',
                'purpose' => 'engage',
                'category' => 'holiday',
                'is_seasonal' => true,
                'season' => 'new_year',
                'best_months' => [12, 1],
                'quality_score' => 85,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Bayramlar',
            ],
            [
                'title' => 'Yangi yil aksiyasi',
                'description' => 'Yangi yil chegirmalari va maxsus takliflar.',
                'example_content' => "🎄 YANGI YIL AKSIYASI! 🎁\n\n🔥 Barcha mahsulotlarga -30%!\n\n⏰ Muddati: 25-dekabr - 5-yanvar\n\n✅ Onlayn buyurtma\n✅ Bepul yetkazib berish\n✅ Bo'lib to'lash\n\n👆 Bio'dagi link orqali buyurtma bering!\n\n#yangiiyil #aksiya",
                'key_points' => ['Chegirma foizi', 'Muddat', 'CTA'],
                'suggested_emojis' => ['🎄', '🎁', '🔥', '⏰'],
                'content_type' => 'post',
                'purpose' => 'sell',
                'category' => 'promotion',
                'is_seasonal' => true,
                'season' => 'new_year',
                'best_months' => [12, 1],
                'quality_score' => 90,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Aksiyalar va Chegirmalar',
            ],

            // Navro'z
            [
                'title' => 'Navro\'z tabrigi',
                'description' => 'Navro\'z bayrami munosabati bilan tabrik.',
                'example_content' => "🌸 Navro'z muborak! 🌸\n\nYangi yil - yangi umidlar!\n\nBarchangizga:\n🌱 Bahor baraka keltirsin\n☀️ Quyosh nur sochsin\n🌸 Tabiat gullasin\n\nOilangiz bilan birga baxtli bo'ling! 💚",
                'key_points' => ['Bayram tabrigi', 'Bahoriy kayfiyat', 'Tilaklar'],
                'suggested_emojis' => ['🌸', '🌱', '☀️', '💚'],
                'content_type' => 'post',
                'purpose' => 'engage',
                'category' => 'holiday',
                'is_seasonal' => true,
                'season' => 'navro\'z',
                'best_months' => [3],
                'quality_score' => 85,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Bayramlar',
            ],

            // Ramazon
            [
                'title' => 'Ramazon tabrigi',
                'description' => 'Ramazon oyi boshlanishi munosabati bilan tabrik.',
                'example_content' => "🌙 Ramazon muborak! 🌙\n\nMuqaddas oy barchangizga barakali bo'lsin!\n\nIbodat qabul,\nRo'za maqbul,\nDuolar ijobat bo'lsin!\n\n🤲 Omin!",
                'key_points' => ['Diniy tabrik', 'Tilaklar', 'Hurmat'],
                'suggested_emojis' => ['🌙', '🤲', '⭐', '🕌'],
                'content_type' => 'post',
                'purpose' => 'engage',
                'category' => 'holiday',
                'is_seasonal' => true,
                'season' => 'ramadan',
                'best_months' => [3, 4], // Taxminiy - har yil o'zgaradi
                'quality_score' => 80,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Bayramlar',
            ],

            // Mustaqillik
            [
                'title' => 'Mustaqillik kuni tabrigi',
                'description' => 'O\'zbekiston Mustaqillik kuni tabrigi.',
                'example_content' => "🇺🇿 Mustaqillik kuni muborak! 🇺🇿\n\n1-sentabr - Vatanimiz eng ulug' bayrami!\n\nMustaqil O'zbekistonimiz obod bo'lsin!\nXalqimiz farovon yashashsin!\n\n💙 Vatan sog'lig'i - biz uchun g'urur!",
                'key_points' => ['Vatanparvarlik', 'Bayram tabrigi', 'Milliy g\'urur'],
                'suggested_emojis' => ['🇺🇿', '💙', '🎉', '⭐'],
                'content_type' => 'post',
                'purpose' => 'engage',
                'category' => 'holiday',
                'is_seasonal' => true,
                'season' => 'independence',
                'best_months' => [9],
                'quality_score' => 80,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Bayramlar',
            ],

            // Yoz
            [
                'title' => 'Yozgi aksiya',
                'description' => 'Yoz mavsumi uchun maxsus takliflar.',
                'example_content' => "☀️ YOZGI AKSIYA! ☀️\n\n🔥 Yoz issig'ida narxlar sovuq!\n\n-25% chegirma barcha {mahsulot}larga!\n\n⏰ Faqat {kun} kungacha!\n\n📦 Yetkazib berish bepul!\n\nBuyurtma: 👆 Bio link",
                'key_points' => ['Mavsumiy taklif', 'Chegirma', 'Muddat'],
                'suggested_emojis' => ['☀️', '🔥', '⏰', '📦'],
                'content_type' => 'post',
                'purpose' => 'sell',
                'category' => 'promotion',
                'is_seasonal' => true,
                'season' => 'summer',
                'best_months' => [6, 7, 8],
                'quality_score' => 75,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Aksiyalar va Chegirmalar',
            ],
        ];

        foreach ($seasonalIdeas as $data) {
            $collectionName = $data['collection'] ?? null;
            unset($data['collection']);

            $idea = ContentIdea::create($data);

            if ($collectionName && isset($collections[$collectionName])) {
                $collections[$collectionName]->addIdea($idea);
            }
        }
    }

    /**
     * Promotion ideas
     */
    protected function createPromotionIdeas(array $collections): void
    {
        $promotionIdeas = [
            [
                'title' => 'Flash Sale (Tezkor aksiya)',
                'description' => 'Qisqa muddatli katta chegirma - urgency yaratadi.',
                'example_content' => "⚡ FLASH SALE! ⚡\n\n🔥 Faqat 24 SOAT!\n🔥 -50% CHEGIRMA!\n\n⏰ Tugash vaqti: bugun soat 23:59\n\nShoshiling! Miqdor cheklangan! 🏃‍♂️\n\n👆 Hoziroq buyurtma bering!",
                'key_points' => ['Urgency', 'Katta chegirma', 'Vaqt limiti'],
                'suggested_emojis' => ['⚡', '🔥', '⏰', '🏃‍♂️'],
                'content_type' => 'story',
                'purpose' => 'sell',
                'category' => 'promotion',
                'quality_score' => 88,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Aksiyalar va Chegirmalar',
            ],
            [
                'title' => '1+1 aksiyasi',
                'description' => 'Ikkitasini bitta narxiga taklif.',
                'example_content' => "🎁 1+1 AKSIYA! 🎁\n\n1 ta sotib oling - 1 ta BEPUL oling!\n\n✅ {Mahsulot nomi}\n💰 Narxi: {narx} so'm (2 ta uchun!)\n\n⏰ Aksiya muddati: {sana}gacha\n\n📲 Buyurtma: DM yoki {telefon}",
                'key_points' => ['1+1 format', 'Aniq narx', 'Muddat'],
                'suggested_emojis' => ['🎁', '✅', '💰', '📲'],
                'content_type' => 'post',
                'purpose' => 'sell',
                'category' => 'promotion',
                'quality_score' => 85,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Aksiyalar va Chegirmalar',
            ],
            [
                'title' => 'Do\'st keltiring aksiyasi',
                'description' => 'Referral dasturi - do\'stni keltirsa chegirma.',
                'example_content' => "👥 DO'STINGIZNI KELTIRING! 👥\n\nSiz va do'stingiz har biringiz -20% chegirma olasiz!\n\n✅ Qanday ishlaydi:\n1️⃣ Do'stingizga aytib bering\n2️⃣ U buyurtma beradi\n3️⃣ Ikkalangiz chegirma olasiz!\n\n📲 Hoziroq ulashing!",
                'key_points' => ['Referral', 'Ikkala tomonga foyda', 'Oddiy qadamlar'],
                'suggested_emojis' => ['👥', '✅', '📲', '🎁'],
                'content_type' => 'post',
                'purpose' => 'sell',
                'category' => 'promotion',
                'quality_score' => 82,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Aksiyalar va Chegirmalar',
            ],
        ];

        foreach ($promotionIdeas as $data) {
            $collectionName = $data['collection'] ?? null;
            unset($data['collection']);

            $idea = ContentIdea::create($data);

            if ($collectionName && isset($collections[$collectionName])) {
                $collections[$collectionName]->addIdea($idea);
            }
        }
    }

    /**
     * Engagement ideas
     */
    protected function createEngagementIdeas(array $collections): void
    {
        $engagementIdeas = [
            [
                'title' => 'Emoji bilan javob bering',
                'description' => 'Oddiy emoji javob - past effort, high engagement.',
                'example_content' => "Emoji bilan javob bering! 👇\n\nHozir qanday kayfiyatdasiz?\n\n😊 - Zo'r!\n😴 - Charchagan\n🤔 - O'ylanmoqda\n😎 - Ajoyib!",
                'key_points' => ['Oddiy javob', 'Emoji tanlov', 'Tez interaksiya'],
                'suggested_emojis' => ['😊', '😴', '🤔', '😎', '👇'],
                'content_type' => 'story',
                'purpose' => 'engage',
                'category' => 'question',
                'quality_score' => 80,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Engagement Boosters',
            ],
            [
                'title' => 'Giveaway/Sovg\'a o\'yini',
                'description' => 'Sovg\'a o\'yini - eng kuchli engagement tool.',
                'example_content' => "🎁 GIVEAWAY VAQTI! 🎁\n\nSovg'a: {sovg'a nomi}!\n\n📋 Ishtirok etish uchun:\n1️⃣ Bu postga LIKE bosing ❤️\n2️⃣ 2 ta do'stingizni belgilang 👥\n3️⃣ Bizni kuzatib boring ✅\n\n🗓 G'olib: {sana} da e'lon qilinadi!\n\nOmad! 🍀",
                'key_points' => ['Aniq qoidalar', 'Sovg\'a qiymati', 'Muddat'],
                'suggested_emojis' => ['🎁', '❤️', '👥', '✅', '🍀'],
                'content_type' => 'post',
                'purpose' => 'engage',
                'category' => 'promotion',
                'quality_score' => 95,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Engagement Boosters',
            ],
            [
                'title' => 'Quiz/Viktorina',
                'description' => 'Qiziqarli savol - bilimlarni sinash.',
                'example_content' => "🧠 QUIZ VAQTI! 🧠\n\nSavol: {savol}?\n\nA) {javob 1}\nB) {javob 2}\nC) {javob 3}\nD) {javob 4}\n\nTo'g'ri javobni izohda yozing! 👇\n\nJavob ertaga e'lon qilinadi! 🎯",
                'key_points' => ['Qiziqarli savol', 'Ko\'p tanlovli', 'Javob va\'dasi'],
                'suggested_emojis' => ['🧠', '🎯', '👇', '❓'],
                'content_type' => 'post',
                'purpose' => 'engage',
                'category' => 'question',
                'quality_score' => 85,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Engagement Boosters',
            ],
        ];

        foreach ($engagementIdeas as $data) {
            $collectionName = $data['collection'] ?? null;
            unset($data['collection']);

            $idea = ContentIdea::create($data);

            if ($collectionName && isset($collections[$collectionName])) {
                $collections[$collectionName]->addIdea($idea);
            }
        }
    }

    /**
     * Educational ideas
     */
    protected function createEducationalIdeas(array $collections): void
    {
        $educationalIdeas = [
            [
                'title' => 'Myth vs Reality',
                'description' => 'Keng tarqalgan noto\'g\'ri tushunchalarni tuzatish.',
                'example_content' => "❌ MIF: {noto'g'ri fikr}\n\n✅ HAQIQAT: {to'g'ri ma'lumot}\n\nKo'pchilik bunga ishonadi, lekin...\n{qisqa tushuntirish}\n\n💾 Saqlang va do'stlaringiz bilan ulashing!",
                'key_points' => ['Mif', 'Haqiqat', 'Tushuntirish'],
                'suggested_emojis' => ['❌', '✅', '💾', '🧠'],
                'content_type' => 'carousel',
                'purpose' => 'educate',
                'category' => 'educational',
                'quality_score' => 88,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Ta\'limiy Kontentlar',
            ],
            [
                'title' => 'Bilasizmi? fakt',
                'description' => 'Qiziqarli fakt ulashish - auditoriya e\'tiborini tortadi.',
                'example_content' => "💡 BILASIZMI?\n\n{Qiziqarli fakt}\n\nBu haqda oldin eshitganmisiz? 🤔\n\n👇 Izohda yozing!\n\n#bilasizmi #fakt",
                'key_points' => ['Qiziqarli fakt', 'Savol', 'Hashtag'],
                'suggested_emojis' => ['💡', '🤔', '👇', '📚'],
                'content_type' => 'post',
                'purpose' => 'educate',
                'category' => 'educational',
                'quality_score' => 75,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Ta\'limiy Kontentlar',
            ],
            [
                'title' => 'Do\'s and Don\'ts',
                'description' => 'Nima qilish va qilmaslik kerakligi haqida.',
                'example_content' => "✅ DO'S & ❌ DON'TS\n\n{Mavzu} haqida:\n\n✅ {Qilish kerak 1}\n✅ {Qilish kerak 2}\n✅ {Qilish kerak 3}\n\n❌ {Qilmaslik kerak 1}\n❌ {Qilmaslik kerak 2}\n❌ {Qilmaslik kerak 3}\n\n💾 Saqlang!",
                'key_points' => ['Qilish kerak', 'Qilmaslik kerak', 'Vizual format'],
                'suggested_emojis' => ['✅', '❌', '💾', '📌'],
                'content_type' => 'carousel',
                'purpose' => 'educate',
                'category' => 'tips',
                'quality_score' => 85,
                'is_global' => true,
                'is_verified' => true,
                'collection' => 'Ta\'limiy Kontentlar',
            ],
        ];

        foreach ($educationalIdeas as $data) {
            $collectionName = $data['collection'] ?? null;
            unset($data['collection']);

            $idea = ContentIdea::create($data);

            if ($collectionName && isset($collections[$collectionName])) {
                $collections[$collectionName]->addIdea($idea);
            }
        }
    }
}
