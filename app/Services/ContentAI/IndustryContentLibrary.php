<?php

namespace App\Services\ContentAI;

use App\Services\KPI\BusinessCategoryMapper;

/**
 * Industry Content Library — Algoritmik kontent kutubxonasi
 *
 * Har bir soha uchun tayyor mavzular, hooklar, va kontent shablonlari.
 * 100% ichki algoritm — AI API chaqirilMAYDI.
 *
 * Bu servis ContentPlanEngine ning "suyagi" — hech qanday data bo'lmasa ham
 * professional kontent reja yaratish imkonini beradi.
 */
class IndustryContentLibrary
{
    /**
     * Soha bo'yicha mavzu shablonlarini olish
     *
     * @return array<int, array{topic: string, category: string, content_type: string, hooks: array, description_template: string, hashtag_seeds: array}>
     */
    public function getTopicsForIndustry(string $industryCode, int $limit = 20): array
    {
        $library = $this->getIndustryLibrary($industryCode);

        if (empty($library)) {
            $library = $this->getIndustryLibrary('default');
        }

        // Diversifikatsiya: turli kategoriyalardan aralash olish
        $diversified = $this->diversifyTopics($library, $limit);

        return $diversified;
    }

    /**
     * Soha uchun haftalik diversified topic set olish
     * Hafta davomida turli content_type va category bo'lishini ta'minlaydi
     */
    public function getWeeklyTopicSet(string $industryCode, int $postsPerWeek = 7): array
    {
        $library = $this->getIndustryLibrary($industryCode);

        if (empty($library)) {
            $library = $this->getIndustryLibrary('default');
        }

        // Har xil kategoriya va turdan aralash tanlash
        $categories = ['educational', 'engagement', 'behind_scenes', 'promotional', 'testimonial'];
        $contentTypes = ['reel', 'carousel', 'post', 'story'];

        $selected = [];
        $categoryIndex = 0;
        $typeIndex = 0;

        // Birinchi: har kategoriyadan kamida 1 ta
        foreach ($categories as $cat) {
            $matching = array_filter($library, fn ($t) => $t['category'] === $cat);
            if (! empty($matching)) {
                $topic = $matching[array_rand($matching)];
                $topic['content_type'] = $contentTypes[$typeIndex % count($contentTypes)];
                $selected[] = $topic;
                $typeIndex++;
            }
        }

        // Qolgan slotlarni to'ldirish
        $remaining = array_diff_key($library, $selected);
        shuffle($remaining);

        while (count($selected) < $postsPerWeek && ! empty($remaining)) {
            $topic = array_shift($remaining);
            $topic['content_type'] = $contentTypes[$typeIndex % count($contentTypes)];
            $selected[] = $topic;
            $typeIndex++;
        }

        return array_slice($selected, 0, $postsPerWeek);
    }

    /**
     * Algoritmik description yaratish (shablonlar asosida)
     */
    public function buildAlgorithmicDescription(array $topic, string $industryCode): string
    {
        $parts = [];
        $industryName = BusinessCategoryMapper::getIndustryName($industryCode);

        // Hook
        if (! empty($topic['hooks'])) {
            $parts[] = $topic['hooks'][0];
        }

        // Description template
        if (! empty($topic['description_template'])) {
            $desc = str_replace(
                ['{industry}', '{topic}'],
                [$industryName, $topic['topic']],
                $topic['description_template']
            );
            $parts[] = $desc;
        }

        // Pain text
        if (! empty($topic['pain_text'])) {
            $parts[] = "Mijoz muammosi: {$topic['pain_text']}";
        }

        // CTA
        $cta = $this->getAlgorithmicCTA($topic['category'] ?? 'educational');
        $parts[] = $cta;

        return implode("\n\n", $parts) ?: $topic['topic'];
    }

    /**
     * Algoritmik CTA tavsiyasi
     */
    private function getAlgorithmicCTA(string $category): string
    {
        $ctas = match ($category) {
            'educational' => [
                "Foydali bo'lsa, saqlang va do'stlaringizga ulashing!",
                "Qaysi maslahat eng foydali bo'ldi? Izohda yozing!",
                "Bu ma'lumotni saqlab qo'ying — kerak bo'ladi!",
            ],
            'engagement' => [
                "Siz qaysi birini tanlaysiz? Izohda yozing!",
                "Do'stingizni belgilang — u ham bilishi kerak!",
                "Fikringizni bildiring — eng yaxshi javobni hikoyamizga chiqaramiz!",
            ],
            'promotional' => [
                "Batafsil ma'lumot uchun xabar yozing yoki profildagi havolani bosing!",
                "Hoziroq bog'laning — joy cheklangan!",
                "Bu imkoniyatni qo'ldan boy bermang!",
            ],
            'behind_scenes' => [
                "Yana nimani ko'rishni xohlaysiz? Izohda yozing!",
                "Ish jarayonida yana ko'p qiziqarli narsalar bor — obuna bo'ling!",
            ],
            'testimonial' => [
                "Siz ham shunday natijaga erishmoqchimisiz? Xabar yozing!",
                "Bu natijaga sizni ham olib boramiz!",
            ],
            default => [
                "Saqlang va do'stlaringizga ulashing!",
            ],
        };

        return $ctas[array_rand($ctas)];
    }

    /**
     * Mavzularni diversifikatsiya qilish (turli kategoriyalardan aralash)
     */
    private function diversifyTopics(array $library, int $limit): array
    {
        // Kategoriya bo'yicha guruhlash
        $byCategory = [];
        foreach ($library as $topic) {
            $cat = $topic['category'] ?? 'educational';
            $byCategory[$cat][] = $topic;
        }

        // Round-robin tarzda har kategoriyadan olish
        $result = [];
        $maxRounds = ceil($limit / max(count($byCategory), 1));

        for ($round = 0; $round < $maxRounds && count($result) < $limit; $round++) {
            foreach ($byCategory as $cat => &$topics) {
                if (count($result) >= $limit) {
                    break;
                }
                if (! empty($topics)) {
                    $result[] = array_shift($topics);
                }
            }
            unset($topics);
        }

        return $result;
    }

    /**
     * Soha kutubxonasini olish
     */
    private function getIndustryLibrary(string $industryCode): array
    {
        return match ($industryCode) {
            'beauty' => $this->getBeautyTopics(),
            'restaurant' => $this->getRestaurantTopics(),
            'ecommerce' => $this->getEcommerceTopics(),
            'retail' => $this->getRetailTopics(),
            'service' => $this->getServiceTopics(),
            'saas' => $this->getSaasTopics(),
            'fitness' => $this->getFitnessTopics(),
            'education' => $this->getEducationTopics(),
            default => $this->getDefaultTopics(),
        };
    }

