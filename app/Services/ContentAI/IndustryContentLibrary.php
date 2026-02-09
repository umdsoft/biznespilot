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
            default => $this->getDefaultTopics(),
        };
    }

    // ================================================================
    // BEAUTY SALON TOPICS
    // ================================================================
    private function getBeautyTopics(): array
    {
        return [
            [
                'topic' => "Soch parvarishi bo'yicha 5 ta professional maslahat",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => ["90% ayollar shu xatolarni qiladi!", "Sochingiz to'kilayaptimi? Sababini aytamiz"],
                'description_template' => "Professional soch parvarishi bo'yicha amaliy maslahatlar. {industry} mutaxassislari tajribasi asosida.",
                'hashtag_seeds' => ['sochparvarishi', 'haircare', 'beautytips', 'tashkent'],
            ],
            [
                'topic' => "Mijoz transformatsiyasi — oldin va keyin natija",
                'category' => 'testimonial',
                'content_type' => 'carousel',
                'hooks' => ["Bu o'zgarishga ishonmaysiz!", "3 soatda butunlay yangi qiyofa"],
                'description_template' => "Mijozimizning ajoyib o'zgarishi. Natijani o'zingiz ko'ring!",
                'hashtag_seeds' => ['transformation', 'beforeafter', 'beauty', 'salon'],
            ],
            [
                'topic' => "Masterimiz qanday ishlaydi — jarayon",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => ["Hech kim ko'rmagan jarayonni ko'rsatamiz", "Master qo'li sehrmi?"],
                'description_template' => "Salon ichidagi professional ish jarayoni. Siz ham bu natijani olishingiz mumkin!",
                'hashtag_seeds' => ['behindthescenes', 'salonlife', 'hairstylist'],
            ],
            [
                'topic' => "Uy sharoitida teri parvarishi — oddiy qadamlar",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => ["Uyda salon natijasiga erishing!", "5 daqiqalik parvarish — ajoyib natija"],
                'description_template' => "Uyda qo'llasa bo'ladigan oddiy, lekin samarali parvarish qadamlari.",
                'hashtag_seeds' => ['skincare', 'teriparvarishi', 'homecare'],
            ],
            [
                'topic' => "Eng ko'p so'raladigan savollar — mutaxassis javoblari",
                'category' => 'engagement',
                'content_type' => 'post',
                'hooks' => ["Siz ham shu savolni berasizmi?", "Eng ko'p beriladigan 3 ta savol"],
                'description_template' => "Mijozlarimiz eng ko'p beriladigan savollarga professional javoblar.",
                'hashtag_seeds' => ['faq', 'beautytips', 'maslahat'],
            ],
            [
                'topic' => "Maxsus taklif — haftalik aksiya",
                'category' => 'promotional',
                'content_type' => 'post',
                'hooks' => ["Faqat shu hafta amal qiladi!", "Bu narxlarda oxirgi marta!"],
                'description_template' => "Maxsus chegirma va takliflar — bugun qo'ng'iroq qiling!",
                'hashtag_seeds' => ['aksiya', 'chegirma', 'offer', 'salon'],
            ],
            [
                'topic' => "2026 yil trend — eng mashhur uslublar",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => ["Bu yilgi eng top trendlar!", "Qaysi birini sinab ko'rgan bo'lsangiz?"],
                'description_template' => "Eng so'nggi trend va uslublar. Qaysi biri sizga yoqadi?",
                'hashtag_seeds' => ['trend2026', 'fashiontrend', 'hairstyle'],
            ],
            [
                'topic' => "Yangi xizmat/mahsulot haqida",
                'category' => 'promotional',
                'content_type' => 'reel',
                'hooks' => ["Yangilik! Buni kutgan bo'lsangiz kerak", "Birinchi bo'lib sinab ko'ring!"],
                'description_template' => "Yangi xizmatimiz haqida batafsil ma'lumot.",
                'hashtag_seeds' => ['newservice', 'yangilik', 'beauty'],
            ],
            [
                'topic' => "Jamoamiz bilan tanishing",
                'category' => 'behind_scenes',
                'content_type' => 'post',
                'hooks' => ["Siz ishongan masterlarimiz bilan tanishing", "Har biri o'z sohasining professionali"],
                'description_template' => "Professional jamoamiz — tajriba va sifat kafolati.",
                'hashtag_seeds' => ['team', 'professionals', 'salon'],
            ],
            [
                'topic' => "To'g'ri vositani qanday tanlash — ekspert maslahati",
                'category' => 'educational',
                'content_type' => 'story',
                'hooks' => ["Ko'pchilik noto'g'ri vosita ishlatadi!", "Sizning teri/soch tipingizga mosini aytamiz"],
                'description_template' => "Teri va soch tipingizga mos vosita tanlash bo'yicha maslahat.",
                'hashtag_seeds' => ['beautyproducts', 'maslahat', 'tanlash'],
            ],
        ];
    }

    // ================================================================
    // RESTAURANT / FOOD TOPICS
    // ================================================================
    private function getRestaurantTopics(): array
    {
        return [
            [
                'topic' => "Taomimiz qanday tayyorlanadi — oshpaz sirlari",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => ["Oshxona ichidagi sirlarni ochamiz!", "Bu taomni ko'rib, och qolasiz"],
                'description_template' => "Oshpazimiz eng mashhur taomni tayyorlash jarayoni. {industry} sirlari!",
                'hashtag_seeds' => ['oshpaz', 'recipe', 'foodie', 'tashkentfood'],
            ],
            [
                'topic' => "Eng mashhur taomimiz — nima uchun uni tanlashadi?",
                'category' => 'promotional',
                'content_type' => 'reel',
                'hooks' => ["Eng ko'p buyurtma qilinadigan taomimiz!", "Buni tatib ko'rmasangiz, ko'p narsa yo'qotasiz"],
                'description_template' => "Mijozlar eng sevgan taomimiz haqida. Hali tatib ko'rmaganmisiz?",
                'hashtag_seeds' => ['bestdish', 'foodlover', 'restoran', 'tashkent'],
            ],
            [
                'topic' => "Uyda tayyorlasa bo'ladigan oddiy retsept",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => ["Bu retseptni uyda sinab ko'ring!", "3 ta ingredient — ajoyib ta'm"],
                'description_template' => "Oddiy retsept — uyda professional darajada tayyorlang!",
                'hashtag_seeds' => ['recipe', 'retsept', 'homecooking', 'easyrecipe'],
            ],
            [
                'topic' => "Mijoz fikri — taom haqida haqiqiy baho",
                'category' => 'testimonial',
                'content_type' => 'post',
                'hooks' => ["Mijozimiz nima deydi?", "Haqiqiy fikr — filtrsiz"],
                'description_template' => "Mijozlarimizning chinakam fikrlari. Ishonch — bizning asosiy qadriyatimiz.",
                'hashtag_seeds' => ['review', 'customerreview', 'foodreview'],
            ],
            [
                'topic' => "Qaysi taomni tanlaysiz? Savol-javob",
                'category' => 'engagement',
                'content_type' => 'story',
                'hooks' => ["A yoki B? Siz qaysi birini tanlaysiz?", "Eng qiyin tanlov!"],
                'description_template' => "Sizning tanlovingiz qanday? Commentda yozing!",
                'hashtag_seeds' => ['poll', 'foodchoice', 'tanlov'],
            ],
            [
                'topic' => "Oilaviy tushlik/kechki ovqat taklifi",
                'category' => 'promotional',
                'content_type' => 'carousel',
                'hooks' => ["Oilangiz bilan mazali dam oling!", "Bolalar uchun maxsus menyu bor!"],
                'description_template' => "Oilangiz bilan birga kelishingizni kutamiz! Maxsus takliflar.",
                'hashtag_seeds' => ['familydining', 'oilaviytaom', 'weekend', 'restoran'],
            ],
            [
                'topic' => "Mavsumiy yangilik — yangi menyu",
                'category' => 'promotional',
                'content_type' => 'reel',
                'hooks' => ["Yangi menyu keldi!", "Buni kutganmisiz?"],
                'description_template' => "Yangi mavsumiy taomlarimiz bilan tanishing!",
                'hashtag_seeds' => ['newmenu', 'seasonal', 'yangilik'],
            ],
            [
                'topic' => "Oziq-ovqat sifati va ingredientlar haqida",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => ["Biz faqat toza mahsulot ishlatamiz!", "Sifat — bizning ustuvorimiz"],
                'description_template' => "Biz ishlatiladigan mahsulotlar haqida. Sifat va halollik.",
                'hashtag_seeds' => ['quality', 'freshfood', 'sifat', 'halol'],
            ],
            [
                'topic' => "Yetkazib berish xizmati — qanday buyurtma qilish",
                'category' => 'educational',
                'content_type' => 'post',
                'hooks' => ["Uyingizga yetkazib beramiz!", "3 bosqichda buyurtma bering"],
                'description_template' => "Yetkazib berish xizmati haqida batafsil. Tez va qulay!",
                'hashtag_seeds' => ['delivery', 'yetkazibberish', 'order'],
            ],
            [
                'topic' => "Restoran muhiti va atmosfera",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => ["Bu yerda vaqt o'tkazishni yaxshi ko'rasiz!", "Qulay muhit — mazali taom"],
                'description_template' => "Restoranimizning qulay muhiti va atmosferasi.",
                'hashtag_seeds' => ['atmosphere', 'cozy', 'restaurant', 'interior'],
            ],
        ];
    }

    // ================================================================
    // E-COMMERCE / ONLINE SAVDO TOPICS
    // ================================================================
    private function getEcommerceTopics(): array
    {
        return [
            [
                'topic' => "Mahsulotni to'g'ri tanlash bo'yicha qo'llanma",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => ["Xarid qilishdan oldin buni bilishingiz shart!", "To'g'ri tanlashning 5 ta qoidasi"],
                'description_template' => "Onlayn xaridda xato qilmaslik uchun amaliy maslahatlar.",
                'hashtag_seeds' => ['onlineshopping', 'onlinesavdo', 'maslahat', 'tanlash'],
            ],
            [
                'topic' => "Mijoz sharhi — haqiqiy tajriba",
                'category' => 'testimonial',
                'content_type' => 'post',
                'hooks' => ["Mijozimiz nima deydi?", "Haqiqiy sharh — filtrsiz"],
                'description_template' => "Mijozlarimizning real sharhlarini o'qing.",
                'hashtag_seeds' => ['review', 'sharh', 'customer', 'real'],
            ],
            [
                'topic' => "Buyurtma jarayoni — qanday ishlaydi",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => ["1 daqiqada buyurtma bering!", "Juda oddiy!"],
                'description_template' => "Buyurtma berish jarayoni — bosqichma-bosqich ko'rsatamiz.",
                'hashtag_seeds' => ['howtoorder', 'buyurtma', 'easy'],
            ],
            [
                'topic' => "TOP-5 eng ko'p sotilgan mahsulot",
                'category' => 'promotional',
                'content_type' => 'carousel',
                'hooks' => ["Eng mashhur 5 ta mahsulot!", "Barchasini ko'rib chiqing"],
                'description_template' => "Mijozlar eng ko'p tanlaydigan mahsulotlarimiz. Siz qaysi birini olgan bo'lardingiz?",
                'hashtag_seeds' => ['bestseller', 'top5', 'mahsulot'],
            ],
            [
                'topic' => "Qadoqlash jarayoni — har bir buyurtma bilan g'amxo'rlik",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => ["Sizning buyurtmangizni qanday tayyorlaymiz", "Har bir buyurtma sevgi bilan qadoqlanadi"],
                'description_template' => "Buyurtmani tayyorlash jarayoni — sifat va g'amxo'rlik.",
                'hashtag_seeds' => ['packing', 'behindthescenes', 'quality'],
            ],
            [
                'topic' => "Mavsumiy chegirma va aksiya",
                'category' => 'promotional',
                'content_type' => 'post',
                'hooks' => ["Chegirmalar boshlandi!", "Buni qo'ldan boy bermang!"],
                'description_template' => "Maxsus takliflar va chegirmalar — hozir foydalaning!",
                'hashtag_seeds' => ['sale', 'chegirma', 'aksiya', 'discount'],
            ],
            [
                'topic' => "Qaysi rang/o'lcham tanlash kerak — maslahat",
                'category' => 'educational',
                'content_type' => 'story',
                'hooks' => ["Tanlashda xato qilmaslik uchun", "Sizga qaysi biri mos keladi?"],
                'description_template' => "To'g'ri o'lcham va variant tanlash bo'yicha maslahat.",
                'hashtag_seeds' => ['sizeguide', 'tips', 'maslahat'],
            ],
            [
                'topic' => "Yetkazib berish va qaytarish shartlari",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => ["Xavotir olmang — biz kafolatlaymiz!", "Qaytarish juda oson"],
                'description_template' => "Yetkazib berish va qaytarish qoidalarimiz — batafsil.",
                'hashtag_seeds' => ['delivery', 'return', 'guarantee'],
            ],
            [
                'topic' => "Yangi kelgan mahsulotlar — unboxing",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => ["Yangi yuk keldi!", "Birinchi bo'lib ko'ring!"],
                'description_template' => "Yangi mahsulotlar omborga yetib keldi — tez buyurtma bering!",
                'hashtag_seeds' => ['newarrival', 'unboxing', 'yangi'],
            ],
            [
                'topic' => "A yoki B? Qaysi birini tanlaysiz?",
                'category' => 'engagement',
                'content_type' => 'story',
                'hooks' => ["Eng qiyin tanlov!", "Commentda javob bering!"],
                'description_template' => "Ikki mashhur mahsulot — qaysi birini tanlaysiz?",
                'hashtag_seeds' => ['poll', 'thisorthat', 'tanlov'],
            ],
        ];
    }

    // ================================================================
    // RETAIL / DO'KON TOPICS
    // ================================================================
    private function getRetailTopics(): array
    {
        return [
            [
                'topic' => "Mahsulotni qanday to'g'ri saqlash — maslahat",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => ["Ko'pchilik shu xatoni qiladi!", "To'g'ri saqlash = uzoq xizmat"],
                'description_template' => "Mahsulotni to'g'ri saqlash va uzoq ishlatish sirlari.",
                'hashtag_seeds' => ['tips', 'maslahat', 'dokon', 'retail'],
            ],
            [
                'topic' => "Do'konimizga virtual sayohat",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => ["Do'konimiz ichida nima bor?", "Virtual sayohatga taklif qilamiz!"],
                'description_template' => "Do'konimizni ichidan ko'ring — barcha mahsulotlar bir joyda!",
                'hashtag_seeds' => ['storetour', 'behindthescenes', 'retail'],
            ],
            [
                'topic' => "Mijoz baxti — xarid tajribasi",
                'category' => 'testimonial',
                'content_type' => 'post',
                'hooks' => ["Baxtli mijozimiz!", "Bu sharhni o'qib ko'ring"],
                'description_template' => "Mijozlarimizning haqiqiy fikrlari va tajribalari.",
                'hashtag_seeds' => ['review', 'happycustomer', 'sharh'],
            ],
            [
                'topic' => "Haftalik TOP mahsulotlar",
                'category' => 'promotional',
                'content_type' => 'carousel',
                'hooks' => ["Bu hafta eng ko'p sotilganlar!", "Trendda nima bor?"],
                'description_template' => "Bu haftaning eng mashhur mahsulotlari — qaysi birini tanlaysiz?",
                'hashtag_seeds' => ['weekly', 'bestseller', 'trending'],
            ],
            [
                'topic' => "Savol-javob: Eng ko'p beriladigan savollar",
                'category' => 'engagement',
                'content_type' => 'story',
                'hooks' => ["Savollaringizga javob beramiz!", "FAQ — bilishingiz kerak"],
                'description_template' => "Mijozlarimiz eng ko'p beriladigan savollarga javoblar.",
                'hashtag_seeds' => ['faq', 'questions', 'savol'],
            ],
            [
                'topic' => "Yangi yuk keldi — yangiliklar",
                'category' => 'promotional',
                'content_type' => 'reel',
                'hooks' => ["Yangi mahsulotlar keldi!", "Birinchi bo'lib ko'ring"],
                'description_template' => "Yangi mahsulotlar bilan tanishing — tez keling!",
                'hashtag_seeds' => ['newarrival', 'yangi', 'newproduct'],
            ],
            [
                'topic' => "Mahsulot taqqoslash — qaysi birini olish kerak?",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => ["Qaysi birini olish kerak?", "Batafsil taqqoslash"],
                'description_template' => "Ikki mashhur mahsulotni taqqoslaymiz — sizga qaysi biri mos?",
                'hashtag_seeds' => ['comparison', 'taqqoslash', 'review'],
            ],
            [
                'topic' => "Maxsus aksiya — chegirma kunlari",
                'category' => 'promotional',
                'content_type' => 'post',
                'hooks' => ["Aksiya boshlandi!", "Faqat cheklangan vaqt!"],
                'description_template' => "Maxsus chegirmalar — hozir xarid qiling!",
                'hashtag_seeds' => ['sale', 'aksiya', 'chegirma'],
            ],
        ];
    }

    // ================================================================
    // SERVICE / XIZMAT KO'RSATISH TOPICS
    // ================================================================
    private function getServiceTopics(): array
    {
        return [
            [
                'topic' => "Xizmatimiz qanday ishlaydi — oddiy tilda",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => ["1 daqiqada tushunasiz!", "Juda oddiy jarayon"],
                'description_template' => "{industry} xizmati qanday ishlaydi — bosqichma-bosqich tushuntiramiz.",
                'hashtag_seeds' => ['xizmat', 'service', 'howto', 'tushuntirish'],
            ],
            [
                'topic' => "Muvaffaqiyatli loyiha — natijalar bilan",
                'category' => 'testimonial',
                'content_type' => 'carousel',
                'hooks' => ["Bu natijaga ishonmaysiz!", "Real loyiha — real natijalar"],
                'description_template' => "Oxirgi loyihamizning natijalari. Raqamlar o'zi gapiradi!",
                'hashtag_seeds' => ['casestudy', 'results', 'natija', 'portfolio'],
            ],
            [
                'topic' => "Mijoz xatosi — qanday oldini olish mumkin",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => ["Bu xatolarni qilmang!", "90% kishi shu xatoni qiladi"],
                'description_template' => "Eng keng tarqalgan xatolar va ularni qanday tuzatish mumkin.",
                'hashtag_seeds' => ['mistakes', 'xatolar', 'tips', 'maslahat'],
            ],
            [
                'topic' => "Ish jarayoni — sahna ortida",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => ["Biz qanday ishlaymiz?", "Sahna ortidagi jarayon"],
                'description_template' => "Loyihalar ustida ishlash jarayonimiz — professional yondashuv.",
                'hashtag_seeds' => ['behindthescenes', 'workflow', 'process'],
            ],
            [
                'topic' => "Qanday qilib biz bilan ishlash mumkin — 3 bosqich",
                'category' => 'educational',
                'content_type' => 'post',
                'hooks' => ["Hamkorlik boshlash juda oson!", "3 oddiy qadam"],
                'description_template' => "Biz bilan ishlash juda oson — 3 bosqichda tushuntiramiz.",
                'hashtag_seeds' => ['partnership', 'hamkorlik', 'steps'],
            ],
            [
                'topic' => "Soha yangiliklari va trendlar",
                'category' => 'educational',
                'content_type' => 'post',
                'hooks' => ["Buni bilishingiz kerak!", "Sohada nima yangilik?"],
                'description_template' => "Sohamizdagi eng so'nggi yangiliklar va trendlar.",
                'hashtag_seeds' => ['trends', 'news', 'yangilik', 'industry'],
            ],
            [
                'topic' => "Bepul maslahat — savol bering!",
                'category' => 'engagement',
                'content_type' => 'story',
                'hooks' => ["Bugun bepul maslahat beramiz!", "Savolingiz bormi? Yozing!"],
                'description_template' => "Savollaringizga javob beramiz — bu imkoniyatni qo'ldan boy bermang!",
                'hashtag_seeds' => ['free', 'consultation', 'maslahat', 'bepul'],
            ],
            [
                'topic' => "Jamoamiz — kim nima qiladi",
                'category' => 'behind_scenes',
                'content_type' => 'carousel',
                'hooks' => ["Jamoamiz bilan tanishing!", "Har biri o'z sohasining ustasi"],
                'description_template' => "Professional jamoamiz haqida ko'proq bilib oling.",
                'hashtag_seeds' => ['team', 'jamoa', 'professionals', 'meettheteam'],
            ],
            [
                'topic' => "Xizmat paketlari va narxlar",
                'category' => 'promotional',
                'content_type' => 'carousel',
                'hooks' => ["Sizga qaysi paket mos?", "Shaffof narxlar — yashirin to'lovlarsiz"],
                'description_template' => "Xizmat paketlarimiz va narxlar — shaffof va tushunarli.",
                'hashtag_seeds' => ['pricing', 'packages', 'narx', 'xizmat'],
            ],
        ];
    }

    // ================================================================
    // SAAS / DASTURIY TA'MINOT TOPICS
    // ================================================================
    private function getSaasTopics(): array
    {
        return [
            [
                'topic' => "Mahsulotimiz qanday muammoni hal qiladi",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => ["Bu muammo sizga tanishmi?", "Yechim topildi!"],
                'description_template' => "{industry} yordamida bu muammoni qanday hal qilish mumkin.",
                'hashtag_seeds' => ['saas', 'solution', 'yechim', 'tech'],
            ],
            [
                'topic' => "Foydalanuvchi hikoyasi — natijalar",
                'category' => 'testimonial',
                'content_type' => 'carousel',
                'hooks' => ["Ular qanday natijaga erishdi?", "Real foydalanuvchi — real natija"],
                'description_template' => "Foydalanuvchimizning muvaffaqiyat hikoyasi — raqamlar bilan.",
                'hashtag_seeds' => ['casestudy', 'success', 'testimonial'],
            ],
            [
                'topic' => "5 ta maslahat — samaradorlikni oshiring",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => ["Vaqtingizni 50% tejang!", "Professional maslahatlar"],
                'description_template' => "Samaradorlikni oshirish bo'yicha amaliy maslahatlar.",
                'hashtag_seeds' => ['productivity', 'tips', 'samaradorlik'],
            ],
            [
                'topic' => "Yangi funksiya — nima o'zgardi?",
                'category' => 'promotional',
                'content_type' => 'reel',
                'hooks' => ["Yangi funksiya chiqdi!", "Buni kutgansiz!"],
                'description_template' => "Yangi funksiyamiz haqida batafsil — qanday foydalanish mumkin.",
                'hashtag_seeds' => ['newfeature', 'update', 'yangilik'],
            ],
            [
                'topic' => "Dasturni qanday ishlatish — tutorial",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => ["30 sekundda o'rganing!", "Juda oson!"],
                'description_template' => "Bosqichma-bosqich ko'rsatma — hech kim qiynalamaydi!",
                'hashtag_seeds' => ['tutorial', 'howto', 'qollanma'],
            ],
            [
                'topic' => "Soha statistikasi — bilishingiz kerak",
                'category' => 'engagement',
                'content_type' => 'post',
                'hooks' => ["Bu raqamlar sizni hayratda qoldiradi!", "Sohada nima bo'lyapti?"],
                'description_template' => "Sohadagi muhim statistika va raqamlar.",
                'hashtag_seeds' => ['statistics', 'data', 'insights'],
            ],
            [
                'topic' => "Dasturchi jamoamiz ish jarayoni",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => ["Kod yozish jarayonini ko'ring!", "Sahna ortida"],
                'description_template' => "Dasturchilarimiz qanday ishlaydi — loyiha ichidan.",
                'hashtag_seeds' => ['devlife', 'behindthescenes', 'coding'],
            ],
            [
                'topic' => "Bepul demo — sinab ko'ring",
                'category' => 'promotional',
                'content_type' => 'post',
                'hooks' => ["Bepul sinab ko'ring!", "14 kun bepul!"],
                'description_template' => "Bepul demo versiya — hech qanday majburiyatsiz sinab ko'ring!",
                'hashtag_seeds' => ['freedemo', 'trial', 'bepul'],
            ],
        ];
    }

    // ================================================================
    // FITNESS / SPORT ZAL TOPICS
    // ================================================================
    private function getFitnessTopics(): array
    {
        return [
            [
                'topic' => "Uyda bajariladigan 5 ta mashq",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => ["Zalga bormasdan shakl oling!", "5 daqiqalik mashq — katta natija"],
                'description_template' => "Uyda bajarsa bo'ladigan oddiy, lekin samarali mashqlar.",
                'hashtag_seeds' => ['homeworkout', 'mashq', 'fitness', 'exercise'],
            ],
            [
                'topic' => "Mijoz transformatsiyasi — 3 oy natijasi",
                'category' => 'testimonial',
                'content_type' => 'carousel',
                'hooks' => ["3 oyda bu natijaga erishdi!", "Oldin va keyin — ishoning!"],
                'description_template' => "Mijozimizning ajoyib transformatsiyasi — mehnat va sabr bilan!",
                'hashtag_seeds' => ['transformation', 'beforeafter', 'results', 'fitness'],
            ],
            [
                'topic' => "To'g'ri ovqatlanish — oddiy maslahatlar",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => ["Mashq 30% — ovqatlanish 70%!", "Bu xatolarni qilmang"],
                'description_template' => "To'g'ri ovqatlanish asoslari — oddiy va amaliy maslahatlar.",
                'hashtag_seeds' => ['nutrition', 'ovqatlanish', 'healthyfood', 'diet'],
            ],
            [
                'topic' => "Trener bilan mashg'ulot jarayoni",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => ["Trener bilan ishlash qanday?", "Mashg'ulot ichidan"],
                'description_template' => "Professional trener bilan shaxsiy mashg'ulot jarayoni.",
                'hashtag_seeds' => ['personaltrainer', 'training', 'gym', 'trener'],
            ],
            [
                'topic' => "Eng keng tarqalgan mashq xatolari",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => ["Bu xato jarohatga olib keladi!", "To'g'ri texnikani o'rganing"],
                'description_template' => "Mashq qilishda eng ko'p qilinadigan xatolar va to'g'ri texnika.",
                'hashtag_seeds' => ['mistakes', 'form', 'technique', 'safety'],
            ],
            [
                'topic' => "Haftalik challenge — qo'shiling!",
                'category' => 'engagement',
                'content_type' => 'story',
                'hooks' => ["7 kunlik challenge boshlandi!", "Siz ham qo'shilasizmi?"],
                'description_template' => "Haftalik fitness challenge — barcha darajadagi odamlar uchun!",
                'hashtag_seeds' => ['challenge', 'fitnesschallenge', 'motivation'],
            ],
            [
                'topic' => "Zal muhiti va jihozlar",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => ["Zalimiz ichini ko'ring!", "Eng zamonaviy jihozlar"],
                'description_template' => "Sport zalimizning zamonaviy jihozlari va qulay muhiti.",
                'hashtag_seeds' => ['gymtour', 'equipment', 'gym'],
            ],
            [
                'topic' => "Maxsus paket — chegirma bilan boshlang",
                'category' => 'promotional',
                'content_type' => 'post',
                'hooks' => ["Hozir boshlash eng yaxshi vaqt!", "Maxsus narxlar — faqat shu hafta"],
                'description_template' => "Maxsus takliflar — sport hayotni hozir boshlang!",
                'hashtag_seeds' => ['offer', 'promo', 'gym', 'fitnessoffer'],
            ],
            [
                'topic' => "Motivatsiya — maqsadga erishing",
                'category' => 'engagement',
                'content_type' => 'post',
                'hooks' => ["Bugun boshlang — ertaga shukr qilasiz!", "Siz buni qila olasiz!"],
                'description_template' => "Motivatsion xabar — har kuni bir qadam oldinga!",
                'hashtag_seeds' => ['motivation', 'fitness', 'goals', 'nevergiveup'],
            ],
        ];
    }

    // ================================================================
    // DEFAULT / UMUMIY BIZNES TOPICS
    // ================================================================
    private function getDefaultTopics(): array
    {
        return [
            [
                'topic' => "Mijozlar eng ko'p beriladigan savollar va javoblar",
                'category' => 'educational',
                'content_type' => 'carousel',
                'hooks' => ["Siz ham shu savolni berasizmi?", "Eng ko'p so'raladigan 5 ta savol"],
                'description_template' => "Mijozlarimiz eng ko'p beriladigan savollarga professional javoblar.",
                'hashtag_seeds' => ['faq', 'savol', 'maslahat', 'biznes'],
            ],
            [
                'topic' => "Mijoz hikoyasi — muvaffaqiyat natijasi",
                'category' => 'testimonial',
                'content_type' => 'carousel',
                'hooks' => ["Bu natijaga ishonmaysiz, lekin haqiqat!", "Mijozimiz nima deydi?"],
                'description_template' => "Mijozimizning haqiqiy tajribasi va natijalari.",
                'hashtag_seeds' => ['testimonial', 'results', 'customer'],
            ],
            [
                'topic' => "Jamoamiz qanday ishlaydi — sahna ortida",
                'category' => 'behind_scenes',
                'content_type' => 'reel',
                'hooks' => ["Hech kim ko'rmagan jarayonni ko'rsatamiz", "Sahna ortida nima bor?"],
                'description_template' => "Biznes jarayonimiz ichidan — professional yondashuv.",
                'hashtag_seeds' => ['behindthescenes', 'teamwork', 'business'],
            ],
            [
                'topic' => "Sohadagi eng keng tarqalgan xatolar",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => ["90% odam shu xatoni qiladi!", "Bu xatolarni qilmang"],
                'description_template' => "Eng ko'p uchraydigan xatolar va ularni qanday oldini olish mumkin.",
                'hashtag_seeds' => ['mistakes', 'xatolar', 'tips'],
            ],
            [
                'topic' => "Xizmat/mahsulotimiz qanday ishlaydi — oddiy tilda",
                'category' => 'educational',
                'content_type' => 'reel',
                'hooks' => ["1 daqiqada tushunasiz!", "Juda oddiy jarayon"],
                'description_template' => "Xizmatimiz qanday ishlaydi — tushunarli tilda tushuntiramiz.",
                'hashtag_seeds' => ['howto', 'qollanma', 'xizmat'],
            ],
            [
                'topic' => "Haftalik amaliy maslahat",
                'category' => 'engagement',
                'content_type' => 'post',
                'hooks' => ["Bugun sinab ko'rsangiz, ertaga natija ko'rasiz!", "Amaliy maslahat"],
                'description_template' => "Haftalik amaliy maslahat — hoziroq qo'llang!",
                'hashtag_seeds' => ['tips', 'maslahat', 'weekly'],
            ],
            [
                'topic' => "Maxsus taklif — faqat shu hafta",
                'category' => 'promotional',
                'content_type' => 'post',
                'hooks' => ["Bu imkoniyatni qo'ldan boy bermang!", "Faqat shu hafta!"],
                'description_template' => "Maxsus taklif va chegirmalar — hozir foydalaning!",
                'hashtag_seeds' => ['offer', 'special', 'aksiya'],
            ],
            [
                'topic' => "Sizning fikringiz biz uchun muhim — baho bering",
                'category' => 'engagement',
                'content_type' => 'story',
                'hooks' => ["Fikringizni bildiring!", "Bizni baholang — 1-10"],
                'description_template' => "Mijozlarimiz fikri bizni yanada yaxshilaydi. Baho bering!",
                'hashtag_seeds' => ['feedback', 'fikr', 'review'],
            ],
            [
                'topic' => "Jamoamiz bilan tanishing",
                'category' => 'behind_scenes',
                'content_type' => 'carousel',
                'hooks' => ["Kim nima qiladi?", "Professional jamoamiz bilan tanishing"],
                'description_template' => "Jamoamiz a'zolari haqida ko'proq bilib oling!",
                'hashtag_seeds' => ['team', 'meettheteam', 'jamoa'],
            ],
            [
                'topic' => "Nima uchun bizni tanlashadi — farqimiz nima",
                'category' => 'promotional',
                'content_type' => 'carousel',
                'hooks' => ["Nima uchun aynan biz?", "5 ta sabab — nima uchun bizni tanlashadi"],
                'description_template' => "Mijozlar bizni nima uchun tanlaydi — asosiy sabablar.",
                'hashtag_seeds' => ['whyus', 'quality', 'trust', 'biznes'],
            ],
        ];
    }
}