    // ================================================================
    // BEAUTY SALON TOPICS
    // DotCom Secrets + Professional ssenariylar asosida
    // ================================================================
    private function getBeautyTopics(): array
    {
        return [
            // --- PROMOTIONAL: Sotish va taklif ---
            [
                'topic' => "Maxsus taklif — birinchi tashrifda chegirma",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "Sochingizdan norozimisiz? Birinchi tashrif 50% chegirma!",
                    "Bu narxlarda endi bo'lmaydi — faqat shu hafta!",
                    "Yangi mijozlarga MAXSUS TAKLIF — o'tkazib yubormang!",
                ],
                'description_template' => "Star/Story/Solution: Muammo (sochdan norozilik) → Yechim (professional xizmat) → Taklif (chegirma) → Urgency (muddat) → CTA.",
                'hashtag_seeds' => ['aksiya', 'chegirma', 'salon', 'beauty', 'tashkent'],
                'pain_text' => "Sochidan norozi, lekin salonga borish qimmat deb o'ylaydi",
            ],
            [
                'topic' => "Yangi xizmat yoki protsedura e'loni",
                'category' => 'promotional',
                'content_type' => 'reel',
                'hooks' => [
                    "YANGILIK! Bu protsedurani Toshkentda faqat biz qilamiz",
                    "Siz kutgan xizmat — endi bizda!",
                    "Birinchi 20 mijozga maxsus narx — band qiling!",
                ],
                'description_template' => "Yangi xizmat e'loni. Hook → Nima yangi → Kim uchun → Natija → Scarcity (cheklangan joy) → CTA.",
                'hashtag_seeds' => ['yangilik', 'newservice', 'beauty', 'salon', 'protsedura'],
                'pain_text' => "Zamonaviy protsedurani sinab ko'rmoqchi, lekin qaerga borishini bilmaydi",
            ],
            // --- TESTIMONIAL: Mijoz natijalari ---
            [
                'topic' => "Mijoz transformatsiyasi — oldin va keyin",
                'category' => 'testimonial',
                'content_type' => 'carousel',
                'hooks' => [
                    "Bu o'zgarishga ishonmaysiz! OLDIN va KEYIN",
                    "'Menga hech narsa yordam bermaydi' degan edi. Natijani ko'ring!",
                    "3 soatda butunlay yangi qiyofa — mijozimiz gapiradi",
                ],
                'description_template' => "Epiphany Bridge: Mijozning oldingi holati → muammosi → bizga kelishi → transformatsiya natijasi. Raqamlar va emotsiya bilan.",
                'hashtag_seeds' => ['transformation', 'beforeafter', 'beauty', 'salon', 'natija'],
                'pain_text' => "O'zini chiroyli his qilmaydi, o'zgarish mumkinligiga ishonmaydi",
            ],
            [
                'topic' => "Mijoz fikri — haqiqiy sharh va natija",
                'category' => 'testimonial',
                'content_type' => 'post',
                'hooks' => [
                    "Mijozimiz nima deydi? Filtrsiz, haqiqiy fikr",
                    "'Endi faqat shu salonga boraman' — mijozimiz so'zlari",
                    "Bu sharh bizni juda xursand qildi — o'qing!",
                ],
                'description_template' => "Mijoz testimoniali. Third-person story: kim edi → muammosi → bizga keldi → hozir qanday. Social proof sifatida.",
                'hashtag_seeds' => ['review', 'sharh', 'mijoz', 'salon', 'ishonch'],
                'pain_text' => "Yaxshi salonga ishonmaydi, oldin yomon tajriba bo'lgan",
            ],
            // --- EDUCATIONAL: Foydali ma'lumot ---
            [
                'topic' => "5 ta soch parvarishi xatosi — 90% ayollar qiladi",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => [
                    "90% ayollar shu xatolarni qiladi — sochini buzadi!",
                    "Sochingiz to'kilayaptimi? 5 ta sababni aytamiz",
                    "Bu xatolarni to'xtatsangiz, sochingiz 2 barobar yaxshilanadi",
                ],
                'description_template' => "Problem → Agitation → Solution: Keng tarqalgan xatolar → oqibatlari → to'g'ri usul. Ekspert maslahati sifatida ishonch qozonish.",
                'hashtag_seeds' => ['sochparvarishi', 'haircare', 'beautytips', 'xatolar', 'maslahat'],
                'pain_text' => "Sochi to'kiladi/quruq/yog'li — nima qilishini bilmaydi",
            ],
            [
                'topic' => "Uy sharoitida teri parvarishi — 5 daqiqalik dastur",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => [
                    "Uyda salon natijasiga erishing — faqat 5 daqiqa!",
                    "Bu oddiy usulni bilganingizda, teri muammosi hal bo'ladi",
                    "Dermatolog maslahatni hech kim bepul bermaydi — biz beramiz!",
                ],
                'description_template' => "Bepul amaliy maslahat. Value berish → Ekspert ishonchi → Saqlash/share uchun format. Big Domino: oddiy parvarish katta natija beradi.",
                'hashtag_seeds' => ['skincare', 'teriparvarishi', 'homecare', 'bepul', 'maslahat'],
                'pain_text' => "Teri muammolari bor, lekin salonga borish qimmat",
            ],
            [
                'topic' => "Trend uslublar — bu yilning TOP trendlari",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => [
                    "2026 yilning eng TOP 5 soch trendi — 3-chisi sizni hayratga soladi",
                    "Bu uslubni hali sinab ko'rmaganmisiz? Vaqti keldi!",
                    "Master maslahat beradi: bu yil nima moda?",
                ],
                'description_template' => "Trend va zamonaviy uslublar. Har bir slaydda boshqa trend + qanday qilish mumkin. Oxirida CTA: 'Band qilish uchun yozing'.",
                'hashtag_seeds' => ['trend2026', 'hairstyle', 'fashion', 'beauty', 'style'],
                'pain_text' => "Zamonaviy ko'rinmoqchi, lekin nima trenddayin bilmaydi",
            ],
            // --- ENGAGEMENT: Faollik oshirish ---
            [
                'topic' => "Qaysi uslubni tanlaysiz? A yoki B",
                'category' => 'engagement',
                'content_type' => 'story',
                'hooks' => [
                    "Qaysi birini tanlaysiz? Izohda javob bering!",
                    "ENG QIYIN TANLOV — A yoki B?",
                    "Siz qaysi rangni tanlardingiz? 1 yoki 2?",
                ],
                'description_template' => "Poll/tanlov formati. 2 ta vizual variant — muhokama ochish. Engagement oshirish, algoritmda ko'tarilish.",
                'hashtag_seeds' => ['poll', 'tanlov', 'beauty', 'style', 'fikringiz'],
                'pain_text' => "O'ziga mos uslubni tanlashda ikkilanadi",
            ],
            [
                'topic' => "Savol-javob: mutaxassis javob beradi",
                'category' => 'engagement',
                'content_type' => 'post',
                'hooks' => [
                    "— Sochim to'kilayapti, nima qilsam?\n— Mutaxassis javob beradi...",
                    "Shu savolni ko'p berishadi. Haqiqiy javobni aytamiz",
                    "Sizda ham shu muammo bormi? Izohda yozing — javob beramiz!",
                ],
                'description_template' => "Dialog formati. Mijozning real savoli → mutaxassis javobi. O'quvchini izoh yozishga undash.",
                'hashtag_seeds' => ['faq', 'savol', 'beautytips', 'mutaxassis', 'maslahat'],
                'pain_text' => "Savollariga javob topmaydi, kimdan so'rashini bilmaydi",
            ],
            // --- BEHIND SCENES: Ichki jarayon ---
            [
                'topic' => "Master qanday ishlaydi — sahna ortida",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => [
                    "Hech kim ko'rmagan jarayonni ko'rsatamiz — master qo'li sehrmi?",
                    "30 sekundda masterimiz ishlash jarayoni — hayron qolasiz!",
                    "Bu video salon haqida hamma narsani aytadi",
                ],
                'description_template' => "Ish jarayonini real ko'rsatish. Ishonch qozonish — shaffoflik va professionallik. Attractive Character: master — o'z sohasining ustasi.",
                'hashtag_seeds' => ['behindthescenes', 'salonlife', 'hairstylist', 'process', 'master'],
                'pain_text' => "Salon sifatiga ishonmaydi, master malakasidan xavotir",
            ],
            [
                'topic' => "Jamoamiz bilan tanishing — kim nima qiladi",
                'category' => 'behind_scenes',
                'content_type' => 'carousel',
                'hooks' => [
                    "Sizni kutayotgan masterlar — har biri o'z sohasining professionali",
                    "Bu jamoaga ishonasiz — tajriba va natija kafolati",
                    "Masterimiz ... yillik tajriba, ... ta mamnun mijoz",
                ],
                'description_template' => "Jamoa tanishish. Attractive Character (DotCom Secrets): har bir masterning backstory, tajribasi, natijasi. Ishonch va bog'lanish.",
                'hashtag_seeds' => ['team', 'professionals', 'salon', 'master', 'meettheteam'],
                'pain_text' => "Kim xizmat ko'rsatishini bilmaydi, masterga ishonch yo'q",
            ],
            // --- AD: Target reklama ---
            [
                'topic' => "Target reklama — salonga yangi mijoz jalb qilish",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "Sochingiz o'zingizga yoqmayaptimi? Biz bilan 1 tashrifda o'zgarasiz!",
                    "Sizga yoqadigan soch — bu qimmat emas. Isbotlaymiz!",
                    "Dugonangiz 'Qaerga bordingiz?' deb so'raydigan natija!",
                ],
                'description_template' => "Star/Story/Solution reklama: Muammo (ko'rinishdan norozilik) → Hikoya (mijoz tajribasi) → Yechim (bizning salon) → Dalil (raqamlar) → Taklif → CTA.",
                'hashtag_seeds' => ['salon', 'beauty', 'soch', 'tashkent', 'master'],
                'pain_text' => "O'zini chiroyli his qilmaydi, yaxshi master topa olmaydi",
            ],
            [
                'topic' => "To'g'ri vosita tanlash — master maslahati",
                'category' => 'educational',
                'content_type' => 'story',
                'hooks' => [
                    "Ko'pchilik noto'g'ri vosita ishlatadi — sochini buzadi!",
                    "Sizning soch tipingizga qaysi shampun mos? Master javob beradi",
                    "Bu xatoni to'xtatsangiz, sochingiz 2 hafta ichida o'zgaradi",
                ],
                'description_template' => "Ekspert maslahati. Problem → noto'g'ri yondashuv oqibati → to'g'ri tanlash qoidalari. CTA: 'Saqlang!'.",
                'hashtag_seeds' => ['beautyproducts', 'haircare', 'maslahat', 'tanlash', 'shampun'],
                'pain_text' => "Qaysi vositani ishlatishini bilmaydi, ko'p pul sarflaydi",
            ],
        ];
    }

    // ================================================================
    // RESTAURANT / FOOD TOPICS
    // DotCom Secrets + Professional ssenariylar asosida
    // ================================================================
    private function getRestaurantTopics(): array
    {
        return [
            // --- PROMOTIONAL ---
            [
                'topic' => "Maxsus taklif — oilaviy tushlik/kechki ovqat",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "Dam olish kuni oilangiz bilan MAZALI o'tkazmoqchimisiz?",
                    "Bolalar uchun bepul menyu + kattalar uchun chegirma — faqat shu hafta!",
                    "Oilaviy dam olish uchun eng yaxshi joy — isbotlaymiz!",
                ],
                'description_template' => "Hook → Taklif (oilaviy set) → Foyda (bolalar bepul, qulay muhit) → Urgency → CTA. Value stack bilan.",
                'hashtag_seeds' => ['oila', 'restoran', 'tashkent', 'familydining', 'weekend'],
                'pain_text' => "Dam olish kunida oila bilan qaerga borish muammo",
            ],
            [
                'topic' => "Yangi menyu e'loni — mavsumiy taomlar",
                'category' => 'promotional',
                'content_type' => 'reel',
                'hooks' => [
                    "YANGI MENYU keldi! Birinchi 50 kishiga degustatsiya BEPUL",
                    "Bu taomni hali Toshkentda hech kim tayyorlamagan — biz birinchimiz!",
                    "Yangi ta'mlar — ko'zingiz bilan yeb qo'yasiz!",
                ],
                'description_template' => "E'lon formulasi: Yangilik → Nima maxsus → Kim uchun → Scarcity (birinchi N kishiga) → CTA.",
                'hashtag_seeds' => ['newmenu', 'yangilik', 'restoran', 'food', 'tashkent'],
                'pain_text' => "Har doim bir xil taom — yangilik izlaydi",
            ],
            // --- TESTIMONIAL ---
            [
                'topic' => "Mijoz fikri — haqiqiy sharh va tajriba",
                'category' => 'testimonial',
                'content_type' => 'post',
                'hooks' => [
                    "'Bu yerda ovqatlanganingdan keyin boshqa joyga borging kelmaydi'",
                    "Mijozimiz Google da 5 yulduz qo'ydi — nima uchunligini o'qing",
                    "Bu sharh bizni juda xursand qildi — haqiqiy, filtrsiz",
                ],
                'description_template' => "Third-person testimonial: Mijoz kim → qaerdan keldi → nima yoqdi → yana kelishini aytdi. Social proof.",
                'hashtag_seeds' => ['review', 'sharh', 'mijoz', 'food', 'restoran'],
                'pain_text' => "Restoran tanlashda yomon tajriba qilishdan qo'rqadi",
            ],
            [
                'topic' => "Doimiy mijoz hikoyasi — nima uchun doim qaytib keladi",
                'category' => 'testimonial',
                'content_type' => 'carousel',
                'hooks' => [
                    "Bu mijoz 3 yildan beri har hafta keladi — sababi nimada?",
                    "Bir marta kelgan — endi doimiy mijozimiz. Hikoyasini eshiting",
                    "'Men boshqa restoranlarni sinab ko'rdim — lekin shu yerga qaytaman'",
                ],
                'description_template' => "Epiphany Bridge: Mijozning boshqa restoranlarni sinab, bizga kelishi → aha-moment → sadoqat sababi. Loyalty story.",
                'hashtag_seeds' => ['doimiymijoz', 'loyalty', 'restoran', 'food', 'ishonch'],
                'pain_text' => "Yaxshi restoran topish qiyin — har safar yangi joy sinash kerak",
            ],
            // --- EDUCATIONAL ---
            [
                'topic' => "Oshpaz sirlari — uyda professional taom tayyorlash",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => [
                    "Oshxona ichidagi sirni ochamiz — buni bilsangiz, uyda ham qilasiz!",
                    "Bu oddiy sir — taomni 10 barobar mazali qiladi",
                    "Professional oshpazlar shu usulni ishlatadi — siz ham qo'llang!",
                ],
                'description_template' => "Bepul qiymat: Professional sir → oddiy usul → natija. Ekspert ishonchini qozonish. CTA: Saqlang!",
                'hashtag_seeds' => ['oshpaz', 'retsept', 'recipe', 'cookingtips', 'sir'],
                'pain_text' => "Uyda mazali tayyorlolmaydi, sir nimada bilmaydi",
            ],
            [
                'topic' => "Taom sifati — biz qanday mahsulot ishlatamiz",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => [
                    "Biz ishlatiladigan mahsulotlarni ko'ring — sifatga kafolatimiz!",
                    "Hamma restoran bir xil emas — farq mahsulot sifatida",
                    "Halol va toza — bu shunchaki so'z emas, bu bizning qoidamiz",
                ],
                'description_template' => "Shaffoflik va ishonch. Big Domino: 'Sifatli mahsulot = mazali taom'. Mahsulot tanlash jarayonini ko'rsatish.",
                'hashtag_seeds' => ['sifat', 'halol', 'freshfood', 'quality', 'restoran'],
                'pain_text' => "Restoranda nima ishlatilganini bilmaydi, sifatdan xavotir",
            ],
            [
                'topic' => "Uyda retsept — oddiy va mazali",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => [
                    "3 ta ingredient — ajoyib ta'm! Uyda sinab ko'ring",
                    "Bu retseptni hech kim o'rgatmaydi — biz bepul beramiz!",
                    "10 daqiqada professional darajadagi taom — qo'llanma",
                ],
                'description_template' => "Bepul retsept — saqlash va share uchun format. Value berish → Ishonch qozonish → 'Bizda bundan ham mazali' CTA.",
                'hashtag_seeds' => ['retsept', 'recipe', 'homecooking', 'easyrecipe', 'mazali'],
                'pain_text' => "Yangi taom o'rganmoqchi, lekin qiyin deb o'ylaydi",
            ],
            // --- ENGAGEMENT ---
            [
                'topic' => "Qaysi taomni tanlaysiz? A yoki B",
                'category' => 'engagement',
                'content_type' => 'story',
                'hooks' => [
                    "ENG QIYIN TANLOV — qaysi taomni tanlaysiz? Izohda yozing!",
                    "Lag'mon yoki shashlik? Siz qaysi tarafdamiz?",
                    "Bu ikki taomdan birini tanlashingiz KERAK — qaysi?",
                ],
                'description_template' => "Poll/tanlov. Engagement oshirish — izohlar va sharelar ko'payadi. Kuchli vizual bilan.",
                'hashtag_seeds' => ['poll', 'tanlov', 'food', 'mazali', 'fikringiz'],
                'pain_text' => "Nima buyurtma qilishni bilmaydi — tanlash qiyin",
            ],
            [
                'topic' => "Buyurtma berish — 3 oddiy qadam",
                'category' => 'educational',
                'content_type' => 'post',
                'hooks' => [
                    "Uyingizga mazali taom yetkazamiz — 3 bosqichda buyurtma bering!",
                    "Bu qadar oson: Yozing → Tanlang → Oling! Tamom",
                    "30 daqiqada eshikda — hoziroq buyurtma bering!",
                ],
                'description_template' => "Buyurtma jarayoni → oddiylik ta'kidlash → tezlik → CTA. Barcha to'siqlarni olib tashlash.",
                'hashtag_seeds' => ['delivery', 'buyurtma', 'yetkazibberish', 'tez', 'qulay'],
                'pain_text' => "Buyurtma qilish murakkab deb o'ylaydi",
            ],
            // --- BEHIND SCENES ---
            [
                'topic' => "Oshxona ichidan — taom tayyorlash jarayoni",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => [
                    "Oshxonamiz ichidan — ko'rganingizda och qolasiz!",
                    "Bu taomni 30 sekundda tayyorlash jarayonini ko'ring",
                    "Oshpazimizning qo'li — bu sehrgarlik!",
                ],
                'description_template' => "Osh jarayonini real ko'rsatish. Shaffoflik → ishonch. Vizual joziba — odam ovqat ko'rsa, ishtahasi ochiladi.",
                'hashtag_seeds' => ['behindthescenes', 'kitchen', 'cooking', 'oshpaz', 'restoran'],
                'pain_text' => "Restoranda nima bo'layotganini bilmaydi, ochiqlikni xohlaydi",
            ],
            [
                'topic' => "Restoran muhiti — bu yerda vaqt o'tkazishni sevib qolasiz",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => [
                    "Bu muhitni ko'ring — mazali taom + qulay joy = mukammal dam olish",
                    "Restoranimiz ichidan tour — virtual sayohat",
                    "Dam olish uchun eng yaxshi joy — isbotlaymiz!",
                ],
                'description_template' => "Muhit va atmosfera. Vizual storytelling — joy chiroyli, qulay, oilaviy. CTA: 'Band qiling!'.",
                'hashtag_seeds' => ['atmosphere', 'interior', 'restoran', 'cozy', 'tashkent'],
                'pain_text' => "Qulay va chiroyli joy izlaydi, lekin tanlashda qiynaladi",
            ],
        ];
    }

    // ================================================================
    // E-COMMERCE / ONLINE SAVDO TOPICS
    // DotCom Secrets + Professional ssenariylar asosida
    // ================================================================
    private function getEcommerceTopics(): array
    {
        return [
            // --- PROMOTIONAL: Sotish va taklif ---
            [
                'topic' => "Maxsus taklif — chegirma va bonus",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "FAQAT BUGUN! Bu narxda endi bo'lmaydi — hoziroq xarid qiling!",
                    "Chegirma + BEPUL yetkazib berish — faqat shu hafta oxirigacha!",
                    "Hali bu chegirmani ko'rmaganmisiz? O'tkazib yubormang!",
                ],
                'description_template' => "Star/Story/Solution: Muammo (kerakli mahsulot qimmat) → Yechim (maxsus taklif) → Value Stack (chegirma + bonus + bepul yetkazish) → Urgency (muddat) → CTA.",
                'hashtag_seeds' => ['aksiya', 'chegirma', 'onlinesavdo', 'maxsustaklif', 'xarid'],
                'pain_text' => "Kerakli mahsulotni olmoqchi, lekin narxi to'xtatyapti",
            ],
            [
                'topic' => "Yangi mahsulot e'loni — birinchilar orasida bo'ling",
                'category' => 'promotional',
                'content_type' => 'reel',
                'hooks' => [
                    "YANGILIK! Bu mahsulotni hamma kutgan — endi bizda!",
                    "Birinchi 50 xaridor uchun MAXSUS narx — band qiling!",
                    "Bu mahsulot hali bozorda kam — tez buyurtma bering!",
                ],
                'description_template' => "Yangi mahsulot e'loni. Hook → Nima yangi → Kim uchun → Scarcity (cheklangan miqdor) → Urgency (birinchi N kishiga maxsus narx) → CTA.",
                'hashtag_seeds' => ['yangilik', 'newproduct', 'onlinesavdo', 'xarid', 'trend'],
                'pain_text' => "Yangi va sifatli mahsulot izlaydi, lekin qaerdan topishni bilmaydi",
            ],
            [
                'topic' => "Flash sale — tezkor chegirma",
                'category' => 'promotional',
                'content_type' => 'story',
                'hooks' => [
                    "FLASH SALE! Faqat 3 soat qoldi — 70% gacha chegirma!",
                    "Bu chegirmani faqat bugun ko'rgan odamlar oladi — tez bo'ling!",
                    "Soat ishlayapti — eng yaxshi narxlar yo'qolmoqda!",
                ],
                'description_template' => "Flash sale urgency formulasi. Scarcity (vaqt cheklangan) + katta chegirma + timer + CTA. DotCom Secrets: Urgency va scarcity kombinatsiyasi.",
                'hashtag_seeds' => ['flashsale', 'chegirma', 'tezkor', 'lastchance', 'sale'],
                'pain_text' => "Yaxshi narx kutadi, lekin qachon bo'lishini bilmaydi",
            ],
            // --- TESTIMONIAL: Mijoz natijalari ---
            [
                'topic' => "Mijoz sharhi va unboxing — haqiqiy tajriba",
                'category' => 'testimonial',
                'content_type' => 'reel',
                'hooks' => [
                    "Mijozimiz buyurtmani ochdi — reaktsiyasini ko'ring!",
                    "'Kutganimdan ham yaxshi ekan!' — mijozimiz so'zlari",
                    "Haqiqiy sharh, filtrsiz — mijozimiz nima deydi?",
                ],
                'description_template' => "Epiphany Bridge: Mijoz shubhalangan edi → buyurtma berdi → qutini ochdi → hayratga tushdi. Real video/foto + mijoz so'zlari. Social proof kuchi.",
                'hashtag_seeds' => ['unboxing', 'review', 'sharh', 'mijoz', 'haqiqiy'],
                'pain_text' => "Onlayn xaridda aldanishdan qo'rqadi, real ko'rmasdan ishonmaydi",
            ],
            [
                'topic' => "Oldin-keyin natija — mahsulot ta'siri",
                'category' => 'testimonial',
                'content_type' => 'carousel',
                'hooks' => [
                    "OLDIN va KEYIN — bu mahsulot haqiqatan ishlaydi!",
                    "Mijozimiz 2 hafta ishlatdi — natijani o'zi ko'rsatdi",
                    "'Ishonmagan edim, lekin natija o'zi gapiradi' — real sharh",
                ],
                'description_template' => "Oldin/keyin format. Mijozning mahsulotdan oldingi holati → ishlatish jarayoni → natija. Raqamlar va vizual dalillar bilan. Third-person story.",
                'hashtag_seeds' => ['beforeafter', 'natija', 'transformation', 'review', 'real'],
                'pain_text' => "Mahsulot haqiqatan ishlashiga ishonmaydi, dalil ko'rmoqchi",
            ],
            // --- EDUCATIONAL: Foydali ma'lumot ---
            [
                'topic' => "Mahsulot tanlash qo'llanmasi — to'g'ri xarid qilish sirlari",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => [
                    "Xarid qilishdan OLDIN shu 5 narsani tekshiring — pul yo'qotmaysiz!",
                    "90% xaridorlar shu xatoni qiladi — qimmatga tushadi!",
                    "Professional maslahat: mahsulot tanlashning 5 ta oltin qoidasi",
                ],
                'description_template' => "Problem → Agitation → Solution: Noto'g'ri tanlash oqibatlari → pul yo'qotish → to'g'ri tanlash qoidalari. Ekspert maslahat sifatida ishonch qozonish.",
                'hashtag_seeds' => ['maslahat', 'tanlash', 'qollanma', 'onlinesavdo', 'xaridqilish'],
                'pain_text' => "Noto'g'ri mahsulot olib, pul yo'qotishdan qo'rqadi",
            ],
            [
                'topic' => "Onlayn xarid qilishda 5 ta keng tarqalgan xato",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => [
                    "Bu 5 xatoni qilsangiz, pulingiz bekor ketadi — to'xtating!",
                    "Onlayn xaridda HECH QACHON qilmang bu narsalarni!",
                    "Ko'pchilik aldanadi — lekin biz to'g'ri yo'lni ko'rsatamiz",
                ],
                'description_template' => "Problem → Agitation → Solution: Xato qilish → pul yo'qotish/aldanish → to'g'ri usullar. Har bir xato uchun aniq yechim. Saqlash va share uchun format.",
                'hashtag_seeds' => ['xatolar', 'onlinexarid', 'maslahat', 'ehtiyot', 'savdo'],
                'pain_text' => "Onlayn xaridda aldanganlar ko'p — qanday ehtiyot bo'lishni bilmaydi",
            ],
            [
                'topic' => "Mahsulot taqqoslash — qaysi birini tanlash kerak?",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => [
                    "A yoki B? Batafsil taqqoslash — eng yaxshisini tanlaymiz!",
                    "Bu 2 ta mashhur mahsulot — farqi nimada? Ekspert javob beradi",
                    "Noto'g'ri tanlasangiz, pul bekor ketadi — taqqoslashni ko'ring!",
                ],
                'description_template' => "Taqqoslash formati: Mahsulot A vs B → narx, sifat, xususiyatlar → kim uchun qaysi biri mos → aniq tavsiya. Big Domino: to'g'ri tanlash = mamnunlik.",
                'hashtag_seeds' => ['taqqoslash', 'comparison', 'tanlash', 'review', 'mahsulot'],
                'pain_text' => "Bir nechta variant orasida tanlay olmayapti, farqini bilmaydi",
            ],
            // --- ENGAGEMENT: Faollik oshirish ---
            [
                'topic' => "Qaysi mahsulotni tanlaysiz? A yoki B",
                'category' => 'engagement',
                'content_type' => 'story',
                'hooks' => [
                    "ENG QIYIN TANLOV — A yoki B? Izohda javob bering!",
                    "Siz qaysi birini tanlagan bo'lardingiz? 1 yoki 2?",
                    "Do'stingizni belgilang — u qaysi birini tanlaydi?",
                ],
                'description_template' => "Poll/tanlov formati. 2 ta vizual variant — muhokama ochish. Engagement oshirish, algoritmda ko'tarilish. Kuchli vizual bilan.",
                'hashtag_seeds' => ['poll', 'tanlov', 'thisorthat', 'tanlang', 'fikringiz'],
                'pain_text' => "Tanlashda ikkilanadi, boshqalar fikri qiziqtiradi",
            ],
            [
                'topic' => "Wishlist savol — eng ko'p xohlagan mahsulotingiz?",
                'category' => 'engagement',
                'content_type' => 'post',
                'hooks' => [
                    "Agar cheksiz pul bo'lsa, qaysi mahsulotni olardingiz? Izohda yozing!",
                    "Wishlist'ingizda nima bor? Eng yaxshi javobga SOVG'A!",
                    "Bu ro'yxatdan qaysi birini olmoqchisiz? Raqamini yozing!",
                ],
                'description_template' => "Wishlist engagement formati. Xohlagan mahsulotni so'rash → izohlar ko'paytirish → sovg'a va'dasi → qayta targeting uchun ma'lumot yig'ish.",
                'hashtag_seeds' => ['wishlist', 'xohish', 'sovga', 'engagement', 'tanlov'],
                'pain_text' => "Ko'p mahsulot yoqadi, lekin hammasini olib bo'lmaydi",
            ],
            // --- BEHIND SCENES: Ichki jarayon ---
            [
                'topic' => "Qadoqlash jarayoni — har bir buyurtma bilan g'amxo'rlik",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => [
                    "Sizning buyurtmangizni qanday tayyorlaymiz — ichkaridan ko'ring!",
                    "Har bir buyurtma SEVGI bilan qadoqlanadi — jarayonni ko'ring",
                    "Bu videoni ko'rganingizdan keyin bizdan xarid qilgingiz keladi!",
                ],
                'description_template' => "Qadoqlash jarayonini real ko'rsatish. Shaffoflik → ishonch. Attractive Character: g'amxo'r jamoa. Sifat va e'tiborni ta'kidlash.",
                'hashtag_seeds' => ['packing', 'behindthescenes', 'qadoqlash', 'sifat', 'buyurtma'],
                'pain_text' => "Onlayn buyurtmada mahsulot buzilmasdan yetib kelishiga ishonmaydi",
            ],
            [
                'topic' => "Ombor ichidan — mahsulotlar qanday saqlanadi",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => [
                    "Omborimiz ichini birinchi marta ko'rsatamiz — hayron qolasiz!",
                    "Minglab mahsulot — tartib va sifat nazorati. Ichkaridan ko'ring!",
                    "Siz buyurtma qilgan mahsulot qanday joyda saqlanadi?",
                ],
                'description_template' => "Ombor tour — sifat nazorati va saqlash sharoitlarini ko'rsatish. Shaffoflik va professionallik. Ishonch qozonish — ochiqlik bilan.",
                'hashtag_seeds' => ['warehouse', 'ombor', 'behindthescenes', 'sifat', 'logistika'],
                'pain_text' => "Mahsulot sifati va saqlash sharoitidan xavotir",
            ],
            // --- AD: Target reklama ---
            [
                'topic' => "Target reklama — onlayn do'konga yangi mijoz jalb qilish",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "Siz izlagan mahsulot — eng yaxshi narxda! Hoziroq ko'ring",
                    "Hali bu mahsulotni ko'rmaganmisiz? Minglab kishi allaqachon oldi!",
                    "Birinchi xaridingizga 20% CHEGIRMA + BEPUL yetkazish!",
                ],
                'description_template' => "Star/Story/Solution reklama: Muammo (kerakli narsa topilmayapti) → Hikoya (minglab mamnun xaridor) → Yechim (bizning do'kon) → Value Stack (chegirma + bonus) → Urgency → CTA.",
                'hashtag_seeds' => ['onlinesavdo', 'xarid', 'chegirma', 'mahsulot', 'tezkor'],
                'pain_text' => "Kerakli mahsulotni sifatli va arzon topmoqchi, ishonchli do'kon izlaydi",
            ],
        ];
    }

    // ================================================================
    // RETAIL / DO'KON TOPICS
    // DotCom Secrets + Professional ssenariylar asosida
    // ================================================================
    private function getRetailTopics(): array
    {
        return [
            // --- PROMOTIONAL: Sotish va taklif ---
            [
                'topic' => "Haftalik aksiya — maxsus narxlar",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "HAFTALIK AKSIYA boshlandi! Bu narxlarda endi bo'lmaydi!",
                    "Faqat shu hafta — eng yaxshi mahsulotlarda katta chegirma!",
                    "Har hafta yangi aksiya — bugungi taklifni ko'ring!",
                ],
                'description_template' => "Star/Story/Solution: Muammo (narx qimmat) → Yechim (haftalik aksiya) → Value Stack (chegirma + bonus) → Urgency (faqat shu hafta) → Scarcity (miqdor cheklangan) → CTA.",
                'hashtag_seeds' => ['aksiya', 'chegirma', 'dokon', 'haftalik', 'maxsustaklif'],
                'pain_text' => "Yaxshi mahsulotni olmoqchi, lekin narxi to'xtatyapti",
            ],
            [
                'topic' => "Yangi yuk keldi — birinchi bo'lib ko'ring",
                'category' => 'promotional',
                'content_type' => 'reel',
                'hooks' => [
                    "YANGI YUK KELDI! Eng mashhur mahsulotlar qaytdi — tez bo'ling!",
                    "Bu mahsulotlar tez tugaydi — birinchilar orasida bo'ling!",
                    "Mijozlar kutgan mahsulotlar — endi do'konimizda!",
                ],
                'description_template' => "Yangi yuk e'loni. Hook → Nima keldi → Kim uchun → Scarcity (tez tugaydi) → CTA (do'konga keling yoki buyurtma bering). Urgency bilan.",
                'hashtag_seeds' => ['yangiyuk', 'newarrival', 'dokon', 'mahsulot', 'yangilik'],
                'pain_text' => "Kerakli mahsulot hamma joyda tugagan, qaerdan topishni bilmaydi",
            ],
            [
                'topic' => "Loyalty dasturi — doimiy mijozlarga maxsus imtiyozlar",
                'category' => 'promotional',
                'content_type' => 'carousel',
                'hooks' => [
                    "Doimiy mijozlarimizga MAXSUS BONUS — siz ham qo'shiling!",
                    "Har bir xaridda ball yig'ing — BEPUL mahsulot oling!",
                    "Nima uchun 1000+ mijoz bizdan qayta-qayta xarid qiladi?",
                ],
                'description_template' => "Loyalty dasturi tushuntirish. Value Ladder (DotCom Secrets): kichik xarid → ball yig'ish → bonus → doimiy mijoz → maxsus imtiyozlar. Retention strategiya.",
                'hashtag_seeds' => ['loyalty', 'bonus', 'doimiymijoz', 'imtiyoz', 'dokon'],
                'pain_text' => "Bir joydan doimiy xarid qilgisi keladi, lekin hech qanday foyda yo'q",
            ],
            // --- TESTIMONIAL: Mijoz natijalari ---
            [
                'topic' => "Mijoz xaridi va fikri — haqiqiy sharh",
                'category' => 'testimonial',
                'content_type' => 'post',
                'hooks' => [
                    "Mijozimiz nima deydi? Filtrsiz, haqiqiy fikr — o'qing!",
                    "'Endi faqat shu do'kondan olaman' — mijozimiz so'zlari",
                    "Bu sharhni o'qib, siz ham ishonasiz — real tajriba!",
                ],
                'description_template' => "Mijoz testimoniali. Third-person story: kim edi → nimaga kerak edi → bizdan oldi → natija qanday. Social proof sifatida ishonch qozonish.",
                'hashtag_seeds' => ['review', 'sharh', 'mijoz', 'ishonch', 'haqiqiy'],
                'pain_text' => "Do'kon sifatiga ishonmaydi, oldin yomon tajriba bo'lgan",
            ],
            [
                'topic' => "Doimiy mijoz hikoyasi — nima uchun doim qaytib keladi",
                'category' => 'testimonial',
                'content_type' => 'carousel',
                'hooks' => [
                    "Bu mijoz 2 yildan beri har oy keladi — sababi nimada?",
                    "Bir marta kelgan — endi doimiy mijozimiz. Hikoyasini eshiting",
                    "'Boshqa do'konlarni sinab ko'rdim — lekin shu yerga qaytaman'",
                ],
                'description_template' => "Epiphany Bridge: Mijozning boshqa do'konlarni sinab, bizga kelishi → aha-moment → nima uchun doim qaytadi. Loyalty story — emotsional bog'lanish.",
                'hashtag_seeds' => ['doimiymijoz', 'loyalty', 'hikoya', 'dokon', 'ishonch'],
                'pain_text' => "Sifatli va ishonchli do'kon topish qiyin — har safar yangi joy sinash kerak",
            ],
            // --- EDUCATIONAL: Foydali ma'lumot ---
            [
                'topic' => "Mahsulotni to'g'ri saqlash — uzoq ishlatish sirlari",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => [
                    "90% odam shu xatoni qiladi — mahsulot tez buziladi!",
                    "To'g'ri saqlash = 3x uzoq xizmat — bu sirlarni bilib oling!",
                    "Mahsulot tez buzilyaptimi? Sababi shu 5 ta xatoda!",
                ],
                'description_template' => "Problem → Agitation → Solution: Noto'g'ri saqlash oqibatlari → mahsulot buzilishi → to'g'ri usullar. Har bir xato uchun aniq yechim. Saqlash uchun format.",
                'hashtag_seeds' => ['maslahat', 'saqlash', 'sifat', 'uzoqmuddat', 'tips'],
                'pain_text' => "Mahsulot tez buziladi, qanday to'g'ri saqlashni bilmaydi",
            ],
            [
                'topic' => "Taqqoslash va tanlash — qaysi mahsulot sizga mos?",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => [
                    "A yoki B? Batafsil taqqoslash — noto'g'ri tanlasangiz, pul bekor ketadi!",
                    "Bu 2 ta mashhur mahsulotning farqi nimada? Ekspert javob beradi!",
                    "Tanlashdan oldin shu taqqoslashni ko'ring — keyin pushaymon bo'lmaysiz!",
                ],
                'description_template' => "Taqqoslash formati: Mahsulot A vs B → narx, sifat, chidamlilik → kim uchun qaysi biri mos → aniq tavsiya. Big Domino: to'g'ri tanlash = mamnunlik.",
                'hashtag_seeds' => ['taqqoslash', 'comparison', 'tanlash', 'maslahat', 'ekspert'],
                'pain_text' => "Bir nechta variant orasidan tanlayolmaydi, farqini bilmaydi",
            ],
            [
                'topic' => "O'lcham tanlash qo'llanmasi — xato qilmang",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => [
                    "O'lcham xato chiqyaptimi? Bu 3 ta qoidani bilsangiz, hech qachon xato qilmaysiz!",
                    "Noto'g'ri o'lcham oldingizmi? Bu maslahatni SAQLANG — kerak bo'ladi!",
                    "O'lcham tanlashning eng oddiy usuli — 30 sekundda o'rganasiz!",
                ],
                'description_template' => "Problem → Agitation → Solution: Noto'g'ri o'lcham → qaytarish muammosi → to'g'ri o'lchash usuli. Amaliy ko'rsatma — vizual bilan.",
                'hashtag_seeds' => ['olcham', 'sizeguide', 'maslahat', 'tanlash', 'qollanma'],
                'pain_text' => "Noto'g'ri o'lcham olib, qaytarish bilan ovora bo'ladi",
            ],
            // --- ENGAGEMENT: Faollik oshirish ---
            [
                'topic' => "Trend yoki classic? Siz qaysi tarafdamiz?",
                'category' => 'engagement',
                'content_type' => 'story',
                'hooks' => [
                    "TREND yoki CLASSIC — siz qaysi birini tanlaysiz? Izohda yozing!",
                    "Bu ikki uslubdan qaysi biri sizga mos? 1 yoki 2?",
                    "Do'stingizni belgilang — u qaysi birini tanlaydi?",
                ],
                'description_template' => "Poll/tanlov formati. 2 ta vizual variant — muhokama ochish. Engagement oshirish, algoritmda ko'tarilish. Kuchli vizual bilan.",
                'hashtag_seeds' => ['poll', 'tanlov', 'trend', 'classic', 'fikringiz'],
                'pain_text' => "Trendga ergashish yoki classicda qolish — ikkilanadi",
            ],
            [
                'topic' => "Savol-javob — mutaxassis javob beradi",
                'category' => 'engagement',
                'content_type' => 'post',
                'hooks' => [
                    "Eng ko'p beriladigan savolga javob beramiz — siz ham so'rang!",
                    "Sizda ham shu savol bormi? Izohda yozing — javob beramiz!",
                    "— Qaysi birini olsam?\n— Mutaxassis javob beradi...",
                ],
                'description_template' => "Dialog formati. Mijozning real savoli → mutaxassis javobi. O'quvchini izoh yozishga undash. FAQ engagement.",
                'hashtag_seeds' => ['faq', 'savol', 'maslahat', 'mutaxassis', 'javob'],
                'pain_text' => "Savollariga javob topmaydi, kimdan maslahat olishni bilmaydi",
            ],
            // --- BEHIND SCENES: Ichki jarayon ---
            [
                'topic' => "Do'kon ichidan tour — mahsulotlar dunyosi",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => [
                    "Do'konimiz ichidan virtual sayohat — ko'rib hayron qolasiz!",
                    "Bu do'konga kirsangiz, bo'sh qo'l chiqolmaysiz!",
                    "Eng mashhur mahsulotlar bir joyda — do'konimizni ko'ring!",
                ],
                'description_template' => "Do'kon ichini real ko'rsatish. Shaffoflik → ishonch. Vizual storytelling — mahsulotlar ko'rinishi, tartib, muhit. CTA: 'Keling va o'zingiz ko'ring!'.",
                'hashtag_seeds' => ['storetour', 'behindthescenes', 'dokon', 'mahsulotlar', 'shopping'],
                'pain_text' => "Do'konga bormasdan oldin ichini ko'rmoqchi, vaqtini bekor sarflamoqchi emas",
            ],
            [
                'topic' => "Yetkazib berish jarayoni — buyurtmangiz qanday yetib keladi",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => [
                    "Buyurtmangiz qanday yo'ldan o'tadi? Butun jarayonni ko'rsatamiz!",
                    "Do'kondan eshikkacha — yetkazib berish jarayoni ichkaridan",
                    "Har bir buyurtma ehtiyotkorlik bilan yetkaziladi — isbotlaymiz!",
                ],
                'description_template' => "Yetkazib berish jarayonini real ko'rsatish. Buyurtma qabul qilish → qadoqlash → jo'natish → yetkazish. Ishonch qozonish — shaffoflik va professionallik.",
                'hashtag_seeds' => ['delivery', 'yetkazibberish', 'behindthescenes', 'logistika', 'buyurtma'],
                'pain_text' => "Buyurtma vaqtida va butun yetib kelishiga ishonmaydi",
            ],
            // --- AD: Target reklama ---
            [
                'topic' => "Target reklama — do'konga yangi mijoz jalb qilish",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "Sizga kerakli mahsulot — eng yaxshi narxda va sifatda!",
                    "1000+ mamnun mijoz — endi sizning navbatingiz!",
                    "Birinchi xaridingizga MAXSUS CHEGIRMA — do'konimizga keling!",
                ],
                'description_template' => "Star/Story/Solution reklama: Muammo (sifatli mahsulot qimmat) → Hikoya (1000+ mamnun mijoz) → Yechim (bizning do'kon) → Dalil (raqamlar) → Taklif → CTA.",
                'hashtag_seeds' => ['dokon', 'xarid', 'sifat', 'narx', 'chegirma'],
                'pain_text' => "Sifatli va arzon mahsulot topmoqchi, ishonchli do'kon izlaydi",
            ],
        ];
    }

    // ================================================================
    // SERVICE / XIZMAT KO'RSATISH TOPICS
    // DotCom Secrets + Professional ssenariylar asosida
    // ================================================================
    private function getServiceTopics(): array
    {
        return [
            // --- PROMOTIONAL: Sotish va taklif ---
            [
                'topic' => "Bepul konsultatsiya taklifi — muammongizni hal qilamiz",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "Muammongiz bormi? BEPUL konsultatsiya — faqat shu hafta!",
                    "Hali qaror qilmadingizmi? Bepul maslahat oling — hech qanday majburiyatsiz!",
                    "Birinchi qadamni biz bilan boshlang — BEPUL konsultatsiya yozing!",
                ],
                'description_template' => "Star/Story/Solution: Mijozning muammosi → Bepul konsultatsiya taklifi → Qiymat (yechim ko'rsatamiz) → Urgency (shu hafta) → CTA. DotCom Secrets: Invisible Funnel — bepul qiymat berish orqali ishonch qozonish.",
                'hashtag_seeds' => ['bepul', 'konsultatsiya', 'xizmat', 'maslahat', 'yechim'],
                'pain_text' => "Muammosi bor, lekin kimga murojaat qilishini bilmaydi",
            ],
            [
                'topic' => "Xizmat paketlari va narxlar — shaffof taklif",
                'category' => 'promotional',
                'content_type' => 'carousel',
                'hooks' => [
                    "Narxlarimiz shaffof — yashirin to'lovlar YO'Q! O'zingizga mosini tanlang",
                    "3 ta paket — har bir byudjetga mos. Qaysi birini tanlaysiz?",
                    "Boshqalar 2x qimmat oladi — biz sifatni arzon beramiz. Taqqoslang!",
                ],
                'description_template' => "Value Stack formulasi: Har bir paket nima berishi → Umumiy qiymat → Haqiqiy narx → Farq. DotCom Secrets: Stack va Close — paketlar orasidagi farqni aniq ko'rsatish.",
                'hashtag_seeds' => ['narx', 'paket', 'xizmat', 'shaffof', 'arzon'],
                'pain_text' => "Narxlar noaniq, yashirin to'lovlardan qo'rqadi",
            ],
            [
                'topic' => "Maxsus taklif — cheklangan vaqt aksiyasi",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "OXIRGI 3 KUN! Bu narxda xizmat olish imkoniyati tugayapti!",
                    "Faqat shu oy — xizmatimizga 30% chegirma + BONUS konsultatsiya!",
                    "Bu taklifni ko'rganlar 24 soat ichida band qilmoqda — kech qolmang!",
                ],
                'description_template' => "Urgency + Scarcity formulasi: Maxsus taklif → Chegirma + Bonus → Muddat cheklangan → Ijtimoiy dalil (ko'pchilik band qilmoqda) → CTA. DotCom Secrets: Deadline funnel.",
                'hashtag_seeds' => ['aksiya', 'chegirma', 'maxsus', 'cheklangan', 'taklif'],
                'pain_text' => "Xizmatni olmoqchi, lekin narxi to'xtatyapti",
            ],
            // --- TESTIMONIAL: Mijoz natijalari ---
            [
                'topic' => "Muvaffaqiyatli loyiha — case study raqamlar bilan",
                'category' => 'testimonial',
                'content_type' => 'carousel',
                'hooks' => [
                    "Bu mijozimiz 3 oyda ... % natijaga erishdi — qanday qildik?",
                    "'Imkonsiz' degan muammoni hal qildik — loyiha tarixini o'qing!",
                    "OLDIN: muammo. KEYIN: raqamlar gapiradi — case study",
                ],
                'description_template' => "Epiphany Bridge: Mijozning oldingi holati → Muammo tavsifi → Bizga murojaat → Yechim jarayoni → Raqamli natija. DotCom Secrets: Proof → Results → Transformation ko'rsatish.",
                'hashtag_seeds' => ['casestudy', 'natija', 'loyiha', 'muvaffaqiyat', 'portfolio'],
                'pain_text' => "Xizmat natija berishiga ishonmaydi, dalil ko'rmoqchi",
            ],
            [
                'topic' => "Mijoz natijasi — haqiqiy sharh raqamlar bilan",
                'category' => 'testimonial',
                'content_type' => 'post',
                'hooks' => [
                    "Mijozimiz: 'Boshqa kompaniyalarni sinab ko'rgan edim — faqat shu yerda natija chiqdi'",
                    "'Men avval ishonmagan edim' — mijozimiz haqiqiy sharhini o'qing",
                    "Bu sharh filtrsiz, haqiqiy — raqamlar va emotsiya bilan",
                ],
                'description_template' => "Third-person testimonial: Mijoz kim → Muammosi nima edi → Boshqa yechimlar sinab ko'rganmi → Bizga kelgach nima o'zgardi → Raqamli natija. DotCom Secrets: Social proof sifatida.",
                'hashtag_seeds' => ['sharh', 'review', 'mijoz', 'natija', 'ishonch'],
                'pain_text' => "Xizmat sifatiga ishonmaydi, oldingi yomon tajriba bor",
            ],
            // --- EDUCATIONAL: Foydali ma'lumot ---
            [
                'topic' => "Xizmatimiz qanday ishlaydi — 3 bosqichda tushuntirish",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => [
                    "1 daqiqada tushunasiz — xizmatimiz qanday ishlaydi!",
                    "Murakkab jarayonni 3 oddiy bosqichga ajratdik — ko'ring!",
                    "Buni bilganingizda, qaror qilish osonlashadi",
                ],
                'description_template' => "Problem → Agitation → Solution: Mijoz muammosi → Noto'g'ri yechim oqibati → To'g'ri yondashuv (bizning xizmat). DotCom Secrets: Big Domino — bir tushunchani o'zgartirish orqali qarorga olib borish.",
                'hashtag_seeds' => ['xizmat', 'jarayon', 'qollanma', 'tushuntirish', 'bosqich'],
                'pain_text' => "Xizmat qanday ishlashini tushunmaydi, noaniqlik bor",
            ],
            [
                'topic' => "Eng keng tarqalgan xatolar — mijozlar qiladigan 5 ta xato",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => [
                    "90% mijoz shu 5 ta xatoni qiladi — keyin qimmatga tushadi!",
                    "Bu xatolarni qilsangiz, vaqt ham, pul ham yo'qotasiz",
                    "Mutaxassis sifatida aytamiz: bu xatolarni HECH QACHON qilmang!",
                ],
                'description_template' => "Problem → Agitation → Solution: Har bir xato → Oqibati (vaqt/pul yo'qotish) → To'g'ri yo'l. DotCom Secrets: Ekspert ishonchini qozonish va keyin xizmatga yo'naltirish.",
                'hashtag_seeds' => ['xatolar', 'maslahat', 'mutaxassis', 'tips', 'ogohlantirish'],
                'pain_text' => "Noto'g'ri qaror qilishdan qo'rqadi, bilim yetishmaydi",
            ],
            [
                'topic' => "Soha trendlari — 2026 yilda nima o'zgarmoqda",
                'category' => 'educational',
                'content_type' => 'post',
                'hooks' => [
                    "2026 yilda sohada 5 ta katta o'zgarish kutilmoqda — 3-chisi sizni hayratga soladi!",
                    "Bu trendlarni bilmasangiz, raqobatda orqada qolasiz",
                    "Soha eksperti sifatida aytamiz: bu o'zgarishga HOZIR tayyorlaning!",
                ],
                'description_template' => "Soha trendlari va kelajak prognozi. DotCom Secrets: Ekspert pozitsiyasini mustahkamlash → Auditoriya ishonchini qozonish → Xizmatga tabiiy yo'naltirish.",
                'hashtag_seeds' => ['trend', 'soha', 'kelajak', 'yangilik', 'ekspert'],
                'pain_text' => "Sohadagi o'zgarishlardan xabarsiz, orqada qolishdan qo'rqadi",
            ],
            // --- ENGAGEMENT: Faollik oshirish ---
            [
                'topic' => "Savol bering — mutaxassis javob beradi",
                'category' => 'engagement',
                'content_type' => 'story',
                'hooks' => [
                    "BUGUN mutaxassis savollaringizga BEPUL javob beradi — yozing!",
                    "Qanday savol bo'lsa ham — izohda yozing, javob beramiz!",
                    "Bu imkoniyat tez-tez bo'lmaydi — savolingizni HOZIR yozing!",
                ],
                'description_template' => "Dialog formati. Mutaxassis savol-javob sessiyasi → O'quvchini izoh yozishga undash → Engagement oshirish → Algoritmda ko'tarilish. DotCom Secrets: Attractive Character — ekspert sifatida namoyon bo'lish.",
                'hashtag_seeds' => ['savol', 'mutaxassis', 'maslahat', 'bepul', 'javob'],
                'pain_text' => "Savollariga javob topmayapti, professional maslahat kerak",
            ],
            [
                'topic' => "Qaysi paketni tanlaysiz? Sizga mosini topamiz",
                'category' => 'engagement',
                'content_type' => 'post',
                'hooks' => [
                    "3 ta paket — qaysi biri SIZGA mos? Izohda yozing, maslahat beramiz!",
                    "A yoki B yoki C? Har birining afzalligi bor — qaysi birini tanlaysiz?",
                    "Byudjetingiz va ehtiyojingizga qarab — qaysi paketni maslahat beramiz",
                ],
                'description_template' => "Poll/tanlov formati. 3 ta xizmat paketi → Har birining qisqacha foydasi → Izohda tanlash → Maslahat berish. DotCom Secrets: Micro-commitment — kichik qaror katta qarorga olib boradi.",
                'hashtag_seeds' => ['tanlov', 'paket', 'poll', 'xizmat', 'fikringiz'],
                'pain_text' => "Qaysi xizmatni tanlashda ikkilanadi, maslahat kerak",
            ],
            // --- BEHIND SCENES: Ichki jarayon ---
            [
                'topic' => "Ish jarayoni ichidan — loyiha qanday amalga oshadi",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => [
                    "Hech kim ko'rmagan jarayonni ko'rsatamiz — loyiha qanday tug'iladi!",
                    "30 sekundda ish jarayonimiz — sifatning siri shu!",
                    "Sahna ortida nima bo'ladi? Professional yondashuv ichidan",
                ],
                'description_template' => "Ish jarayonini real ko'rsatish. Shaffoflik → Ishonch qozonish. DotCom Secrets: Attractive Character — professional va ishonchli tasvir. Har bir bosqichda sifat nazorati ko'rsatish.",
                'hashtag_seeds' => ['behindthescenes', 'jarayon', 'sifat', 'professional', 'ishonch'],
                'pain_text' => "Ish qanday bajarilishini bilmaydi, sifatdan xavotir",
            ],
            [
                'topic' => "Jamoamiz bilan tanishing — har biri sohasining mutaxassisi",
                'category' => 'behind_scenes',
                'content_type' => 'carousel',
                'hooks' => [
                    "Sizga xizmat ko'rsatadigan jamoa — har biri ... yillik tajriba!",
                    "Bu professional jamoaga ishonasiz — natijalari o'zi gapiradi!",
                    "Jamoamiz: tajriba + bilim + natija = sizning muvaffaqiyatingiz!",
                ],
                'description_template' => "Jamoa tanishish. Attractive Character (DotCom Secrets): har bir mutaxassisning backstory, tajribasi, natijasi. Har bir slaydda — ism, lavozim, tajriba yili, muvaffaqiyat raqami.",
                'hashtag_seeds' => ['jamoa', 'team', 'mutaxassis', 'professional', 'meettheteam'],
                'pain_text' => "Kim xizmat ko'rsatishini bilmaydi, jamoaga ishonchi yo'q",
            ],
            // --- AD: Target reklama ---
            [
                'topic' => "Target reklama — xizmatga yangi mijoz jalb qilish",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "Bu muammo sizga tanishmi? BIZ HAL QILAMIZ — natijaga kafolatimiz!",
                    "... ta mijozimiz natijaga erishdi — navbat sizda!",
                    "Boshqa kutmang — muammongiz kuchayib borishidan oldin murojaat qiling!",
                ],
                'description_template' => "Star/Story/Solution formulasi: Muammo (og'riq nuqtasi) → Og'riq kuchaytirish (kutish oqibati) → Yechim (bizning xizmat) → Dalil (raqamlar) → Taklif → Urgency → CTA.",
                'hashtag_seeds' => ['xizmat', 'yechim', 'professional', 'natija', 'murojaat'],
                'pain_text' => "Muammoni hal qilmoqchi, lekin yaxshi mutaxassis topa olmaydi",
            ],
        ];
    }

    // ================================================================
    // SAAS / DASTURIY TA'MINOT TOPICS
    // DotCom Secrets + Professional ssenariylar asosida
    // ================================================================
    private function getSaasTopics(): array
    {
        return [
            // --- PROMOTIONAL: Sotish va taklif ---
            [
                'topic' => "Bepul demo/trial taklifi — 14 kun sinab ko'ring",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "14 KUN BEPUL! Dasturimizni sinab ko'ring — karta talab qilinmaydi!",
                    "Hali ishonmayapsizmi? Bepul sinab ko'ring — natijani O'ZINGIZ ko'rasiz!",
                    "Minglab kompaniya allaqachon foydalanmoqda — siz ham BEPUL boshlang!",
                ],
                'description_template' => "Star/Story/Solution: Muammo (vaqt yo'qotish, qo'lda ishlash) → Yechim (dastur) → Bepul sinash taklifi → Scarcity yo'q, lekin urgency bor (muammo har kuni kuchayadi) → CTA. DotCom Secrets: Tripwire funnel — bepul trial.",
                'hashtag_seeds' => ['bepul', 'trial', 'demo', 'dastur', 'sinash'],
                'pain_text' => "Dasturni olishdan oldin sinab ko'rmoqchi, xavfdan qo'rqadi",
            ],
            [
                'topic' => "Yangi funksiya e'loni — dasturda nima yangi",
                'category' => 'promotional',
                'content_type' => 'reel',
                'hooks' => [
                    "YANGI FUNKSIYA chiqdi! Siz kutgan imkoniyat endi mavjud!",
                    "Bu yangilik ishingizni 2x tezlashtiradi — hoziroq sinab ko'ring!",
                    "Foydalanuvchilarimiz so'ragan #1 funksiya — tayyor!",
                ],
                'description_template' => "Yangi funksiya e'loni. Hook → Nima yangi → Qanday muammoni hal qiladi → Kim uchun → Demo ko'rsatish → CTA. DotCom Secrets: Product Launch formula.",
                'hashtag_seeds' => ['yangilik', 'funksiya', 'update', 'dastur', 'release'],
                'pain_text' => "Mavjud dasturdan ko'proq imkoniyat kutadi",
            ],
            [
                'topic' => "Maxsus narx — yillik paketda katta chegirma",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "FAQAT SHU OY! Yillik paketda 40% chegirma — 4 oy BEPUL!",
                    "Oylik to'lash o'rniga yillik oling — ... so'm tejaysiz!",
                    "Bu narxda endi bo'lmaydi — 3 kun qoldi!",
                ],
                'description_template' => "Urgency + Scarcity formulasi: Value Stack (funksiyalar qiymati) → Haqiqiy narx → Chegirma → Tejalgan summa → Muddat cheklangan → CTA. DotCom Secrets: Deadline funnel + Price anchoring.",
                'hashtag_seeds' => ['chegirma', 'aksiya', 'yillik', 'maxsus', 'narx'],
                'pain_text' => "Dastur kerak, lekin oylik to'lov qimmat tuyuladi",
            ],
            // --- TESTIMONIAL: Foydalanuvchi natijalari ---
            [
                'topic' => "Foydalanuvchi muvaffaqiyat hikoyasi — raqamlar bilan",
                'category' => 'testimonial',
                'content_type' => 'carousel',
                'hooks' => [
                    "Bu kompaniya dasturimiz bilan vaqtni 60% tejadi — qanday?",
                    "'Oldin Excelda 3 soat sarflardik, endi 15 daqiqa' — real sharh",
                    "... ta xodim, ... % samaradorlik o'sishi — foydalanuvchimiz gapiradi!",
                ],
                'description_template' => "Epiphany Bridge: Kompaniyaning oldingi holati (qo'lda ish, xatolar) → Dasturimizni topishi → Aha-moment → Raqamli natija (vaqt tejash, xato kamaytirish, daromad o'sishi). DotCom Secrets: Proof stacking.",
                'hashtag_seeds' => ['casestudy', 'natija', 'muvaffaqiyat', 'samaradorlik', 'sharh'],
                'pain_text' => "Dastur haqiqatan ham natija berishiga ishonmaydi",
            ],
            [
                'topic' => "Case study — kompaniya muammosini qanday hal qildik",
                'category' => 'testimonial',
                'content_type' => 'post',
                'hooks' => [
                    "Bu kompaniya oyiga ... soat yo'qotayotgan edi. Endi yo'q!",
                    "'Boshqa dasturlarni sinab ko'rgan edik — faqat bu yerda natija chiqdi'",
                    "OLDIN: 5 ta xodim + xatolar. KEYIN: 1 dastur + mukammal natija",
                ],
                'description_template' => "Third-person case study: Kompaniya nomi → Muammosi → Boshqa yechimlar sinab ko'rganmi → Bizni topishi → Natija raqamlarda. DotCom Secrets: Social proof + Before/After/Bridge.",
                'hashtag_seeds' => ['casestudy', 'raqamlar', 'dalil', 'kompaniya', 'natija'],
                'pain_text' => "Dastur tanlashda xato qilishdan qo'rqadi, dalil ko'rmoqchi",
            ],
            // --- EDUCATIONAL: Foydali ma'lumot ---
            [
                'topic' => "Muammoni hal qilish qo'llanmasi — bosqichma-bosqich",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => [
                    "Bu muammo sizga tanishmi? 5 bosqichda HAL QILAMIZ!",
                    "90% kompaniya shu muammoni qo'lda hal qiladi — biz avtomatlashtirdik!",
                    "Bu qo'llanmani saqlang — har kuni kerak bo'ladi!",
                ],
                'description_template' => "Problem → Agitation → Solution: Keng tarqalgan biznes muammosi → Qo'lda ishlash oqibatlari → Dastur yordamida yechim. DotCom Secrets: Value berish → Ekspert ishonchi → Dasturga tabiiy yo'naltirish.",
                'hashtag_seeds' => ['qollanma', 'tutorial', 'yechim', 'muammo', 'maslahat'],
                'pain_text' => "Muammoni hal qilishning oson yo'lini bilmaydi",
            ],
            [
                'topic' => "Vaqtni tejash maslahatlari — 5 ta oddiy usul",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => [
                    "Har kuni 2 SOAT tejashingiz mumkin — bu 5 ta usul bilan!",
                    "Bu usulni bilganingizda, nima uchun oldin qilmaganimga afsuslanasiz!",
                    "Xodimlaringiz qo'lda ishlamaydi — avtomatlashtiring va vaqt tejang!",
                ],
                'description_template' => "Bepul amaliy maslahat. Har bir usul → Qancha vaqt tejaydi → Natija. DotCom Secrets: Big Domino — avtomatlashtirish = erkinlik. Value berish orqali dasturga qiziqtirish.",
                'hashtag_seeds' => ['vaqt', 'tejash', 'samaradorlik', 'avtomatlashtirish', 'maslahat'],
                'pain_text' => "Vaqti yetishmaydi, barchani qo'lda qiladi",
            ],
            [
                'topic' => "Tutorial — dasturdan foydalanish qo'llanmasi",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => [
                    "30 sekundda o'rganing — bu funksiya ishingizni osonlashtiradi!",
                    "Ko'pchilik bu funksiyani bilmaydi — bilsangiz, hayotingiz osonlashadi!",
                    "Bosqichma-bosqich ko'rsatamiz — hech kim qiynalmaydi!",
                ],
                'description_template' => "Tutorial format: Muammo → Dasturda yechim → Bosqichma-bosqich ko'rsatish → Natija. Maqsad: mavjud foydalanuvchilarni aktivlashtirish + yangilarni jalb qilish.",
                'hashtag_seeds' => ['tutorial', 'qollanma', 'howto', 'dastur', 'oson'],
                'pain_text' => "Dasturni to'liq ishlatishni bilmaydi, funksiyalarni tushunmaydi",
            ],
            // --- ENGAGEMENT: Faollik oshirish ---
            [
                'topic' => "Qaysi funksiya eng kerakli? So'rovnoma",
                'category' => 'engagement',
                'content_type' => 'story',
                'hooks' => [
                    "Qaysi funksiya SIZGA eng kerak? Izohda yozing — birinchi yaratamiz!",
                    "3 ta yangi funksiya rejalashtirilmoqda — qaysi birini oldin chiqaraylik?",
                    "Sizning ovozingiz muhim — dasturimizni SIZ shakllantiring!",
                ],
                'description_template' => "Poll/so'rovnoma formati. Foydalanuvchilar fikrini olish → Engagement oshirish → Algoritmda ko'tarilish. DotCom Secrets: Micro-commitment — kichik ishtirok katta qarorga olib boradi.",
                'hashtag_seeds' => ['poll', 'sorovnoma', 'funksiya', 'ovoz', 'fikringiz'],
                'pain_text' => "O'zi uchun kerakli funksiya bor-yo'qligini bilmaydi",
            ],
            [
                'topic' => "Soha statistikasi — raqamlar gapiradi",
                'category' => 'engagement',
                'content_type' => 'post',
                'hooks' => [
                    "Bu raqam sizni hayratda qoldiradi: ... % kompaniya hali qo'lda ishlaydi!",
                    "Soha statistikasi: avtomatlashtirilgan kompaniyalar ... % tezroq o'sadi",
                    "2026 yil statistikasi — bu trenddan orqada qolmang!",
                ],
                'description_template' => "Statistika va faktlar bilan engagement. DotCom Secrets: Epiphany Bridge — raqamlar orqali 'aha-moment' yaratish. Har bir fakt → izoh yozishga undash → dasturga qiziqish.",
                'hashtag_seeds' => ['statistika', 'raqamlar', 'soha', 'trend', 'fakt'],
                'pain_text' => "Sohadagi o'zgarishlardan xabarsiz, raqobatda orqada qolmoqda",
            ],
            // --- BEHIND SCENES: Ichki jarayon ---
            [
                'topic' => "Dasturchi jamoamiz — kod ortidagi insonlar",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => [
                    "Dasturingiz ortida kim bor? Jamoamiz bilan tanishing!",
                    "Kod yozish jarayonini ko'ring — professional dasturchilar ishlaydi!",
                    "Bu jamoaga ishonasiz — har biri ... yillik tajribaga ega!",
                ],
                'description_template' => "Dasturchi jamoani ko'rsatish. DotCom Secrets: Attractive Character — insoniy tasvir yaratish. Kompaniya emas, odamlar ishlaydi degan his. Ishonch va bog'lanish.",
                'hashtag_seeds' => ['devlife', 'jamoa', 'dasturchi', 'behindthescenes', 'tech'],
                'pain_text' => "Dastur ortida kim borligini bilmaydi, ishonch yetishmaydi",
            ],
            [
                'topic' => "Yangilik ustida ish jarayoni — dastur qanday tug'iladi",
                'category' => 'behind_scenes',
                'content_type' => 'carousel',
                'hooks' => [
                    "Yangi funksiya qanday yaratiladi? G'oyadan relizgacha — jarayon!",
                    "Foydalanuvchi so'radi → Biz yaratdik! Jarayonni ko'ring",
                    "1 ta funksiya ortida ... soat ish bor — qadrlang!",
                ],
                'description_template' => "Product development jarayoni. G'oya → Dizayn → Kod → Test → Reliz. DotCom Secrets: Shaffoflik va ishonch. Foydalanuvchi o'z so'rovi amalga oshganini ko'radi — loyalty oshadi.",
                'hashtag_seeds' => ['development', 'jarayon', 'yangilik', 'product', 'behindthescenes'],
                'pain_text' => "Dastur qanday rivojlanishini bilmaydi, yangiliklar keladimi degan shubha",
            ],
            // --- AD: Target reklama ---
            [
                'topic' => "Target reklama — dasturga yangi foydalanuvchi jalb qilish",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "Hali Excelda ishlayapsizmi? ... % kompaniya allaqachon avtomatlashtirilgan!",
                    "Har kuni ... soat yo'qotasiz — bu dastur bilan TO'XTATANG!",
                    "Raqobatchilaringiz allaqachon foydalanmoqda — siz ham boshlang!",
                ],
                'description_template' => "Star/Story/Solution formulasi: Muammo (qo'lda ish, xatolar, vaqt yo'qotish) → Og'riq kuchaytirish (raqobatda orqada qolish) → Yechim (dastur) → Dalil (foydalanuvchilar raqami) → Bepul trial → CTA.",
                'hashtag_seeds' => ['dastur', 'avtomatlashtirish', 'samaradorlik', 'biznes', 'reklama'],
                'pain_text' => "Qo'lda ishlaydi, vaqt va pul yo'qotadi, zamonaviy yechim kerak",
            ],
        ];
    }

    // ================================================================
    // FITNESS / SPORT ZAL TOPICS
    // DotCom Secrets + Professional ssenariylar asosida
    // ================================================================
    private function getFitnessTopics(): array
    {
        return [
            // --- PROMOTIONAL: Sotish va taklif ---
            [
                'topic' => "Birinchi oy chegirma yoki bepul mashg'ulot",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "Birinchi oy BEPUL! Faqat shu hafta — joylar cheklangan!",
                    "Sport zalga borishni xohlaysiz, lekin narx to'xtatyaptimi? Endi sabab yo'q!",
                    "Hozir yozilsangiz, 1 oy BEPUL + personal trener bonus!",
                ],
                'description_template' => "Star/Story/Solution: Muammo (shakldan norozilik) → Hikoya (harakat qilmay vaqt o'tdi) → Yechim (bizning zal) → Taklif (1 oy bepul) → Urgency (joy cheklangan) → CTA.",
                'hashtag_seeds' => ['sportzal', 'bepul', 'chegirma', 'fitness', 'tashkent'],
                'pain_text' => "Sport zalga borishni xohlaydi, lekin narx yoki uyalish to'xtatyapti",
            ],
            [
                'topic' => "Personal trener bilan shaxsiy mashg'ulot paketi",
                'category' => 'promotional',
                'content_type' => 'post',
                'hooks' => [
                    "Yolg'iz mashq qilib natija chiqmayaptimi? Personal trener yechim!",
                    "Trener bilan 3 oyda natija — kafolatlaymiz!",
                    "Maxsus paket: Personal trener + ovqatlanish rejasi — hozir band qiling!",
                ],
                'description_template' => "Value stack formulasi: Personal trener (qiymat) + Ovqatlanish rejasi (bonus) + Natija kafolati (risk reversal) → Urgency (cheklangan joy) → CTA.",
                'hashtag_seeds' => ['personaltrener', 'shaxsiymashq', 'trener', 'fitness', 'natija'],
                'pain_text' => "O'zi mashq qiladi lekin natija chiqmaydi, yo'l-yo'riq kerak",
            ],
            [
                'topic' => "Yangi paket — oilaviy yoki juftlik abonement",
                'category' => 'promotional',
                'content_type' => 'reel',
                'hooks' => [
                    "YANGILIK! Juftlik abonement — 2-chi kishi 50% chegirma!",
                    "Oilangiz bilan birga sport qiling — yangi oilaviy paket!",
                    "Do'stingiz bilan birga yozilsangiz, ikkovingizga maxsus narx!",
                ],
                'description_template' => "Yangi paket e'loni. Hook → Nima yangi → Kim uchun → Foyda (birgalikda arzonroq) → Scarcity (cheklangan vaqt) → CTA.",
                'hashtag_seeds' => ['juftlik', 'oilaviy', 'abonement', 'fitness', 'sport'],
                'pain_text' => "Yolg'iz borish zerikarli, sherigi yo'q, oilasi bilan vaqt o'tkazmoqchi",
            ],
            // --- TESTIMONIAL: Mijoz natijalari ---
            [
                'topic' => "Mijoz transformatsiyasi — oldin va keyin",
                'category' => 'testimonial',
                'content_type' => 'carousel',
                'hooks' => [
                    "Bu o'zgarishga ishonmaysiz! 15 kg yo'qotdi — OLDIN va KEYIN",
                    "'Menga sport yordam bermaydi' degan edi. 90 kunlik natijani ko'ring!",
                    "Bu odam 3 oy oldin bu zalga birinchi marta keldi — hozir natijasini ko'ring",
                ],
                'description_template' => "Epiphany Bridge: Mijozning oldingi holati (ortiqcha vazn, charchoq) → 'Men qila olmayman' ishonchi → zalga kelishi → aha-moment → 90 kunlik natija. Raqamlar va emotsiya bilan.",
                'hashtag_seeds' => ['transformation', 'beforeafter', 'fitness', 'natija', 'sportzal'],
                'pain_text' => "O'zini yoqtirmaydi, o'zgarish mumkinligiga ishonmaydi",
            ],
            [
                'topic' => "90 kunlik natija hikoyasi — mijoz so'zlari bilan",
                'category' => 'testimonial',
                'content_type' => 'reel',
                'hooks' => [
                    "90 kunda hayotim o'zgardi — mijozimiz o'zi gapiradi!",
                    "Oldin: 'qila olmayman'. 90 kun keyin: natijani ko'ring!",
                    "'Eng yaxshi qarorim — shu zalga kelganim' — mijozimiz fikri",
                ],
                'description_template' => "Epiphany Bridge testimonial video. Mijozning o'z ovozida: oldingi holati → qiyinchiliklari → birinchi oy tajribasi → 90 kunlik natija. Social proof — boshqalar ham qila oladi.",
                'hashtag_seeds' => ['90kunchallenge', 'testimonial', 'natija', 'fitness', 'motivation'],
                'pain_text' => "Sportni boshlashdan qo'rqadi, natija chiqishiga ishonmaydi",
            ],
            // --- EDUCATIONAL: Foydali ma'lumot ---
            [
                'topic' => "Mashq xatolari — 90% odam qiladi va jarohat oladi",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => [
                    "90% odam shu mashq xatolarini qiladi — jarohatga olib keladi!",
                    "Skuat, planka, press — hammasini noto'g'ri qilyapsiz! To'g'risini ko'rsatamiz",
                    "Bu 5 ta xatoni to'xtatsangiz, natija 2 barobar tez chiqadi",
                ],
                'description_template' => "Problem → Agitation → Solution: Keng tarqalgan mashq xatolari → oqibatlari (jarohat, natijasizlik) → to'g'ri texnika. Ekspert maslahati sifatida ishonch qozonish.",
                'hashtag_seeds' => ['mashqxatolari', 'technique', 'fitness', 'safety', 'maslahat'],
                'pain_text' => "Mashq qiladi lekin natija yo'q, ba'zan og'riq bor, nima xato bilmaydi",
            ],
            [
                'topic' => "Ovqatlanish maslahat — mashqdan keyin nima yeyish kerak",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => [
                    "Mashqdan keyin NIMA yeyish kerak? 70% natija ovqatlanishda!",
                    "Bu xatoni qilsangiz, mashqingiz behuda! Ovqatlanish qoidalari",
                    "Protein, uglevodlar, kaloriya — oddiy tilda tushuntiramiz!",
                ],
                'description_template' => "Problem → Agitation → Solution: Mashq qiladi lekin natija yo'q → sababi ovqatlanishda → oddiy va amaliy ovqatlanish rejasi. Big Domino: to'g'ri ovqatlanish = natijaning 70%.",
                'hashtag_seeds' => ['ovqatlanish', 'nutrition', 'protein', 'healthyfood', 'fitnesstips'],
                'pain_text' => "Mashq qiladi lekin vazn tushmaydi, ovqatlanishni bilmaydi",
            ],
            [
                'topic' => "Uy mashqlari — jihozlarsiz samarali mashqlar",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => [
                    "Zalga bora olmaysizmi? 5 ta uy mashqi — jihozlarsiz katta natija!",
                    "Uyda 15 daqiqa — zalda 1 soatcha samarali! Isbotlaymiz",
                    "Hech qanday jihozlarsiz — professional trener ko'rsatadi",
                ],
                'description_template' => "Bepul amaliy maslahat. Value berish → Ekspert ishonchi qozonish. Maqsad: foydali kontent berib, zalga qiziqtirish. CTA: 'Yana ko'proq mashqlar uchun — zalga keling!'.",
                'hashtag_seeds' => ['uymashqlari', 'homeworkout', 'jihozlarsiz', 'fitness', 'mashq'],
                'pain_text' => "Zalga bora olmaydi (vaqt/pul), lekin shaklga tushmoqchi",
            ],
            // --- ENGAGEMENT: Faollik oshirish ---
            [
                'topic' => "Challenge — 7 kunlik sport challenge, qo'shiling!",
                'category' => 'engagement',
                'content_type' => 'story',
                'hooks' => [
                    "7 KUNLIK CHALLENGE boshlandi! Siz ham qo'shilasizmi? Izohda yozing!",
                    "100 ta squat har kuni — 7 kunga chidaysizmi? Challenge!",
                    "Kim bilan challenge qilasiz? Do'stingizni belgilang — birga boshlaymiz!",
                ],
                'description_template' => "Challenge formati. Faollik va izohlarni oshirish. Ishtirokchilarni hikoyaga chiqarish va'dasi. Urgency: 'Bugun boshlanadi — kechikish yo'q!'.",
                'hashtag_seeds' => ['challenge', 'fitnesschallenge', '7kunchallenge', 'sport', 'motivation'],
                'pain_text' => "Motivatsiya yo'q, yolg'iz boshlash qiyin, sherigi kerak",
            ],
            [
                'topic' => "Motivatsion savol — nega sport qilasiz?",
                'category' => 'engagement',
                'content_type' => 'post',
                'hooks' => [
                    "Nega sport qilasiz? Sog'liq uchunmi, chiroyli ko'rinish uchunmi? Izohda yozing!",
                    "Eng qiyin narsa — boshlash. Sizni nima to'xtatyapti? Javob bering!",
                    "1 yil oldin boshlagan bo'lsangiz, hozir qanday bo'lardingiz? O'ylang...",
                ],
                'description_template' => "Dialog formati. O'quvchi o'zini taniydigan savol. Izohda javob berishga undash → engagement oshadi → algoritmda ko'tarilish.",
                'hashtag_seeds' => ['motivation', 'fitness', 'savol', 'sport', 'boshlash'],
                'pain_text' => "Motivatsiya yo'qolgan, maqsadini unutgan, ilhom kerak",
            ],
            // --- BEHIND SCENES: Ichki jarayon ---
            [
                'topic' => "Zal ichidan — jihozlar, muhit va atmosfera",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => [
                    "Zalimiz ichini ko'ring — eng zamonaviy jihozlar, toza muhit!",
                    "Bu videoni ko'rib, zalga kelgingiz keladi — kafolatlaymiz!",
                    "Sport zal qanday bo'lishi kerak? Mana shunday! Virtual tour",
                ],
                'description_template' => "Zal ichini real ko'rsatish. Shaffoflik → ishonch qozonish. Jihozlar sifati, tozalik, muhit — barcha xavotirlarni olib tashlash. CTA: 'Bepul sinov mashg'ulotiga yozing!'.",
                'hashtag_seeds' => ['gymtour', 'sportzal', 'jihozlar', 'fitness', 'gym'],
                'pain_text' => "Zal sifatini bilmaydi, iflos yoki eski jihozli zal bo'lishidan qo'rqadi",
            ],
            [
                'topic' => "Trener bilan tanishing — tajriba va muvaffaqiyatlar",
                'category' => 'behind_scenes',
                'content_type' => 'carousel',
                'hooks' => [
                    "Sizni o'qitadigan trener — ... yillik tajriba, ... ta mijoz transformatsiyasi",
                    "Bu trener o'z hayotini sport va sog'liqqa bag'ishlagan — hikoyasini o'qing",
                    "Trenerimiz bilan tanishing — u sizni maqsadingizga olib boradi!",
                ],
                'description_template' => "Attractive Character (DotCom Secrets): Trenerning backstory → sport bilan qanday boshlagan → tajribasi → mijozlar natijalari. Ishonch va bog'lanish.",
                'hashtag_seeds' => ['trener', 'coach', 'personaltrainer', 'fitness', 'meetthecoach'],
                'pain_text' => "Trener malakasiga ishonmaydi, kim o'qitishini bilmaydi",
            ],
            // --- AD: Target reklama ---
            [
                'topic' => "Target reklama — sport zalga yangi mijoz jalb qilish",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "Ortiqcha vazndan xalos bo'lmoqchimisiz? 90 kunda natija — kafolatlaymiz!",
                    "Ko'zguga qarab o'zingiz yoqmayaptimi? Biz bilan 3 oyda o'zgarasiz!",
                    "Sport boshlash uchun eng to'g'ri vaqt — HOZIR. Birinchi mashg'ulot bepul!",
                ],
                'description_template' => "Star/Story/Solution reklama: Muammo (ortiqcha vazn, energiya yo'q) → Og'riq kuchaytirish (har kuni og'irlashadi) → Yechim (bizning zal + trener) → Dalil (mijoz natijalari) → Taklif → CTA.",
                'hashtag_seeds' => ['sportzal', 'fitness', 'gym', 'tashkent', 'transformation'],
                'pain_text' => "Ortiqcha vazn, energiya yo'q, o'ziga ishonchi pasaygan",
            ],
        ];
    }

    // ================================================================
    // EDUCATION / O'QUV MARKAZ TOPICS
    // DotCom Secrets + Professional ssenariylar asosida
    // ================================================================
    private function getEducationTopics(): array
    {
        return [
            // --- PROMOTIONAL: Grant/Aksiya e'lonlari ---
            [
                'topic' => "Grant e'loni — bepul o'qish imkoniyati",
                'category' => 'promotional',
                'content_type' => 'post',
                'hooks' => [
                    "GRANT OCHILDI! Bepul o'qish imkoniyati cheklangan — faqat ... ta joy",
                    "Hech qachon bunday imkoniyat bo'lmagan — GRANT dasturi boshlandi!",
                    "Siz kursga yozilmoqchi edingiz, lekin narxi to'xtatdimi? Endi sabab yo'q!",
                ],
                'description_template' => "Grant dasturi haqida e'lon. Urgency va scarcity bilan — joy cheklangan, muddat bor. DotCom Secrets: Hook → Offer → Urgency → CTA.",
                'hashtag_seeds' => ['grant', 'bepul', 'kurs', 'imkoniyat', 'talim'],
                'pain_text' => "Kursga yozilmoqchi, lekin narxi to'xtatyapti",
            ],
            [
                'topic' => "Maxsus chegirma — kurs narxida katta chegirma",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "OXIRGI KUN! Bu narxda endi bo'lmaydi",
                    "Birinchi marta shunday chegirma — faqat ... gacha",
                    "Narxni ko'rib ishonmaysiz — lekin haqiqat!",
                ],
                'description_template' => "Kurs chegirmasi reklama posti. Value stack: asosiy kurs + bonuslar + umumiy qiymat. Urgency: muddat cheklangan.",
                'hashtag_seeds' => ['chegirma', 'kurs', 'aksiya', 'talim', 'imkoniyat'],
                'pain_text' => "Pul topish usulini o'rganmoqchi, lekin qimmat deb o'ylaydi",
            ],
            // --- TESTIMONIAL: Mijoz natijalari ---
            [
                'topic' => "O'quvchi muvaffaqiyat hikoyasi — real natija",
                'category' => 'testimonial',
                'content_type' => 'carousel',
                'hooks' => [
                    "U 0 dan ... ga erishdi — faqat 3 oyda!",
                    "Bu o'quvchimiz hech kimga ishonmagan edi. Natijasini ko'ring",
                    "'Men qila olmayman' degan edi. Endi oyiga ... so'm topadi",
                ],
                'description_template' => "Epiphany Bridge hikoya: o'quvchining oldingi holati → qiyinchiliklari → kursda o'rganganlari → hozirgi natijasi. Raqamlar va dalillar bilan.",
                'hashtag_seeds' => ['natija', 'muvaffaqiyat', 'talim', 'kurs', 'transformation'],
                'pain_text' => "O'quvchi oldin umidsiz edi, endi natijaga erishdi",
            ],
            [
                'topic' => "Oldin va keyin — o'quvchi transformatsiyasi",
                'category' => 'testimonial',
                'content_type' => 'reel',
                'hooks' => [
                    "3 oy oldin: ishsiz. Hozir: oyiga ... so'm daromad",
                    "Oldin: 'qila olmayman'. Keyin: natijani ko'ring!",
                    "Bu odamning hayoti 90 kunda o'zgardi",
                ],
                'description_template' => "Oldin va keyin format. O'quvchining transformatsiyasini raqamlar bilan ko'rsatish. Social proof — boshqalar ham qila oladi.",
                'hashtag_seeds' => ['beforeafter', 'transformation', 'natija', 'talim'],
                'pain_text' => "Yangi kasb o'rganib, daromad olish mumkinligiga ishonmayapti",
            ],
            // --- EDUCATIONAL: Foydali ma'lumot ---
            [
                'topic' => "Eng keng tarqalgan xatolar — nima uchun natija chiqmaydi",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => [
                    "90% o'quvchilar shu 3 ta xatoni qiladi — siz ham!",
                    "Nega natija chiqmaydi? 5 ta sabab",
                    "Bu xatolarni qilsangiz, hech qachon o'rganolmaysiz",
                ],
                'description_template' => "Eng keng tarqalgan xatolar va ularning yechimlari. DotCom Secrets: Problem → Agitation → Solution. Har bir xato uchun to'g'ri yo'lni ko'rsatish.",
                'hashtag_seeds' => ['xatolar', 'maslahat', 'talim', 'tips', 'oquvmarkaz'],
                'pain_text' => "O'qiydi lekin natija chiqmaydi, nima qilishini bilmaydi",
            ],
            [
                'topic' => "Bepul amaliy maslahat — hoziroq qo'llang",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => [
                    "1 daqiqada o'rganing — bu maslahatni hech kim bermaydi",
                    "Bepul maslahat: bu oddiy usul daromadni 2x oshiradi",
                    "5 ta amaliy qadam — bugun boshlang",
                ],
                'description_template' => "Bepul amaliy maslahat — foydali, saqlashga arziydigan. Maqsad: ekspert sifatida ishonch qozonish va kursga qiziqtirish.",
                'hashtag_seeds' => ['maslahat', 'bepul', 'tips', 'oqish', 'skill'],
                'pain_text' => "Yangi ko'nikma o'rganmoqchi, lekin qaerdan boshlashini bilmaydi",
            ],
            [
                'topic' => "Bu kasb kelajakda eng talab qilinadigan — nima uchun?",
                'category' => 'educational',
                'content_type' => 'post',
                'hooks' => [
                    "2026 yilda eng ko'p talab qilinadigan 5 ta kasb — 3-chisi sizni hayratga soladi",
                    "Bu kasb hali O'zbekistonda kam — shuning uchun endi boshlash kerak",
                    "AI davri keldi — bu kasblar yo'qoladi, bu kasblar paydo bo'ladi",
                ],
                'description_template' => "Soha kelajagi haqida statistika va faktlar. Maqsad: kursga yozilish uchun motivatsiya. DotCom Secrets: Big Domino — bir ishonchni buzish.",
                'hashtag_seeds' => ['kasb', 'kelajak', 'trend', 'talim', 'career'],
                'pain_text' => "Qaysi kasbni tanlashini bilmaydi, kelajakdan xavotir",
            ],
            // --- ENGAGEMENT: Faollik oshirish ---
            [
                'topic' => "Savol-javob: qaysi kursni tanlash kerak?",
                'category' => 'engagement',
                'content_type' => 'story',
                'hooks' => [
                    "Qaysi kasbni tanlaysiz? A yoki B? Izohda yozing!",
                    "Eng qiyin tanlov: pul yoki vaqt? Siz nimani tanlaysiz?",
                    "Bu 2 ta imkoniyatdan qaysi birini tanlardingiz?",
                ],
                'description_template' => "Poll yoki tanlov formati. Muhokama ochish va kommentlar ko'paytirish. Engagement oshirish — algoritmda ko'tarilish.",
                'hashtag_seeds' => ['savol', 'poll', 'tanlov', 'fikringiz', 'talim'],
                'pain_text' => "Qanday yo'nalish tanlashda ikkilanmoqda",
            ],
            [
                'topic' => "Dialog formati — o'quvchi bilan suhbat",
                'category' => 'engagement',
                'content_type' => 'post',
                'hooks' => [
                    "— Men bu kasbni o'rganmoqchiman, lekin...\n— Lekin nima?\n— Kech bo'ldi deb o'ylayman",
                    "— Menga yozing: nima sababdan kursga yozilmadingiz?\n— Men...",
                    "— Qancha maosh olasiz?\n— ... so'm\n— Shu bilan qanoatsizmi?",
                ],
                'description_template' => "Dialog/suhbat formatida engagement post. O'quvchi o'zini taniydigan vaziyat. Izohda javob berishga undash.",
                'hashtag_seeds' => ['dialog', 'suhbat', 'oqish', 'kasb', 'engagement'],
                'pain_text' => "O'zgarishdan qo'rqadi, vaqti yo'q deb o'ylaydi",
            ],
            // --- BEHIND SCENES: Ichki jarayon ---
            [
                'topic' => "Dars jarayoni — ichkaridan ko'ring",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => [
                    "Darsda nima bo'ladi? Ichkaridan ko'ring!",
                    "O'quvchilarimiz aslida nima o'rganadi — real kadr",
                    "Bu 30 sekunddagi video kursimiz haqida hamma narsani aytadi",
                ],
                'description_template' => "Dars jarayonini real ko'rsatish. Ishonch qozonish — ochiqlik va shaffoflik. O'quvchilarning faol ishtirokini ko'rsatish.",
                'hashtag_seeds' => ['behindthescenes', 'dars', 'kurs', 'oqish', 'talim'],
                'pain_text' => "Kurs sifati haqida shubha bor",
            ],
            [
                'topic' => "O'qituvchi/mentor bilan tanishish",
                'category' => 'behind_scenes',
                'content_type' => 'carousel',
                'hooks' => [
                    "Sizni o'qitadigan inson — ... yillik tajriba, ... ta o'quvchi",
                    "Bu inson o'z hayotini ... ga bag'ishlagan",
                    "Mentordan bir savol: 'Nega hali boshlamadingiz?'",
                ],
                'description_template' => "O'qituvchi/mentor haqida — tajriba, natijalar, o'quvchilar soni. Attractive Character (DotCom Secrets): ishonchli, tajribali, natijaga olib boradigan.",
                'hashtag_seeds' => ['mentor', 'oqituvchi', 'tajriba', 'professional', 'talim'],
                'pain_text' => "Kim o'qitadi va sifati haqida ishonchi yo'q",
            ],
            // --- SELL: Kurs sotish uchun target reklama ---
            [
                'topic' => "Target reklama — kursga yozilish",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "Ishsizmisiz? Oyiga ... so'm topadigan kasb o'rganmoqchimisiz?",
                    "Maoshingiz yetmayaptimi? Qo'shimcha daromad yo'lini o'rganamiz",
                    "... sohasida kasb o'rganib, 3 oyda ishga kirgan o'quvchilarimiz bor",
                ],
                'description_template' => "Star/Story/Solution formulasi: Muammo → Og'riq kuchaytirish → Yechim → Dalil → Taklif → Urgency → CTA. Raqamlar va natijalar bilan.",
                'hashtag_seeds' => ['kurs', 'kasb', 'daromad', 'talim', 'ishga'],
                'pain_text' => "Ishsiz yoki kam maosh, yangi kasb o'rganmoqchi",
            ],
            [
                'topic' => "Vebinar/Master-klass taklifi",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "BEPUL MASTER-KLASS: ... ni 1 soatda o'rganasiz!",
                    "Bepul vebinar — ... sohasida ishlash sirlarini ochamiz",
                    "Faqat ... ta joy — bepul darsga yoziling!",
                ],
                'description_template' => "Bepul vebinar yoki master-klass reklama. DotCom Secrets: Invisible Webinar model. Value berish → Asosiy kursga ko'prik. Scarcity: joy cheklangan.",
                'hashtag_seeds' => ['vebinar', 'bepul', 'masterklass', 'talim', 'online'],
                'pain_text' => "Biror narsani o'rganmoqchi, lekin pullik kursga ishonmaydi",
            ],
            [
                'topic' => "Kurs dasturi va imkoniyatlar",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => [
                    "Kursda nima o'rganasiz? To'liq dastur — shu yerda",
                    "3 oy ichida noldan ... gacha — mana shu yo'l xaritasi",
                    "Bu kursning boshqalardan farqi nimada? 5 ta sabab",
                ],
                'description_template' => "Kurs dasturi batafsil. Har bir modul nima berishi. Big Domino: 'Bu kurs bilan siz ham ... ga erishasiz'. Dalillar va raqamlar.",
                'hashtag_seeds' => ['kurs', 'dastur', 'oqish', 'talim', 'kasboquv'],
                'pain_text' => "Kurs haqida batafsil bilmoqchi, nima o'rgatiladi",
            ],
        ];
    }

    // ================================================================
    // DEFAULT / UMUMIY BIZNES TOPICS
    // DotCom Secrets + Professional ssenariylar asosida
    // ================================================================
    private function getDefaultTopics(): array
    {
        return [
            // --- PROMOTIONAL: Sotish va taklif ---
            [
                'topic' => "Maxsus taklif — cheklangan vaqt, katta foyda",
                'category' => 'promotional',
                'content_type' => 'ad',
                'hooks' => [
                    "Bu taklifni o'tkazib yuborsangiz, afsuslanasiz! Faqat ... gacha!",
                    "Narxni ko'rib ishonmaysiz — lekin bu HAQIQAT! Maxsus taklif",
                    "Birinchi ... ta mijozga MAXSUS NARX — hoziroq bog'laning!",
                ],
                'description_template' => "Star/Story/Solution: Muammo (mijoz og'rig'i) → Hikoya (boshqa mijoz tajribasi) → Yechim (bizning taklif) → Value stack (asosiy + bonuslar) → Urgency (muddat/joy cheklangan) → CTA.",
                'hashtag_seeds' => ['maxsustaklif', 'chegirma', 'aksiya', 'biznes', 'taklif'],
                'pain_text' => "Xizmat/mahsulotga ehtiyoji bor, lekin narx yoki ishonchsizlik to'xtatyapti",
            ],
            [
                'topic' => "Yangilik e'loni — yangi xizmat yoki mahsulot",
                'category' => 'promotional',
                'content_type' => 'reel',
                'hooks' => [
                    "YANGILIK! Bu xizmatni ko'p kutgansiz — endi bizda!",
                    "Birinchi bo'lib sinab ko'ring — yangi mahsulotimiz tayyor!",
                    "Bu haqda ko'p so'rashdi — endi rasman e'lon qilamiz!",
                ],
                'description_template' => "Yangi xizmat/mahsulot e'loni. Hook → Nima yangi → Qanday foyda beradi → Kim uchun → Scarcity (birinchi N kishiga maxsus narx) → CTA.",
                'hashtag_seeds' => ['yangilik', 'newproduct', 'elon', 'biznes', 'xizmat'],
                'pain_text' => "Bu xizmat/mahsulotni qidirgan, lekin topilmagan edi",
            ],
            [
                'topic' => "Chegirma va aksiya — cheklangan muddatli taklif",
                'category' => 'promotional',
                'content_type' => 'post',
                'hooks' => [
                    "AKSIYA BOSHLANDI! Bu narxlarda endi bo'lmaydi — faqat shu hafta!",
                    "Chegirma foizi sizni hayratda qoldiradi — ko'ring!",
                    "Do'stingizga ham ayting — bu imkoniyatni birga qo'lga oling!",
                ],
                'description_template' => "Urgency + Scarcity formulasi: Taklif → Qancha chegirma → Muddat cheklangan → Joy cheklangan → CTA. DotCom Secrets: Deadline funnel prinsipi.",
                'hashtag_seeds' => ['aksiya', 'chegirma', 'sale', 'maxsustaklif', 'cheklangan'],
                'pain_text' => "Xarid qilmoqchi, lekin eng yaxshi vaqtni kutmoqda",
            ],
            // --- TESTIMONIAL: Mijoz natijalari ---
            [
                'topic' => "Mijoz muvaffaqiyat hikoyasi — real natija",
                'category' => 'testimonial',
                'content_type' => 'carousel',
                'hooks' => [
                    "Bu mijozimiz 0 dan boshlagan edi — hozir natijasini ko'ring!",
                    "'Menga yordam bermaydi' degan edi. Natijani o'zi gapiradi!",
                    "Bu hikoyani o'qib, siz ham harakat qilishni xohlaysiz!",
                ],
                'description_template' => "Epiphany Bridge hikoya: Mijozning oldingi holati → muammosi → bizga kelishi → aha-moment → natijasi raqamlarda. DotCom Secrets: Third-person story — o'quvchi o'zini taniydigan.",
                'hashtag_seeds' => ['natija', 'muvaffaqiyat', 'mijoz', 'hikoya', 'transformation'],
                'pain_text' => "Xizmat/mahsulot haqiqatan ishlashiga ishonmaydi",
            ],
            [
                'topic' => "Mijoz sharhi — haqiqiy fikr, filtrsiz",
                'category' => 'testimonial',
                'content_type' => 'post',
                'hooks' => [
                    "Mijozimiz nima deydi? Haqiqiy sharh — filtrsiz, tahrirsiz!",
                    "'Eng yaxshi qarorim shu edi' — mijozimiz so'zlari",
                    "Bu sharh bizni juda xursand qildi — o'qing va o'zingiz baho bering!",
                ],
                'description_template' => "Mijoz testimoniali. Third-person story: kim edi → qanday muammo bor edi → bizga keldi → natija. Social proof sifatida ishonch qozonish. Raqamlar va emotsiya bilan.",
                'hashtag_seeds' => ['sharh', 'review', 'mijoz', 'ishonch', 'natija'],
                'pain_text' => "Boshqa mijozlar tajribasi bilmaydi, qaror qabul qila olmaydi",
            ],
            // --- EDUCATIONAL: Foydali ma'lumot ---
            [
                'topic' => "Sohadagi eng keng tarqalgan xatolar — 90% qiladi",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => [
                    "90% odam shu xatolarni qiladi — siz ham! To'xtating!",
                    "Bu 5 ta xatoni bilsangiz, muammolarning yarmi hal bo'ladi",
                    "Hech kim aytmaydi, lekin bu xatolar sizga qimmatga tushyapti!",
                ],
                'description_template' => "Problem → Agitation → Solution: Keng tarqalgan xatolar → oqibatlari (pul/vaqt yo'qotish) → to'g'ri yondashuv. DotCom Secrets: Ekspert ishonchi qozonish → keyin sotish.",
                'hashtag_seeds' => ['xatolar', 'maslahat', 'tips', 'biznes', 'foydali'],
                'pain_text' => "Muammosi bor, lekin sababi xato qilayotganini bilmaydi",
            ],
            [
                'topic' => "Qanday ishlaydi — xizmatimizni oddiy tilda tushuntiramiz",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => [
                    "1 daqiqada tushunasiz — xizmatimiz QANDAY ishlaydi!",
                    "Ko'pchilik tushunmaydi — biz ODDIY TILDA tushuntiramiz!",
                    "3 bosqich — tamom! Bizning xizmat shu qadar oson",
                ],
                'description_template' => "Problem → Solution format: Murakkab narsani oddiy tilda tushuntirish. Barcha to'siqlarni olib tashlash — 'Bu menga tushunarli emas' degan ishonchni buzish. Big Domino.",
                'hashtag_seeds' => ['qanday', 'howto', 'tushuntirish', 'oson', 'xizmat'],
                'pain_text' => "Xizmat qanday ishlashini tushunmaydi, shuning uchun buyurtma bermaydi",
            ],
            [
                'topic' => "Foydali maslahat — bugun qo'llang, ertaga natija ko'ring",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => [
                    "Bu maslahatni hech kim bepul bermaydi — biz beramiz! Saqlang!",
                    "Bugun qo'llasangiz, ertaga natija ko'rasiz — 5 ta amaliy maslahat",
                    "Bu oddiy usulni bilsangiz, ... muammosi butunlay hal bo'ladi",
                ],
                'description_template' => "Bepul amaliy maslahat. Value berish → Ekspert ishonchi → Saqlash va share uchun format. DotCom Secrets: Bepul qiymat berib, katta sotuvga yo'l ochish.",
                'hashtag_seeds' => ['maslahat', 'tips', 'foydali', 'bepul', 'amaliy'],
                'pain_text' => "Muammosiga amaliy yechim qidirmoqda, lekin ishonchli manba yo'q",
            ],
            // --- ENGAGEMENT: Faollik oshirish ---
            [
                'topic' => "Savol-javob — savolingizga javob beramiz",
                'category' => 'engagement',
                'content_type' => 'story',
                'hooks' => [
                    "Savolingiz bormi? Izohda yozing — BARCHASIGA javob beramiz!",
                    "Eng ko'p beriladigan 3 ta savolga javob — 4-chisini SIZ bering!",
                    "Savol-javob vaqti! Nima qiziqtiradi? Yozing!",
                ],
                'description_template' => "Dialog formati. Mijozlarning real savollariga javob berish → O'quvchini izoh yozishga undash → Engagement oshadi → Algoritmda ko'tarilish. DotCom Secrets: Micro-commitment.",
                'hashtag_seeds' => ['savol', 'faq', 'javob', 'dialog', 'maslahat'],
                'pain_text' => "Savollariga javob topmaydi, kimdan so'rashini bilmaydi",
            ],
            [
                'topic' => "Poll va tanlov — fikringizni bildiring",
                'category' => 'engagement',
                'content_type' => 'story',
                'hooks' => [
                    "Qaysi birini tanlaysiz? A yoki B? Izohda yozing!",
                    "ENG QIYIN TANLOV — faqat 1 tasini tanlang!",
                    "Siz qaysi tarafdamiz? Javob bering — eng yaxshisini hikoyaga chiqaramiz!",
                ],
                'description_template' => "Poll/tanlov formati. 2 ta vizual variant — muhokama ochish. Engagement oshirish, algoritmda ko'tarilish. Micro-commitment: kichik ishtirok → katta qarorga olib boradi.",
                'hashtag_seeds' => ['poll', 'tanlov', 'fikringiz', 'savol', 'engagement'],
                'pain_text' => "O'ziga mos variantni tanlashda ikkilanadi",
            ],
            [
                'topic' => "Dialog formati — mijoz bilan suhbat",
                'category' => 'engagement',
                'content_type' => 'post',
                'hooks' => [
                    "— Menga kerak, lekin...\n— Lekin nima?\n— Qimmat deb o'ylayman",
                    "— Nima uchun hali boshlamadingiz?\n— Men...\n— Sababni aytamiz!",
                    "— Bu haqiqatan ishlaydi deb o'ylaysizmi?\n— Natijalarni ko'ring!",
                ],
                'description_template' => "Dialog/suhbat formatida engagement post. Mijoz o'zini taniydigan vaziyat. Izohda javob berishga undash. DotCom Secrets: False belief buzish — 'qimmat/ishlamaydi' degan ishonchni yo'qotish.",
                'hashtag_seeds' => ['dialog', 'suhbat', 'mijoz', 'savol', 'engagement'],
                'pain_text' => "Qaror qabul qila olmaydi, ichki to'siqlar bor",
            ],
            // --- BEHIND SCENES: Ichki jarayon ---
            [
                'topic' => "Jamoamiz — kim nima qiladi, qanday ishlaymiz",
                'category' => 'behind_scenes',
                'content_type' => 'carousel',
                'hooks' => [
                    "Sizga xizmat ko'rsatadigan jamoa — har biri o'z sohasining ustasi!",
                    "Bu professional jamoaga ishonasiz — tajriba va natijalar guvoh!",
                    "Jamoamiz: ... ta mutaxassis, ... yillik tajriba, ... ta mamnun mijoz!",
                ],
                'description_template' => "Jamoa tanishish. Attractive Character (DotCom Secrets): har bir a'zoning backstory, tajribasi, natijasi. Har bir slaydda — ism, lavozim, tajriba, muvaffaqiyat. Ishonch va bog'lanish.",
                'hashtag_seeds' => ['jamoa', 'team', 'professional', 'meettheteam', 'ishonch'],
                'pain_text' => "Kim xizmat ko'rsatishini bilmaydi, jamoaga ishonchi yo'q",
            ],
            [
                'topic' => "Ish jarayoni ichidan — qanday ishlaymiz",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => [
                    "Hech kim ko'rmagan jarayonni ko'rsatamiz — biz QANDAY ishlaymiz!",
                    "30 sekundda ish jarayonimiz — professionallik shu yerdan boshlanadi!",
                    "Bu video biznes haqida hamma narsani aytadi — ko'ring!",
                ],
                'description_template' => "Ish jarayonini real ko'rsatish. Shaffoflik → ishonch qozonish. DotCom Secrets: Attractive Character — kompaniya emas, odamlar ishlaydi degan his yaratish.",
                'hashtag_seeds' => ['behindthescenes', 'jarayon', 'ishonch', 'professional', 'shaffoflik'],
                'pain_text' => "Sifat va jarayon haqida ishonchi yo'q, ichkarini ko'rmoqchi",
            ],
        ];
    }
}
