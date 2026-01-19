<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import {
    ChevronDownIcon,
    ChevronUpIcon,
    MagnifyingGlassIcon,
    BookmarkIcon,
    PrinterIcon,
    ArrowRightIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    LightBulbIcon,
    StarIcon,
    ExclamationTriangleIcon,
    PhoneIcon,
    ChatBubbleLeftRightIcon,
    ClipboardDocumentIcon,
    PlayIcon,
    PauseIcon,
    ArrowUpIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon as StarSolidIcon, BookmarkIcon as BookmarkSolidIcon, CheckIcon } from '@heroicons/vue/24/solid';

// Search query
const searchQuery = ref('');

// Toast notification
const toast = ref({ show: false, message: '', type: 'success' });
const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => { toast.value.show = false; }, 2500);
};

// Copy to clipboard
const copyToClipboard = async (text, label = 'Skript') => {
    try {
        await navigator.clipboard.writeText(text);
        showToast(`${label} nusxalandi!`, 'success');
    } catch (err) {
        showToast('Nusxalashda xatolik', 'error');
    }
};

// Practice mode
const practiceMode = ref(false);
const currentPracticeIndex = ref(0);
const showAnswer = ref(false);

const togglePracticeMode = () => {
    practiceMode.value = !practiceMode.value;
    currentPracticeIndex.value = 0;
    showAnswer.value = false;
    if (practiceMode.value) {
        expandedSections.value.objections = true;
    }
};

const nextPractice = () => {
    showAnswer.value = false;
    currentPracticeIndex.value = (currentPracticeIndex.value + 1) % objections.length;
};

const prevPractice = () => {
    showAnswer.value = false;
    currentPracticeIndex.value = currentPracticeIndex.value === 0 ? objections.length - 1 : currentPracticeIndex.value - 1;
};

// Show only bookmarked
const showOnlyBookmarked = ref(false);

// Scroll to top visibility
const showScrollTop = ref(false);
const handleScroll = () => {
    showScrollTop.value = window.scrollY > 500;
};
const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    // Load bookmarks from localStorage
    const saved = localStorage.getItem('salesArsenalBookmarks');
    if (saved) {
        bookmarkedItems.value = new Set(JSON.parse(saved));
    }
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

// Reactive state for collapsible sections
const expandedSections = ref({
    methods: true,
    psychology: false,
    nlp: false,
    script: false,
    objections: false,
    closing: false,
    followup: false,
});

// Bookmarked items
const bookmarkedItems = ref(new Set());

const toggleBookmark = (id) => {
    if (bookmarkedItems.value.has(id)) {
        bookmarkedItems.value.delete(id);
    } else {
        bookmarkedItems.value.add(id);
    }
    // Save to localStorage
    localStorage.setItem('salesArsenalBookmarks', JSON.stringify([...bookmarkedItems.value]));
};

const toggleSection = (section) => {
    expandedSections.value[section] = !expandedSections.value[section];
};

const expandAll = () => {
    Object.keys(expandedSections.value).forEach(key => {
        expandedSections.value[key] = true;
    });
};

const collapseAll = () => {
    Object.keys(expandedSections.value).forEach(key => {
        expandedSections.value[key] = false;
    });
};

// Stats
const stats = [
    { value: '50+', label: 'Texnika', icon: '⚡' },
    { value: '15', label: "E'tiroz", icon: '🛡️' },
    { value: '12', label: 'Yopish usuli', icon: '🎯' },
    { value: '8', label: 'Metodologiya', icon: '📚' },
    { value: '6', label: 'Bosqich', icon: '📋' },
];

// Methodologies
const methodologies = [
    {
        name: 'SPIN SELLING',
        origin: 'Neil Rackham, 1988',
        icon: '🔄',
        color: 'amber',
        steps: [
            { letter: 'S', title: 'SITUATION (Vaziyat)', desc: '"Hozir qanday yechim ishlatayapsiz?"' },
            { letter: 'P', title: 'PROBLEM (Muammo)', desc: '"Eng katta qiyinchilik nima?"' },
            { letter: 'I', title: 'IMPLICATION (Oqibat)', desc: '"Bu qancha zarar keltiradi?"' },
            { letter: 'N', title: 'NEED-PAYOFF (Foyda)', desc: '"Hal bo\'lsa nima o\'zgaradi?"' },
        ]
    },
    {
        name: 'CHALLENGER SALE',
        origin: 'CEB/Gartner, 2011',
        icon: '⚔️',
        color: 'red',
        steps: [
            { letter: 'T', title: 'TEACH (O\'rgating)', desc: 'Mijozga yangi narsani o\'rgating – insight bering' },
            { letter: 'T', title: 'TAILOR (Moslang)', desc: 'Xabarni mijoz vaziyatiga moslang' },
            { letter: 'T', title: 'TAKE CONTROL (Boshqaring)', desc: 'Suhbatni va jarayonni boshqaring' },
        ]
    },
    {
        name: 'MEDDPICC',
        origin: 'Enterprise Sales Qualification',
        icon: '🎯',
        color: 'blue',
        steps: [
            { letter: 'M', title: 'Metrics', desc: 'Muvaffaqiyat mezonlari – qanday o\'lchanadi?' },
            { letter: 'E', title: 'Economic Buyer', desc: 'Kim pul to\'laydi? Qaror qiluvchi kim?' },
            { letter: 'D', title: 'Decision Criteria', desc: 'Qaror qilish mezonlari nima?' },
            { letter: 'D', title: 'Decision Process', desc: 'Qaror qilish jarayoni qanday?' },
            { letter: 'P', title: 'Paper Process', desc: 'Shartnoma jarayoni qanday?' },
            { letter: 'I', title: 'Identify Pain', desc: 'Asosiy "og\'riq" nuqtasi nima?' },
            { letter: 'C', title: 'Champion', desc: 'Ichki tarafdor kim?' },
            { letter: 'C', title: 'Competition', desc: 'Raqobatchilar kim?' },
        ]
    },
    {
        name: 'SANDLER SUBMARINE',
        origin: 'David Sandler, 1967',
        icon: '🔱',
        color: 'purple',
        steps: [
            { letter: '1', title: 'Bonding & Rapport', desc: 'Ishonch va aloqa o\'rnatish' },
            { letter: '2', title: 'Up-Front Contract', desc: 'Kutilmalar haqida kelishish' },
            { letter: '3', title: 'Pain', desc: '"Og\'riq" ni topish va kuchaytirish' },
            { letter: '4', title: 'Budget', desc: 'Byudjetni aniqlash' },
            { letter: '5', title: 'Decision', desc: 'Qaror jarayonini tushunish' },
            { letter: '6', title: 'Fulfillment', desc: 'Yechimni taqdim etish' },
            { letter: '7', title: 'Post-Sell', desc: '"Buyer\'s remorse" ni oldini olish' },
        ]
    },
    {
        name: 'GAP SELLING',
        origin: 'Keenan, 2018',
        icon: '📊',
        color: 'emerald',
        steps: [
            { letter: '1', title: 'CURRENT STATE', desc: 'Hozirgi vaziyat – muammolar, og\'riqlar' },
            { letter: '⚡', title: 'THE GAP', desc: 'Farq = Motivatsiya = Sotuv imkoniyati' },
            { letter: '2', title: 'FUTURE STATE', desc: 'Kerakli vaziyat – maqsadlar, natijalar' },
        ],
        keyPoint: 'GAP qanchalik katta = Mijoz qanchalik tez va ko\'p to\'laydi'
    },
    {
        name: 'SOLUTION SELLING',
        origin: 'Michael Bosworth, 1988',
        icon: '💡',
        color: 'cyan',
        steps: [
            { letter: '1', title: 'Diagnose', desc: 'Muammoni "doktor" kabi tashxislang' },
            { letter: '2', title: 'Create Vision', desc: 'Yechilgan muammo tasvirini yarating' },
            { letter: '3', title: 'Negotiate Value', desc: 'Qiymat va narx bo\'yicha kelishing' },
        ],
        keyPoint: 'Mahsulot sotmang – YECHIM taklif qiling!'
    },
    {
        name: 'SNAP SELLING',
        origin: 'Jill Konrath, 2012',
        icon: '⚡',
        color: 'yellow',
        steps: [
            { letter: 'S', title: 'SIMPLE', desc: 'Oddiy qiling – murakkablikni kamaytiring' },
            { letter: 'N', title: 'INVALUABLE', desc: 'Qiymatli bo\'ling – ekspert sifatida' },
            { letter: 'A', title: 'ALIGNED', desc: 'Moslang – mijoz maqsadlariga' },
            { letter: 'P', title: 'PRIORITY', desc: 'Ustuvor bo\'ling – hozir kerakligini ko\'rsating' },
        ]
    },
    {
        name: 'CONCEPTUAL SELLING',
        origin: 'Miller Heiman',
        icon: '🎭',
        color: 'pink',
        steps: [
            { letter: '1', title: 'Confirmation Questions', desc: 'Ma\'lumotni tasdiqlash savollari' },
            { letter: '2', title: 'New Information Questions', desc: 'Yangi ma\'lumot olish savollari' },
            { letter: '3', title: 'Attitude Questions', desc: 'Munosabatni aniqlash savollari' },
            { letter: '4', title: 'Commitment Questions', desc: 'Majburiyat olish savollari' },
            { letter: '5', title: 'Basic Issue Questions', desc: 'Asosiy muammolarni aniqlash' },
        ]
    },
];

// Cialdini's 6 Principles
const cialdiniPrinciples = [
    {
        name: 'RECIPROCITY',
        icon: '🎁',
        power: 5,
        desc: 'Biror narsa bepul bersangiz, odam sizga javob qaytarishga majbur his qiladi.',
        examples: [
            '"Sizga bepul audit o\'tkazib beraman."',
            '"Mana bu qimmatbaho material – sovg\'a."',
            '"Sizga maxsus bonus qo\'shaman."'
        ]
    },
    {
        name: 'SCARCITY',
        icon: '⏳',
        power: 5,
        desc: 'Odamlar kam bo\'lgan narsani ko\'proq xohlashadi.',
        examples: [
            '"Bu narx faqat shu hafta."',
            '"Faqat 3 ta o\'rin qoldi."',
            '"Keyingi guruh 2 oydan keyin."'
        ]
    },
    {
        name: 'AUTHORITY',
        icon: '👔',
        power: 4,
        desc: 'Odamlar ekspertlarga ko\'proq ishonadilar.',
        examples: [
            '"Biz 10 yildan beri bu sohada."',
            '"Bizning ISO sertifikati bor."',
            '"[Ekspert] bizni tavsiya qiladi."'
        ]
    },
    {
        name: 'SOCIAL PROOF',
        icon: '👥',
        power: 5,
        desc: 'Odamlar boshqalar qilgan ishni qilishga moyil.',
        examples: [
            '"500+ kompaniya bizni tanlagan."',
            '"O\'tgan hafta 12 kompaniya shu paketni oldi."',
            '"[Kompaniya]: \'Eng yaxshi qaror edi.\'"'
        ]
    },
    {
        name: 'LIKING',
        icon: '😊',
        power: 4,
        desc: 'Odamlar yoqtirgan odamlardan sotib olishadi.',
        examples: [
            '"Men ham [shahar]danman!" (Umumiylik)',
            '"Ajoyib ofis!" (Kompliment)',
            'Mirroring – ovoz tonusini moslashtirish'
        ]
    },
    {
        name: 'COMMITMENT',
        icon: '🤝',
        power: 5,
        desc: 'Kichik "ha"lar katta "ha"ga olib keladi – Yes Ladder.',
        examples: [
            '"Vaqt tejash muhimmi?" – "Ha"',
            '"Pul tejash yaxshimi?" – "Ha"',
            '"Demak, bu siz uchun!"'
        ]
    },
];

// Psychological Techniques
const psychTechniques = [
    { name: 'FOMO', icon: '😰', desc: 'Fear Of Missing Out – imkoniyatni qo\'ldan boy berish qo\'rquvi.', example: '"Raqobatchilaringiz allaqachon ishlatishyapti."' },
    { name: 'LOSS AVERSION', icon: '📉', desc: 'Odamlar yo\'qotishdan topishdan 2× ko\'proq qo\'rqishadi.', example: '"Bu muammo sizga [X] zarar."' },
    { name: 'ANCHORING', icon: '⚓', desc: 'Avval katta raqam ayting – keyin haqiqiy narx arzon ko\'rinadi.', example: '"Boshqalarda 100 mln. Bizda 50 mln."' },
    { name: 'FUTURE PACING', icon: '🔮', desc: 'Mijozni muvaffaqiyatli kelajakni tasavvur qilishga undang.', example: '"Tasavvur qiling: 3 oydan keyin..."' },
    { name: 'PATTERN INTERRUPT', icon: '⚡', desc: 'Mijozning kutilgan javobini buzish – e\'tiborni qayta jalb qilish.', example: '"Ajoyib! Ko\'pchilik ham shunday deydi. Bitta savol:..."' },
    { name: 'REFRAMING', icon: '🖼️', desc: 'Salbiyni ijobiyga aylantirib ko\'rsating.', example: '"Qimmat" → "Bu investitsiya"' },
];

// NLP Techniques
const nlpTechniques = [
    { name: 'ASSUMPTIVE LANGUAGE', icon: '✓', desc: 'Mijoz allaqachon "ha" degan deb faraz qilib gapiring.', example: '❌ "Sotib olasizmi?" → ✅ "Qachon boshlaymiz?"' },
    { name: 'TIE-DOWN QUESTIONS', icon: '🔗', desc: 'Gap oxiriga "shunday emasmi?" qo\'shib, "ha"lar oling.', example: '"Vaqt tejash muhim, shunday emasmi?"' },
    { name: 'MIRRORING', icon: '🪞', desc: 'Mijozning so\'zlarini, tonusini aks ettiring.', example: 'Mijoz: "Vaqt topish qiyin" → Siz: "Ha, vaqt topish qiyin, tushundim..."' },
    { name: 'FEEL-FELT-FOUND', icon: '💡', desc: 'Universal e\'tiroz javob formulasi.', example: '"Siz qimmat deb HIS QILYAPSIZ. Ko\'pchilik ham SHUNDAY HIS QILGAN. Lekin ANIQLAB OLISHDI..."' },
    { name: 'STORYTELLING', icon: '📖', desc: 'Faktlar emas, hikoya ayting – odamlar eslab qoladi.', example: '"O\'tgan oy [Kompaniya] biz bilan bog\'landi..."' },
    { name: 'EMBEDDED COMMANDS', icon: '🎯', desc: 'Gap ichiga yashirin buyruqlar qo\'ying.', example: '"Ko\'pchilik BU YECHIMNI TANLAYDI."' },
];

// All 15 Objections
const objections = [
    {
        id: 'obj1',
        text: '"QIMMAT"',
        color: 'red',
        tags: ['REFRAMING', 'ANCHORING', 'LOSS AVERSION'],
        script: `Tushundim, siz qimmat deb his qilyapsiz.
Ko'pchilik ham avval shunday his qilgan.

Lekin aniqlab olishdi:
• Hozir bu muammoga oyiga [X] sarflaysiz
• Biz bilan [Y] tejaysiz
• 3 oyda investitsiya qaytadi

Bu xarajat emas – investitsiya. Mantiqiymi?`,
        result: '5-BOSQICHga (Yopish)'
    },
    {
        id: 'obj2',
        text: '"O\'YLAB KO\'RAMAN"',
        color: 'orange',
        tags: ['FOMO', 'SCARCITY', 'SCALE CLOSE'],
        script: `Albatta, bu muhim qaror.

ANIQ nima ustida o'ylaysiz?
• Narxmi? – [NARX javob]
• Sifatmi? – [SIFAT javob]
• Boshqa variantmi? – [TAQQOSLASH]

1 dan 10 gacha – qanchalik tayyorsiz?
Nima 10 ga yetkazadi?`,
        result: 'Haqiqiy e\'tirozni topish'
    },
    {
        id: 'obj3',
        text: '"BOSHQASI BOR"',
        color: 'blue',
        tags: ['SOCIAL PROOF', 'STORYTELLING', 'RECIPROCITY'],
        script: `Ajoyib! Demak bu soha sizga muhim.

Nima yoqadi ularda?
Nima yaxshilanishi mumkin edi?

[Kompaniya Y] ham [raqobatchi] bilan ishlardi.
Biz bilan o'tgandan keyin [NATIJA].

Bepul sinov taklif qilaman – taqqoslang.`,
        result: 'Bepul sinov taklifi'
    },
    {
        id: 'obj4',
        text: '"VAQT YO\'Q"',
        color: 'purple',
        tags: ['REFRAMING', 'FUTURE PACING'],
        script: `Tushundim, vaqtingiz qimmat.

Aynan shuning uchun bu yechim –
sizga oyiga 20 soat tejaydi.

60 sekundda asosiy fikrni aytsam?`,
        result: '60-sekundlik pitch'
    },
    {
        id: 'obj5',
        text: '"MEN QAROR QILMAYMAN"',
        color: 'green',
        tags: ['COMMITMENT', 'CHAMPION'],
        script: `Tushundim. Lekin sizning fikringiz ham muhim.

Siz shaxsan nima deb o'ylaysiz?

Keling, qaror qiluvchi bilan birgalikda uchrashuvni belgilaylik.

Men sizga tayyor prezentatsiya tayyorlayman.`,
        result: 'Uchrashuv belgilash'
    },
    {
        id: 'obj6',
        text: '"ISHONCHIM KOMIL EMAS"',
        color: 'orange',
        tags: ['AUTHORITY', 'SOCIAL PROOF'],
        script: `To'g'ri savol. Aniq nima tashvishlantiradi?

Bizning obro':
• [Sertifikat] bor
• [X] yil tajriba
• [Y] kompaniya bizni tanlagan
• Kafolat beramiz

Bepul demo ko'rsatay?`,
        result: 'Demo ko\'rsatish'
    },
    {
        id: 'obj7',
        text: '"HOZIR KERAK EMAS"',
        color: 'gray',
        tags: ['FOMO', 'LOSS AVERSION'],
        script: `Tushundim. Qachon bu mavzu dolzarb bo'ladi?

Aytgancha, har oy kutish –
[X] yo'qotish degani.

Foydali material yuborib tursam?`,
        result: 'Nurturing boshlash'
    },
    {
        id: 'obj8',
        text: '"BYUDJET YO\'Q"',
        color: 'slate',
        tags: ['REFRAMING', 'OPTIONS'],
        script: `Tushundim. Qachon yangi byudjet?

Aytgancha, bir nechta variant bor:
• Bo'lib to'lash – oyiga [X] dan
• Kichik paketdan boshlash
• Bepul sinov – hoziroq

Qaysi variant qulayroq?`,
        result: 'Moslashuvchan to\'lov'
    },
    {
        id: 'obj9',
        text: '"JO\'NATIB QO\'YING"',
        color: 'purple',
        tags: ['COMMITMENT', 'FOLLOW-UP'],
        script: `Albatta! Lekin 2 daqiqada asosiyni tushuntirsam – material o'qiyotganda ancha oson bo'ladi.

[Rozi bo'lmasa]

Yaxshi. Qachon ko'rib chiqasiz?
[Kun]da qayta qo'ng'iroq qilsam?`,
        result: 'Follow-up belgilash'
    },
    {
        id: 'obj10',
        text: '"RAHBARIYAT ROZI BO\'LMAYDI"',
        color: 'blue',
        tags: ['CHAMPION', 'VALUE PROP'],
        script: `Tushundim. Rahbariyatni nima qiziqtiradi?

Men sizga:
• ROI hisob-kitobi
• Case study
• Qisqa prezentatsiya tayyorlayman

Rahbariyatga taqdim qilishda yordam beraman.`,
        result: 'Prezentatsiya tayyorlash'
    },
    {
        id: 'obj11',
        text: '"OLDIN SINAB KO\'RGANMIZ"',
        color: 'red',
        tags: ['DIFFERENTIATION', 'STORYTELLING'],
        script: `Tushundim. Nima natija berdi?
Nima yetishmadi?

Bizning farqimiz:
• [Farq 1]
• [Farq 2]
• [Farq 3]

[Kompaniya X] ham shunday degan edi...`,
        result: 'Farqni ko\'rsatish'
    },
    {
        id: 'obj12',
        text: '"SHARTNOMA BORLIGIDAN QO\'RQAMAN"',
        color: 'orange',
        tags: ['RISK REVERSAL', 'GUARANTEE'],
        script: `Tushundim, bu muhim masala.

Bizda:
• 30 kunlik pulni qaytarish kafolati
• Istalgan vaqtda bekor qilish mumkin
• Majburiyatsiz sinov davri

Risk nol. Nima yo'qotasiz?`,
        result: 'Risk kamaytirish'
    },
    {
        id: 'obj13',
        text: '"TEXNIK JIHATDAN MURAKKAB"',
        color: 'cyan',
        tags: ['SUPPORT', 'SIMPLICITY'],
        script: `To'g'ri savol!

Bizda:
• 24/7 texnik yordam
• Bepul o'rnatish va sozlash
• Video qo'llanmalar
• Shaxsiy menejer

Siz hech narsa qilmaysiz – biz hammasini qilamiz.`,
        result: 'Yordam ko\'rsatish'
    },
    {
        id: 'obj14',
        text: '"HOZIRGI YECHIM YETARLI"',
        color: 'green',
        tags: ['GAP SELLING', 'PAIN DISCOVERY'],
        script: `Ajoyib! Nima yaxshi ishlayapti?

Lekin... agar [MUAMMO] hal bo'lsa,
qancha vaqt/pul tejardingiz?

Tasavvur qiling: [YAXSHI NATIJA]

Hech bo'lmasa taqqoslab ko'ring?`,
        result: 'GAP yaratish'
    },
    {
        id: 'obj15',
        text: '"KEYINROQ QAYTAMAN"',
        color: 'gray',
        tags: ['URGENCY', 'COMMITMENT'],
        script: `Albatta! Lekin aniq qachon?

Aytgancha:
• Bu narx [SANA]gacha
• Keyingi oy [X]% qimmatroq
• Hozir boshlasangiz, [FOYDA]

Qaysi kun qo'ng'iroq qilsam?`,
        result: 'Aniq sana belgilash'
    },
];

// All 12 Closing Techniques
const closingTechniques = [
    {
        number: 1,
        name: 'DIRECT CLOSE',
        desc: 'To\'g\'ridan-to\'g\'ri so\'rang',
        script: '[ISM], barcha savollaringizga javob berdim.\nBoshlaymizmi?',
        when: 'Mijoz tayyor, barcha savollarga javob berilgan',
        power: 4
    },
    {
        number: 2,
        name: 'ALTERNATIVE CLOSE',
        desc: 'Tanlov bering – ikkalasi ham "ha"',
        script: 'Qaysi variant sizga qulayroq –\nasosiy paketmi yoki premium?',
        when: 'Mijoz ikkilanayotganda',
        power: 5
    },
    {
        number: 3,
        name: 'URGENCY CLOSE',
        desc: 'Shoshilinchlik yarating – SCARCITY',
        script: 'Bu narx faqat shu hafta.\nDushanbadan 30% qimmatroq.',
        when: 'Qo\'shimcha motivatsiya kerak',
        power: 4
    },
    {
        number: 4,
        name: 'SUMMARY CLOSE',
        desc: 'Hammasini xulosa qiling',
        script: 'Xulosa: Sizga [MUAMMO] hal kerak.\nBizning [YECHIM] aynan bunga.\nHammasiga rozimisiz?',
        when: 'Ko\'p nuqtalar muhokama qilingan',
        power: 4
    },
    {
        number: 5,
        name: 'ASSUMPTIVE CLOSE',
        desc: '"Ha" degan deb faraz qiling',
        script: 'Ajoyib, demak boshlaymiz!\nShartnomani qaysi emailga jo\'natay?',
        when: 'Mijoz "ha" signallari bergan',
        power: 5
    },
    {
        number: 6,
        name: 'SCALE CLOSE',
        desc: '1-10 shkala bilan aniqlang',
        script: '1 dan 10 gacha – qanchalik tayyorsiz?\nNima 10 ga yetkazadi?',
        when: 'Yashirin e\'tirozni topish uchun',
        power: 5
    },
    {
        number: 7,
        name: 'IF CLOSE',
        desc: 'Shartli kelishuv',
        script: 'Agar men [SHARTNI] bajarsam...\nBoshlaymizmi?',
        when: 'E\'tirozni bartaraf etish bilan',
        power: 4
    },
    {
        number: 8,
        name: 'PUPPY DOG CLOSE',
        desc: 'Sinov taklif qiling',
        script: '7 kunlik bepul sinov – majburiyat yo\'q.\nYoqmasa – bekor qilasiz.',
        when: 'Ishonch yo\'q – risk kamaytirish',
        power: 5
    },
    {
        number: 9,
        name: 'TAKEAWAY CLOSE',
        desc: 'Olib qo\'yish bilan qiziqtiring',
        script: 'Bu yechim hamma uchun emas.\nFaqat [KRITERIYA]ga mos kompaniyalar uchun.',
        when: 'Mijoz juda ko\'p o\'ylayotganda',
        power: 4
    },
    {
        number: 10,
        name: 'SHARP ANGLE CLOSE',
        desc: 'So\'rovga shartli javob',
        script: 'Mijoz: "Chegirma bormi?"\nSiz: "Agar bugun imzolasangiz – 10%."',
        when: 'Mijoz so\'rov qilganda',
        power: 5
    },
    {
        number: 11,
        name: 'BEN FRANKLIN CLOSE',
        desc: 'Foyda va zarar ro\'yxati',
        script: 'Keling birga ko\'raylik:\n+ [Foyda 1, 2, 3...]\n- [Kamchilik]\nQaysi biri og\'irroq?',
        when: 'Analitik mijozlar uchun',
        power: 4
    },
    {
        number: 12,
        name: 'COLUMBO CLOSE',
        desc: '"Oxirgi savol" texnikasi',
        script: 'Tushundim. Oxirgi savol:\nAgar narx/vaqt/[X] muammo bo\'lmasa – boshlar edingizmi?',
        when: 'Haqiqiy e\'tirozni topish',
        power: 5
    },
];

// Buying signals
const buyingSignals = [
    { icon: '💬', text: '"Qancha turadi?"', meaning: 'Qiziqish bor! Yopishga o\'ting', positive: true },
    { icon: '📅', text: '"Qachon boshlash mumkin?"', meaning: 'Tayyor! Sanani belgilang', positive: true },
    { icon: '🔧', text: '"Qanday ishlaydi?"', meaning: 'Texnik savol = Jiddiy qiziqish', positive: true },
    { icon: '👥', text: '"Jamoa uchun ham bormi?"', meaning: 'Kengaytirish istagi = Upsell', positive: true },
    { icon: '📄', text: '"Shartnoma qanday?"', meaning: 'Juda yaxshi! Formaliklarga o\'ting', positive: true },
    { icon: '🔍', text: '"[Raqobatchi]dan nima farqi?"', meaning: 'Taqqoslash = Jiddiy ko\'rib chiqish', positive: true },
    { icon: '💳', text: '"To\'lov shartlari qanday?"', meaning: 'Xarid qilishga tayyor!', positive: true },
    { icon: '📞', text: '"Kim bilan bog\'lansam?"', meaning: 'Keyingi qadamga tayyor', positive: true },
    { icon: '⚠️', text: '"Qiziq, lekin..."', meaning: 'E\'tiroz keladi! Tayyor bo\'ling', positive: false },
    { icon: '⚠️', text: '"O\'ylab ko\'raman"', meaning: 'Haqiqiy e\'tirozni toping!', positive: false },
    { icon: '⚠️', text: '"Hozir band"', meaning: 'Vaqt belgilang yoki nurturing', positive: false },
    { icon: '⚠️', text: '"Material jo\'nating"', meaning: 'Follow-up rejalashtiring', positive: false },
];

// Script Steps
const scriptSteps = [
    {
        number: 1,
        title: 'ALOQA O\'RNATISH',
        duration: '15-30 sek',
        color: 'blue',
        script: `"Assalomu alaykum, [ISM]!

Men [ISMINGIZ], [KOMPANIYA]dan.
2 daqiqa – va siz [FOYDA] haqida bilasiz.

Hozir gaplashsak bo'ladimi?"`,
        techniques: ['LIKING', 'PATTERN INTERRUPT'],
        responses: [
            { type: 'positive', text: '"Ha, gapiring"', action: '2-BOSQICHga o\'ting' },
            { type: 'neutral', text: '"Hozir bandman"', action: '"Bugun kechqurun yoki ertaga – qaysi biri?"' },
            { type: 'negative', text: '"Kerak emas"', action: '"Ajoyib! Faqat 1 savol: [MUAMMO] sizda ham bormi?"' },
        ]
    },
    {
        number: 2,
        title: 'EHTIYOJNI ANIQLASH – SPIN',
        duration: '2-5 daq',
        color: 'green',
        script: `S: "Hozirda [SOHA] bo'yicha qanday yechimlardan foydalanasiz?"

P: "Bu jarayonda eng katta qiyinchilik nima?"
⚠️ OG'RIQ NUQTASINI YOZIB OLING!

I: "Bu muammo tufayli oyiga qancha yo'qotyapsiz?"

N: "Agar bu muammo to'liq hal bo'lsa, sizga nima o'zgaradi?"`,
        techniques: ['SPIN', 'MIRRORING', 'ACTIVE LISTENING'],
        keyRule: '70% TINGLANG, 30% GAPIRING'
    },
    {
        number: 3,
        title: 'PREZENTATSIYA – FAB',
        duration: '3-5 daq',
        color: 'orange',
        script: `"Siz aytdingiz: [MIJOZ AYTGAN MUAMMO]...

Aynan shuning uchun bizda [YECHIM] bor.
Bu sizga [AFZALLIK] beradi.
Natijada [RAQAMLI FOYDA] olasiz.

HIKOYA: [Kompaniya X] ham xuddi shunday edi.
Ular biz bilan 3 oyda [NATIJA]ga erishdi.

Bu sizga mos keladi, to'g'rimi?"`,
        techniques: ['FAB', 'STORYTELLING', 'SOCIAL PROOF', 'TIE-DOWN'],
        keyRule: 'Doimo "Siz aytdingiz..." bilan boshlang!'
    },
    {
        number: 4,
        title: 'E\'TIROZLARNI BARTARAF ETISH',
        duration: '2-5 daq',
        color: 'red',
        script: `LAER TEXNIKASI:

L - LISTEN: Tinglang, bo'lmang!
A - ACKNOWLEDGE: "Tushundim, muhim savol"
E - EXPLORE: "Aniqroq aytsangiz?"
R - RESPOND: Javob bering va QAYTARING

Har bir e'tirozdan keyin:
"Shu savolga javob berdimmi? – Demak, davom etamiz..."`,
        techniques: ['LAER', 'FEEL-FELT-FOUND', 'REFRAMING'],
        keyRule: 'E\'tiroz = "Menga ko\'proq ma\'lumot kerak"'
    },
    {
        number: 5,
        title: 'BITIMNI YOPISH',
        duration: '1-3 daq',
        color: 'emerald',
        script: `1. DIRECT: "Boshlaymizmi?"

2. ALTERNATIVE: "Asosiy yoki premium?"

3. ASSUMPTIVE: "Shartnomani qaysi emailga?"

4. SCALE: "1-10, qanchalik tayyorsiz?"

5. IF CLOSE: "Agar [SHART] - boshlaymizmi?"

⚠️ SAVOLDAN KEYIN – JIM TURING!`,
        techniques: ['DIRECT', 'ALTERNATIVE', 'ASSUMPTIVE', 'SCALE'],
        keyRule: 'Savoldan keyin – JIM TURING!'
    },
    {
        number: 6,
        title: 'YAKUNLASH & FOLLOW-UP',
        duration: '1-2 daq',
        color: 'purple',
        script: `✅ BITIM BO'LDI:
"Ajoyib qaror! Kelishuvimiz:
1. Shartnoma – bugun
2. To'lov – [SANA]
3. Start – [SANA]"

⏳ KUTISH:
"Material jo'nataman – bugun
[KUN]da qayta qo'ng'iroq qilaman"

❌ RAD:
"Tushundim. Kelajakda ehtiyoj bo'lsa – qo'ng'iroq qiling.
Foydali material yuborib turaymi?"`,
        techniques: ['COMMITMENT', 'FOLLOW-UP', 'NURTURING'],
        keyRule: 'Har doim keyingi qadamni belgilang!'
    },
];

// Print function
const printPage = () => {
    window.print();
};

// Filter function based on search
const filteredObjections = computed(() => {
    let list = objections;
    if (showOnlyBookmarked.value) {
        list = list.filter(obj => bookmarkedItems.value.has(obj.id));
    }
    if (!searchQuery.value) return list;
    const query = searchQuery.value.toLowerCase();
    return list.filter(obj =>
        obj.text.toLowerCase().includes(query) ||
        obj.tags.some(tag => tag.toLowerCase().includes(query)) ||
        obj.script.toLowerCase().includes(query)
    );
});

// Filter methodologies
const filteredMethodologies = computed(() => {
    if (!searchQuery.value) return methodologies;
    const query = searchQuery.value.toLowerCase();
    return methodologies.filter(m =>
        m.name.toLowerCase().includes(query) ||
        m.origin.toLowerCase().includes(query) ||
        m.steps.some(s => s.title.toLowerCase().includes(query) || s.desc.toLowerCase().includes(query))
    );
});

// Filter closing techniques
const filteredClosingTechniques = computed(() => {
    if (!searchQuery.value) return closingTechniques;
    const query = searchQuery.value.toLowerCase();
    return closingTechniques.filter(t =>
        t.name.toLowerCase().includes(query) ||
        t.desc.toLowerCase().includes(query) ||
        t.script.toLowerCase().includes(query)
    );
});

// Search result count
const searchResultCount = computed(() => {
    if (!searchQuery.value) return null;
    return filteredObjections.value.length + filteredMethodologies.value.length + filteredClosingTechniques.value.length;
});

// Current practice objection
const currentPracticeObjection = computed(() => objections[currentPracticeIndex.value]);
</script>

<template>
    <div class="min-h-screen">
        <!-- Toast Notification -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="transform translate-y-2 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform translate-y-2 opacity-0"
        >
            <div v-if="toast.show"
                 :class="[
                     'fixed bottom-24 right-6 z-[100] px-5 py-3 rounded-xl shadow-2xl flex items-center gap-3 font-medium',
                     toast.type === 'success' ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white'
                 ]">
                <CheckIcon v-if="toast.type === 'success'" class="w-5 h-5" />
                <XCircleIcon v-else class="w-5 h-5" />
                {{ toast.message }}
            </div>
        </Transition>

        <!-- Scroll to Top Button -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 scale-90"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-90"
        >
            <button v-if="showScrollTop"
                    @click="scrollToTop"
                    class="fixed bottom-6 right-6 z-50 w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-500 text-white rounded-full shadow-lg hover:shadow-xl hover:scale-110 transition-all flex items-center justify-center print:hidden">
                <ArrowUpIcon class="w-6 h-6" />
            </button>
        </Transition>

        <!-- Header -->
        <div class="relative bg-gradient-to-br from-amber-500/10 via-orange-500/5 to-yellow-500/10 dark:from-amber-500/20 dark:via-orange-500/10 dark:to-yellow-500/20 border-2 border-amber-500/30 dark:border-amber-400/40 rounded-2xl p-8 mb-6 overflow-hidden">
            <div class="absolute inset-0 opacity-5 dark:opacity-10">
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-[200px] select-none">🏆</div>
            </div>

            <div class="relative z-10 text-center">
                <div class="inline-block bg-gradient-to-r from-amber-500 to-orange-500 text-white px-6 py-2 rounded-full text-sm font-bold mb-4 shadow-lg">
                    🏆 ULTIMATE EDITION
                </div>
                <h1 class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-amber-500 to-orange-500 bg-clip-text text-transparent mb-3">
                    SOTUV ARSENALI
                </h1>
                <p class="text-gray-600 dark:text-gray-400 text-lg mb-8 max-w-2xl mx-auto">
                    Dunyodagi eng kuchli sotuv texnikalari, metodologiyalari va psixologik usullar
                </p>

                <!-- Stats -->
                <div class="flex flex-wrap justify-center gap-3 md:gap-4">
                    <div v-for="stat in stats" :key="stat.label"
                         class="bg-white/60 dark:bg-gray-800/60 backdrop-blur-sm border border-gray-200/50 dark:border-gray-700/50 px-5 py-3 rounded-xl shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">{{ stat.icon }}</span>
                            <span class="text-2xl font-bold text-amber-500">{{ stat.value }}</span>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ stat.label }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation & Search -->
        <div class="sticky top-0 z-50 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-6 shadow-sm print:hidden">
            <!-- Search -->
            <div class="relative mb-4">
                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Qidirish... (masalan: qimmat, SPIN, yopish)"
                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-400"
                />
            </div>

            <!-- Search result count -->
            <div v-if="searchResultCount !== null" class="mb-3 text-center">
                <span class="bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 px-4 py-1.5 rounded-full text-sm font-medium">
                    {{ searchResultCount }} ta natija topildi
                </span>
            </div>

            <!-- Buttons -->
            <div class="flex flex-wrap gap-2 justify-center">
                <button @click="togglePracticeMode"
                        :class="[
                            'px-4 py-2 rounded-full font-medium text-sm hover:shadow-lg transition-all flex items-center gap-1.5',
                            practiceMode ? 'bg-purple-500 text-white' : 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400'
                        ]">
                    <component :is="practiceMode ? PauseIcon : PlayIcon" class="w-4 h-4" />
                    {{ practiceMode ? 'Mashqni to\'xtatish' : 'Mashq rejimi' }}
                </button>
                <button @click="showOnlyBookmarked = !showOnlyBookmarked"
                        :class="[
                            'px-4 py-2 rounded-full font-medium text-sm hover:shadow-lg transition-all flex items-center gap-1.5',
                            showOnlyBookmarked ? 'bg-amber-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
                        ]">
                    <component :is="showOnlyBookmarked ? BookmarkSolidIcon : BookmarkIcon" class="w-4 h-4" />
                    Sevimlilar ({{ bookmarkedItems.size }})
                </button>
                <button @click="expandAll" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-full font-medium text-sm hover:shadow-lg transition-all flex items-center gap-1.5">
                    <ChevronDownIcon class="w-4 h-4" />
                    Hammasini ochish
                </button>
                <button @click="collapseAll" class="px-4 py-2 bg-gray-500 text-white rounded-full font-medium text-sm hover:shadow-lg transition-all flex items-center gap-1.5">
                    <ChevronUpIcon class="w-4 h-4" />
                    Yopish
                </button>
                <button @click="printPage" class="px-4 py-2 bg-emerald-500 text-white rounded-full font-medium text-sm hover:shadow-lg transition-all flex items-center gap-1.5">
                    <PrinterIcon class="w-4 h-4" />
                    Chop etish
                </button>
                <div class="hidden md:flex gap-2 ml-2 border-l border-gray-200 dark:border-gray-600 pl-4">
                    <button @click="expandedSections.methods = true; expandedSections.psychology = false; expandedSections.nlp = false; expandedSections.script = false; expandedSections.objections = false; expandedSections.closing = false; expandedSections.followup = false;"
                            class="px-3 py-2 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg text-sm font-medium transition-colors">
                        📚 Metodlar
                    </button>
                    <button @click="expandedSections.script = true"
                            class="px-3 py-2 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg text-sm font-medium transition-colors">
                        📋 6-Bosqich
                    </button>
                    <button @click="expandedSections.objections = true"
                            class="px-3 py-2 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg text-sm font-medium transition-colors">
                        🛡️ E'tirozlar
                    </button>
                    <button @click="expandedSections.closing = true"
                            class="px-3 py-2 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg text-sm font-medium transition-colors">
                        🎯 Yopish
                    </button>
                </div>
            </div>
        </div>

        <!-- PRACTICE MODE PANEL -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 -translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-4"
        >
            <section v-if="practiceMode" class="bg-gradient-to-br from-purple-500/10 to-pink-500/10 dark:from-purple-500/20 dark:to-pink-500/20 border-2 border-purple-500/30 dark:border-purple-400/40 rounded-2xl mb-6 overflow-hidden print:hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg">
                                <PlayIcon class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">🎯 E'tiroz Mashqi</h2>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">{{ currentPracticeIndex + 1 }} / {{ objections.length }}</p>
                            </div>
                        </div>
                        <button @click="togglePracticeMode" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <XCircleIcon class="w-8 h-8" />
                        </button>
                    </div>

                    <!-- Question Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-4">
                        <div class="text-center mb-6">
                            <span class="text-5xl mb-4 block">🙋</span>
                            <div class="text-gray-500 dark:text-gray-400 text-sm mb-2">Mijoz aytdi:</div>
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                {{ currentPracticeObjection?.text }}
                            </div>
                        </div>

                        <div class="flex flex-wrap justify-center gap-2 mb-4">
                            <span v-for="tag in currentPracticeObjection?.tags" :key="tag"
                                  class="bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 px-3 py-1 rounded-full text-sm font-medium">
                                {{ tag }}
                            </span>
                        </div>

                        <Transition
                            enter-active-class="transition ease-out duration-300"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                        >
                            <div v-if="showAnswer" class="mt-6">
                                <div class="bg-gray-900 dark:bg-black rounded-xl p-5 text-gray-200 whitespace-pre-line text-sm leading-relaxed font-mono mb-4">{{ currentPracticeObjection?.script }}</div>
                                <div class="flex items-center gap-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 p-3 rounded-lg text-sm font-medium">
                                    <ArrowRightIcon class="w-4 h-4 flex-shrink-0" />
                                    {{ currentPracticeObjection?.result }}
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Controls -->
                    <div class="flex items-center justify-center gap-4">
                        <button @click="prevPractice" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            ← Oldingi
                        </button>
                        <button v-if="!showAnswer" @click="showAnswer = true"
                                class="px-8 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all">
                            Javobni ko'rsatish
                        </button>
                        <button v-else @click="copyToClipboard(currentPracticeObjection?.script, 'Skript')"
                                class="px-6 py-3 bg-emerald-500 text-white rounded-xl font-medium hover:bg-emerald-600 transition-colors flex items-center gap-2">
                            <ClipboardDocumentIcon class="w-5 h-5" />
                            Nusxalash
                        </button>
                        <button @click="nextPractice" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            Keyingi →
                        </button>
                    </div>
                </div>
            </section>
        </Transition>

        <!-- 6 BOSQICHLI SKRIPT -->
        <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl mb-6 overflow-hidden print:break-inside-avoid">
            <div @click="toggleSection('script')"
                 class="flex items-center gap-4 p-6 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-200 dark:border-gray-700">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-xl flex items-center justify-center text-2xl flex-shrink-0 shadow-lg">
                    📋
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">6 BOSQICHLI TO'LIQ SKRIPT</h2>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Qo'ng'iroqdan bitimgacha - to'liq yo'l xaritasi</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-3 py-1 rounded-full text-sm font-medium">
                        Professional
                    </span>
                    <component :is="expandedSections.script ? ChevronUpIcon : ChevronDownIcon" class="w-6 h-6 text-gray-400" />
                </div>
            </div>

            <div v-show="expandedSections.script" class="p-6">
                <div class="space-y-6">
                    <div v-for="step in scriptSteps" :key="step.number"
                         class="relative">
                        <!-- Step Header -->
                        <div class="flex items-start gap-4 mb-4">
                            <div :class="[
                                'w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg flex-shrink-0',
                                {
                                    'bg-gradient-to-br from-blue-500 to-blue-600': step.color === 'blue',
                                    'bg-gradient-to-br from-green-500 to-emerald-600': step.color === 'green',
                                    'bg-gradient-to-br from-orange-500 to-amber-600': step.color === 'orange',
                                    'bg-gradient-to-br from-red-500 to-rose-600': step.color === 'red',
                                    'bg-gradient-to-br from-emerald-500 to-teal-600': step.color === 'emerald',
                                    'bg-gradient-to-br from-purple-500 to-violet-600': step.color === 'purple',
                                }
                            ]">
                                {{ step.number }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ step.title }}</h3>
                                    <span class="text-gray-400 dark:text-gray-500 text-sm">({{ step.duration }})</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    <span v-for="tech in step.techniques" :key="tech"
                                          class="bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 px-2 py-0.5 rounded text-xs font-medium">
                                        {{ tech }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Script Content -->
                        <div class="ml-16 space-y-4">
                            <div class="relative group">
                                <div class="bg-gray-900 dark:bg-black rounded-xl p-5 text-gray-100 whitespace-pre-line text-sm leading-relaxed font-mono">{{ step.script }}</div>
                                <button @click="copyToClipboard(step.script, step.title)"
                                        class="absolute top-3 right-3 p-2 bg-gray-700 hover:bg-gray-600 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                    <ClipboardDocumentIcon class="w-4 h-4 text-gray-300" />
                                </button>
                            </div>

                            <!-- Responses for step 1 -->
                            <div v-if="step.responses" class="grid md:grid-cols-3 gap-3">
                                <div v-for="response in step.responses" :key="response.text"
                                     :class="[
                                         'p-4 rounded-xl border-2',
                                         {
                                             'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300 dark:border-emerald-700': response.type === 'positive',
                                             'bg-orange-50 dark:bg-orange-900/20 border-orange-300 dark:border-orange-700': response.type === 'neutral',
                                             'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700': response.type === 'negative',
                                         }
                                     ]">
                                    <div class="font-medium text-gray-900 dark:text-white mb-2">{{ response.text }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">→ {{ response.action }}</div>
                                </div>
                            </div>

                            <!-- Key Rule -->
                            <div v-if="step.keyRule" class="bg-amber-100 dark:bg-amber-900/30 border border-amber-300 dark:border-amber-700 rounded-xl p-4 flex items-start gap-3">
                                <LightBulbIcon class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                                <span class="text-amber-800 dark:text-amber-300 font-medium">{{ step.keyRule }}</span>
                            </div>
                        </div>

                        <!-- Connector Line -->
                        <div v-if="step.number < 6" class="ml-6 mt-4 mb-2 w-0.5 h-8 bg-gradient-to-b from-gray-300 to-gray-200 dark:from-gray-600 dark:to-gray-700"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- METODOLOGIYALAR -->
        <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl mb-6 overflow-hidden print:break-inside-avoid">
            <div @click="toggleSection('methods')"
                 class="flex items-center gap-4 p-6 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-200 dark:border-gray-700">
                <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl flex items-center justify-center text-2xl flex-shrink-0 shadow-lg">
                    📚
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">SOTUV METODOLOGIYALARI</h2>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Dunyodagi eng muvaffaqiyatli sotuv tizimlari</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 px-3 py-1 rounded-full text-sm font-medium">
                        8 ta metodologiya
                    </span>
                    <component :is="expandedSections.methods ? ChevronUpIcon : ChevronDownIcon" class="w-6 h-6 text-gray-400" />
                </div>
            </div>

            <div v-show="expandedSections.methods" class="p-6">
                <div v-if="filteredMethodologies.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <span class="text-4xl block mb-2">🔍</span>
                    Hech qanday natija topilmadi
                </div>
                <div class="grid md:grid-cols-2 gap-6">
                    <div v-for="method in filteredMethodologies" :key="method.name"
                         :class="[
                             'rounded-xl overflow-hidden border-2 transition-all hover:shadow-lg',
                             {
                                 'bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border-amber-200 dark:border-amber-800': method.color === 'amber',
                                 'bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-red-200 dark:border-red-800': method.color === 'red',
                                 'bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-blue-200 dark:border-blue-800': method.color === 'blue',
                                 'bg-gradient-to-br from-purple-50 to-violet-50 dark:from-purple-900/20 dark:to-violet-900/20 border-purple-200 dark:border-purple-800': method.color === 'purple',
                                 'bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 border-emerald-200 dark:border-emerald-800': method.color === 'emerald',
                                 'bg-gradient-to-br from-cyan-50 to-sky-50 dark:from-cyan-900/20 dark:to-sky-900/20 border-cyan-200 dark:border-cyan-800': method.color === 'cyan',
                                 'bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 border-yellow-200 dark:border-yellow-800': method.color === 'yellow',
                                 'bg-gradient-to-br from-pink-50 to-rose-50 dark:from-pink-900/20 dark:to-rose-900/20 border-pink-200 dark:border-pink-800': method.color === 'pink',
                             }
                         ]">
                        <div class="p-5 text-center border-b border-inherit">
                            <div class="text-4xl mb-2">{{ method.icon }}</div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ method.name }}</h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ method.origin }}</p>
                        </div>
                        <div class="p-5 space-y-2">
                            <div v-for="step in method.steps" :key="step.letter"
                                 class="flex items-center gap-3 bg-white/60 dark:bg-gray-800/60 p-3 rounded-lg hover:bg-white dark:hover:bg-gray-700 transition-colors">
                                <div class="w-9 h-9 bg-gradient-to-br from-amber-500 to-orange-500 text-white rounded-lg flex items-center justify-center font-bold text-sm flex-shrink-0 shadow">
                                    {{ step.letter }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">{{ step.title }}</div>
                                    <div class="text-gray-500 dark:text-gray-400 text-xs truncate">{{ step.desc }}</div>
                                </div>
                            </div>
                            <div v-if="method.keyPoint" class="bg-amber-100 dark:bg-amber-900/40 rounded-lg p-3 mt-3">
                                <p class="text-amber-700 dark:text-amber-300 text-sm font-medium flex items-start gap-2">
                                    <LightBulbIcon class="w-4 h-4 flex-shrink-0 mt-0.5" />
                                    {{ method.keyPoint }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- PSIXOLOGIK TRIGGERLAR -->
        <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl mb-6 overflow-hidden print:break-inside-avoid">
            <div @click="toggleSection('psychology')"
                 class="flex items-center gap-4 p-6 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-200 dark:border-gray-700">
                <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center text-2xl flex-shrink-0 shadow-lg">
                    🧠
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">PSIXOLOGIK TRIGGERLAR</h2>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Cialdini's 6 Principles + Psixologik texnikalar</p>
                </div>
                <component :is="expandedSections.psychology ? ChevronUpIcon : ChevronDownIcon" class="w-6 h-6 text-gray-400" />
            </div>

            <div v-show="expandedSections.psychology" class="p-6">
                <h3 class="text-lg font-bold text-amber-500 mb-4 flex items-center gap-2">
                    🎯 CIALDINI'S 6 PRINCIPLES OF PERSUASION
                </h3>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                    <div v-for="principle in cialdiniPrinciples" :key="principle.name"
                         class="bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl overflow-hidden hover:border-amber-500 dark:hover:border-amber-400 hover:shadow-md transition-all">
                        <div class="flex items-center gap-3 p-4 border-b border-gray-200 dark:border-gray-600 bg-white/50 dark:bg-gray-800/50">
                            <span class="text-2xl">{{ principle.icon }}</span>
                            <span class="font-bold text-amber-600 dark:text-amber-400">{{ principle.name }}</span>
                            <div class="ml-auto flex gap-0.5">
                                <StarSolidIcon v-for="n in principle.power" :key="n" class="w-4 h-4 text-amber-400" />
                                <StarIcon v-for="n in (5 - principle.power)" :key="'e'+n" class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                            </div>
                        </div>
                        <div class="p-4">
                            <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">{{ principle.desc }}</p>
                            <div class="bg-gray-900 dark:bg-black rounded-lg p-3 border-l-4 border-amber-500">
                                <div class="text-amber-400 text-xs font-bold mb-2 uppercase tracking-wider">💬 Misol</div>
                                <div class="text-gray-300 text-sm space-y-1">
                                    <div v-for="(example, idx) in principle.examples" :key="idx" class="leading-relaxed">{{ example }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Psychological Techniques -->
                <h3 class="text-lg font-bold text-amber-500 mb-4 flex items-center gap-2">
                    🧪 KUCHLI PSIXOLOGIK TEXNIKALAR
                </h3>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                    <div v-for="tech in psychTechniques" :key="tech.name"
                         class="bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl p-4 hover:border-purple-500 dark:hover:border-purple-400 transition-colors">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xl">{{ tech.icon }}</span>
                            <span class="font-bold text-purple-600 dark:text-purple-400">{{ tech.name }}</span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 text-sm mb-2">{{ tech.desc }}</p>
                        <div class="bg-purple-100 dark:bg-purple-900/30 rounded-lg p-2 text-purple-700 dark:text-purple-300 text-sm">
                            {{ tech.example }}
                        </div>
                    </div>
                </div>

                <!-- Buying Signals -->
                <h3 class="text-lg font-bold text-amber-500 mb-4 flex items-center gap-2">
                    📢 XARID SIGNALLARI (Buying Signals)
                </h3>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div v-for="signal in buyingSignals" :key="signal.text"
                         :class="[
                             'p-4 rounded-xl border-2 transition-transform hover:-translate-y-1 hover:shadow-md',
                             signal.positive
                                 ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300 dark:border-emerald-700'
                                 : 'bg-orange-50 dark:bg-orange-900/20 border-orange-300 dark:border-orange-700'
                         ]">
                        <div class="text-2xl mb-2">{{ signal.icon }}</div>
                        <div class="text-gray-700 dark:text-gray-300 text-sm italic mb-2">{{ signal.text }}</div>
                        <div :class="[
                            'text-xs font-bold',
                            signal.positive ? 'text-emerald-600 dark:text-emerald-400' : 'text-orange-600 dark:text-orange-400'
                        ]">
                            {{ signal.positive ? '✓' : '⚠' }} {{ signal.meaning }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- NLP TEXNIKALAR -->
        <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl mb-6 overflow-hidden print:break-inside-avoid">
            <div @click="toggleSection('nlp')"
                 class="flex items-center gap-4 p-6 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-200 dark:border-gray-700">
                <div class="w-14 h-14 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-xl flex items-center justify-center text-2xl flex-shrink-0 shadow-lg">
                    💬
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">NLP & SO'Z SAN'ATI</h2>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Neuro-Linguistic Programming texnikalari</p>
                </div>
                <component :is="expandedSections.nlp ? ChevronUpIcon : ChevronDownIcon" class="w-6 h-6 text-gray-400" />
            </div>

            <div v-show="expandedSections.nlp" class="p-6">
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="tech in nlpTechniques" :key="tech.name"
                         class="bg-gradient-to-br from-cyan-50 to-blue-50 dark:from-cyan-900/20 dark:to-blue-900/20 border border-cyan-200 dark:border-cyan-800 rounded-xl p-5 hover:shadow-lg transition-all">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="w-8 h-8 bg-gradient-to-br from-cyan-500 to-blue-500 text-white rounded-lg flex items-center justify-center font-bold">{{ tech.icon }}</span>
                            <span class="font-bold text-cyan-700 dark:text-cyan-400">{{ tech.name }}</span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">{{ tech.desc }}</p>
                        <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-3 text-gray-700 dark:text-gray-300 text-sm border border-cyan-200 dark:border-cyan-800">
                            {{ tech.example }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- E'TIROZLAR -->
        <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl mb-6 overflow-hidden print:break-inside-avoid">
            <div @click="toggleSection('objections')"
                 class="flex items-center gap-4 p-6 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-200 dark:border-gray-700">
                <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-rose-500 rounded-xl flex items-center justify-center text-2xl flex-shrink-0 shadow-lg">
                    🛡️
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">E'TIROZLARNI BARTARAF ETISH</h2>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">15 ta asosiy e'tiroz va psixologik javoblar</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 px-3 py-1 rounded-full text-sm font-medium">
                        LAER texnikasi
                    </span>
                    <component :is="expandedSections.objections ? ChevronUpIcon : ChevronDownIcon" class="w-6 h-6 text-gray-400" />
                </div>
            </div>

            <div v-show="expandedSections.objections" class="p-6">
                <!-- LAER Method -->
                <div class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border-2 border-amber-300 dark:border-amber-700 rounded-xl p-6 mb-6">
                    <div class="text-center mb-4">
                        <div class="text-4xl mb-2">🧠</div>
                        <h3 class="text-xl font-bold text-amber-600 dark:text-amber-400">LAER TEXNIKASI</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Universal e'tiroz javob tizimi</p>
                    </div>
                    <div class="grid md:grid-cols-4 gap-4">
                        <div class="bg-white/70 dark:bg-gray-800/70 p-4 rounded-xl text-center shadow-sm">
                            <div class="w-12 h-12 mx-auto bg-gradient-to-br from-amber-500 to-orange-500 text-white rounded-xl flex items-center justify-center font-bold text-xl mb-2 shadow">L</div>
                            <div class="font-bold text-gray-900 dark:text-white">LISTEN</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm">Tinglang, bo'lmang!</div>
                        </div>
                        <div class="bg-white/70 dark:bg-gray-800/70 p-4 rounded-xl text-center shadow-sm">
                            <div class="w-12 h-12 mx-auto bg-gradient-to-br from-amber-500 to-orange-500 text-white rounded-xl flex items-center justify-center font-bold text-xl mb-2 shadow">A</div>
                            <div class="font-bold text-gray-900 dark:text-white">ACKNOWLEDGE</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm">"Tushundim, muhim"</div>
                        </div>
                        <div class="bg-white/70 dark:bg-gray-800/70 p-4 rounded-xl text-center shadow-sm">
                            <div class="w-12 h-12 mx-auto bg-gradient-to-br from-amber-500 to-orange-500 text-white rounded-xl flex items-center justify-center font-bold text-xl mb-2 shadow">E</div>
                            <div class="font-bold text-gray-900 dark:text-white">EXPLORE</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm">"Aniqroq aytsangiz?"</div>
                        </div>
                        <div class="bg-white/70 dark:bg-gray-800/70 p-4 rounded-xl text-center shadow-sm">
                            <div class="w-12 h-12 mx-auto bg-gradient-to-br from-amber-500 to-orange-500 text-white rounded-xl flex items-center justify-center font-bold text-xl mb-2 shadow">R</div>
                            <div class="font-bold text-gray-900 dark:text-white">RESPOND</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm">Javob + QAYTARING</div>
                        </div>
                    </div>
                </div>

                <!-- Objection Cards -->
                <div v-if="filteredObjections.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <span class="text-4xl block mb-2">{{ showOnlyBookmarked ? '📑' : '🔍' }}</span>
                    {{ showOnlyBookmarked ? 'Hech qanday sevimli e\'tiroz tanlanmagan' : 'Hech qanday natija topilmadi' }}
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="objection in filteredObjections" :key="objection.id"
                         class="bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl overflow-hidden hover:shadow-lg transition-all group">
                        <div :class="[
                            'p-4 font-bold text-white flex items-center justify-between',
                            {
                                'bg-gradient-to-r from-red-500 to-rose-500': objection.color === 'red',
                                'bg-gradient-to-r from-orange-500 to-amber-500': objection.color === 'orange',
                                'bg-gradient-to-r from-blue-500 to-cyan-500': objection.color === 'blue',
                                'bg-gradient-to-r from-purple-500 to-pink-500': objection.color === 'purple',
                                'bg-gradient-to-r from-emerald-500 to-teal-500': objection.color === 'green',
                                'bg-gradient-to-r from-gray-500 to-slate-500': objection.color === 'gray',
                                'bg-gradient-to-r from-slate-500 to-zinc-500': objection.color === 'slate',
                                'bg-gradient-to-r from-cyan-500 to-sky-500': objection.color === 'cyan',
                            }
                        ]">
                            <span class="flex items-center gap-2">
                                <ChatBubbleLeftRightIcon class="w-5 h-5" />
                                {{ objection.text }}
                            </span>
                            <button @click.stop="toggleBookmark(objection.id)" class="opacity-0 group-hover:opacity-100 transition-opacity">
                                <component :is="bookmarkedItems.has(objection.id) ? BookmarkSolidIcon : BookmarkIcon"
                                           :class="['w-5 h-5', bookmarkedItems.has(objection.id) ? 'text-amber-300' : 'text-white/70 hover:text-white']" />
                            </button>
                        </div>
                        <div class="p-4">
                            <div class="flex flex-wrap gap-1 mb-3">
                                <span v-for="tag in objection.tags" :key="tag"
                                      class="bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 px-2 py-0.5 rounded text-xs font-medium">
                                    {{ tag }}
                                </span>
                            </div>
                            <div class="relative group/script">
                                <div class="bg-gray-900 dark:bg-black rounded-lg p-4 text-gray-200 text-sm whitespace-pre-line mb-3 font-mono leading-relaxed">{{ objection.script }}</div>
                                <button @click.stop="copyToClipboard(objection.script, objection.text)"
                                        class="absolute top-2 right-2 p-1.5 bg-gray-700 hover:bg-gray-600 rounded-lg opacity-0 group-hover/script:opacity-100 transition-opacity"
                                        title="Nusxalash">
                                    <ClipboardDocumentIcon class="w-4 h-4 text-gray-300" />
                                </button>
                            </div>
                            <div class="flex items-center gap-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 p-3 rounded-lg text-sm font-medium">
                                <ArrowRightIcon class="w-4 h-4 flex-shrink-0" />
                                {{ objection.result }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Key Point -->
                <div class="mt-6 bg-amber-100 dark:bg-amber-900/30 border-2 border-amber-300 dark:border-amber-700 rounded-xl p-5">
                    <h4 class="text-amber-700 dark:text-amber-400 font-bold flex items-center gap-2 mb-2">
                        <LightBulbIcon class="w-5 h-5" />
                        E'TIROZ = "MENGA KO'PROQ MA'LUMOT KERAK"
                    </h4>
                    <p class="text-gray-700 dark:text-gray-300">
                        Har bir e'tirozdan keyin: <strong>"Shu savolga javob berdimmi? – Demak, davom etamiz..."</strong><br><br>
                        <strong>Muhim:</strong> E'tiroz – bu "yo'q" emas! Bu mijozning "menga yordam bering" degan signali.
                    </p>
                </div>
            </div>
        </section>

        <!-- YOPISH TEXNIKALARI -->
        <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl mb-6 overflow-hidden print:break-inside-avoid">
            <div @click="toggleSection('closing')"
                 class="flex items-center gap-4 p-6 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-200 dark:border-gray-700">
                <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-xl flex items-center justify-center text-2xl flex-shrink-0 shadow-lg">
                    🎯
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">BITIMNI YOPISH TEXNIKALARI</h2>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">12 ta kuchli yopish usuli – "HA" javobini olish</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 px-3 py-1 rounded-full text-sm font-medium">
                        12 ta texnika
                    </span>
                    <component :is="expandedSections.closing ? ChevronUpIcon : ChevronDownIcon" class="w-6 h-6 text-gray-400" />
                </div>
            </div>

            <div v-show="expandedSections.closing" class="p-6">
                <div v-if="filteredClosingTechniques.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <span class="text-4xl block mb-2">🔍</span>
                    Hech qanday natija topilmadi
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="technique in filteredClosingTechniques" :key="technique.number"
                         class="bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl p-5 hover:border-emerald-500 dark:hover:border-emerald-400 hover:shadow-lg transition-all">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-500 text-white rounded-full flex items-center justify-center font-bold shadow">
                                {{ technique.number }}
                            </div>
                            <div class="flex gap-0.5">
                                <StarSolidIcon v-for="n in technique.power" :key="n" class="w-4 h-4 text-amber-400" />
                                <StarIcon v-for="n in (5 - technique.power)" :key="'e'+n" class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                            </div>
                        </div>
                        <h4 class="text-emerald-600 dark:text-emerald-400 font-bold mb-1">{{ technique.name }}</h4>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mb-3">{{ technique.desc }}</p>
                        <div class="relative group/close">
                            <div class="bg-gray-900 dark:bg-black rounded-lg p-3 text-gray-200 text-sm italic whitespace-pre-line mb-3 font-mono">{{ technique.script }}</div>
                            <button @click="copyToClipboard(technique.script, technique.name)"
                                    class="absolute top-2 right-2 p-1.5 bg-gray-700 hover:bg-gray-600 rounded-lg opacity-0 group-hover/close:opacity-100 transition-opacity"
                                    title="Nusxalash">
                                <ClipboardDocumentIcon class="w-4 h-4 text-gray-300" />
                            </button>
                        </div>
                        <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 text-sm">
                            <ClockIcon class="w-4 h-4" />
                            {{ technique.when }}
                        </div>
                    </div>
                </div>

                <!-- Key Rule -->
                <div class="mt-6 bg-emerald-100 dark:bg-emerald-900/30 border-2 border-emerald-300 dark:border-emerald-700 rounded-xl p-5">
                    <h4 class="text-emerald-700 dark:text-emerald-400 font-bold flex items-center gap-2 mb-2">
                        <LightBulbIcon class="w-5 h-5" />
                        YOPISH QOIDASI
                    </h4>
                    <p class="text-gray-700 dark:text-gray-300">
                        Savoldan keyin – <strong>JIM TURING!</strong> Ko'p sotuvchilar javobni kutmasdan gapira boshlaydi.<br><br>
                        <strong>Ketma-ketlik:</strong> Direct → Alternative → Summary → Scale → E'tiroz topish → Qayta yopish
                    </p>
                </div>
            </div>
        </section>

        <!-- FOLLOW-UP -->
        <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl mb-6 overflow-hidden print:break-inside-avoid">
            <div @click="toggleSection('followup')"
                 class="flex items-center gap-4 p-6 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-200 dark:border-gray-700">
                <div class="w-14 h-14 bg-gradient-to-br from-violet-500 to-purple-500 rounded-xl flex items-center justify-center text-2xl flex-shrink-0 shadow-lg">
                    📅
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">YAKUNLASH & FOLLOW-UP TIZIMI</h2>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Bitimdan keyin va kutish davrida qilinadigan ishlar</p>
                </div>
                <component :is="expandedSections.followup ? ChevronUpIcon : ChevronDownIcon" class="w-6 h-6 text-gray-400" />
            </div>

            <div v-show="expandedSections.followup" class="p-6">
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- BITIM BO'LDI -->
                    <div class="border-2 border-emerald-500 rounded-xl overflow-hidden shadow-lg">
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-500 text-white p-4 flex items-center gap-2 font-bold">
                            <CheckCircleIcon class="w-6 h-6" />
                            BITIM BO'LDI
                        </div>
                        <div class="p-5 bg-emerald-50 dark:bg-emerald-900/10">
                            <div class="bg-gray-900 dark:bg-black rounded-lg p-4 text-gray-200 text-sm mb-4 font-mono">
                                "Ajoyib qaror, [ISM]!<br><br>
                                <strong>Kelishuvimiz:</strong><br>
                                1️⃣ Men shartnoma jo'nataman – bugun<br>
                                2️⃣ Siz to'lov qilasiz – [SANA]<br>
                                3️⃣ Start – [SANA]<br><br>
                                <strong>Hamkorlik uchun rahmat!</strong>"
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-3 rounded-lg border border-emerald-200 dark:border-emerald-800">
                                    <span class="bg-emerald-500 text-white px-2.5 py-1 rounded-lg text-xs font-bold shadow">0-kun</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">📧 Shartnoma + Welcome email</span>
                                </div>
                                <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-3 rounded-lg border border-emerald-200 dark:border-emerald-800">
                                    <span class="bg-emerald-500 text-white px-2.5 py-1 rounded-lg text-xs font-bold shadow">1-kun</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">📞 Onboarding qo'ng'iroq</span>
                                </div>
                                <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-3 rounded-lg border border-emerald-200 dark:border-emerald-800">
                                    <span class="bg-emerald-500 text-white px-2.5 py-1 rounded-lg text-xs font-bold shadow">7-kun</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">📞 Qoniqish tekshiruvi</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KUTISH -->
                    <div class="border-2 border-orange-500 rounded-xl overflow-hidden shadow-lg">
                        <div class="bg-gradient-to-r from-orange-500 to-amber-500 text-white p-4 flex items-center gap-2 font-bold">
                            <ClockIcon class="w-6 h-6" />
                            KUTISH
                        </div>
                        <div class="p-5 bg-orange-50 dark:bg-orange-900/10">
                            <div class="bg-gray-900 dark:bg-black rounded-lg p-4 text-gray-200 text-sm mb-4 font-mono">
                                "Rahmat, [ISM]!<br><br>
                                1️⃣ Men material jo'nataman – bugun<br>
                                2️⃣ [KUN]da qayta qo'ng'iroq qilaman"
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-3 rounded-lg border border-orange-200 dark:border-orange-800">
                                    <span class="bg-orange-500 text-white px-2.5 py-1 rounded-lg text-xs font-bold shadow">0-kun</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">📧 Material + Taklifnoma</span>
                                </div>
                                <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-3 rounded-lg border border-orange-200 dark:border-orange-800">
                                    <span class="bg-orange-500 text-white px-2.5 py-1 rounded-lg text-xs font-bold shadow">2-kun</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">📞 "Ko'rib chiqdingizmi?"</span>
                                </div>
                                <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-3 rounded-lg border border-orange-200 dark:border-orange-800">
                                    <span class="bg-orange-500 text-white px-2.5 py-1 rounded-lg text-xs font-bold shadow">5-kun</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">📧 Case study yuborish</span>
                                </div>
                                <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-3 rounded-lg border border-orange-200 dark:border-orange-800">
                                    <span class="bg-orange-500 text-white px-2.5 py-1 rounded-lg text-xs font-bold shadow">7-kun</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">📞 "Qaroringiz qanday?"</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RAD -->
                    <div class="border-2 border-red-500 rounded-xl overflow-hidden shadow-lg">
                        <div class="bg-gradient-to-r from-red-500 to-rose-500 text-white p-4 flex items-center gap-2 font-bold">
                            <XCircleIcon class="w-6 h-6" />
                            RAD
                        </div>
                        <div class="p-5 bg-red-50 dark:bg-red-900/10">
                            <div class="bg-gray-900 dark:bg-black rounded-lg p-4 text-gray-200 text-sm mb-4 font-mono">
                                "Tushundim, [ISM].<br><br>
                                Kelajakda ehtiyoj bo'lsa – qo'ng'iroq qiling.<br>
                                Foydali material yuborib turaymi?"
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-3 rounded-lg border border-red-200 dark:border-red-800">
                                    <span class="bg-gray-500 text-white px-2.5 py-1 rounded-lg text-xs font-bold shadow">30-kun</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">📧 Foydali kontent</span>
                                </div>
                                <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-3 rounded-lg border border-red-200 dark:border-red-800">
                                    <span class="bg-gray-500 text-white px-2.5 py-1 rounded-lg text-xs font-bold shadow">60-kun</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">📧 Yangilik/Case study</span>
                                </div>
                                <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-3 rounded-lg border border-red-200 dark:border-red-800">
                                    <span class="bg-gray-500 text-white px-2.5 py-1 rounded-lg text-xs font-bold shadow">90-kun</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">📞 Qayta qo'ng'iroq (1-bosqichdan)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Summary -->
        <div class="bg-gradient-to-br from-amber-500/10 via-orange-500/5 to-yellow-500/10 dark:from-amber-500/20 dark:via-orange-500/10 dark:to-yellow-500/20 border-2 border-amber-500/30 dark:border-amber-400/40 rounded-2xl p-8 text-center">
            <h2 class="text-2xl font-bold text-amber-600 dark:text-amber-400 mb-6">🏆 MUVAFFAQIYAT FORMULASI</h2>

            <div class="bg-white/60 dark:bg-gray-800/60 rounded-xl p-6 mb-6 shadow-inner">
                <div class="flex flex-wrap items-center justify-center gap-2 md:gap-3 text-sm md:text-base">
                    <span class="bg-amber-100 dark:bg-amber-900/30 text-gray-800 dark:text-gray-200 px-3 py-2 rounded-lg font-medium">METODOLOGIYA</span>
                    <span class="text-amber-500 font-bold text-xl">+</span>
                    <span class="bg-amber-100 dark:bg-amber-900/30 text-gray-800 dark:text-gray-200 px-3 py-2 rounded-lg font-medium">PSIXOLOGIYA</span>
                    <span class="text-amber-500 font-bold text-xl">+</span>
                    <span class="bg-amber-100 dark:bg-amber-900/30 text-gray-800 dark:text-gray-200 px-3 py-2 rounded-lg font-medium">NLP</span>
                    <span class="text-amber-500 font-bold text-xl">+</span>
                    <span class="bg-amber-100 dark:bg-amber-900/30 text-gray-800 dark:text-gray-200 px-3 py-2 rounded-lg font-medium">SKRIPT</span>
                    <span class="text-amber-500 font-bold text-xl">=</span>
                    <span class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 px-4 py-2 rounded-lg font-bold shadow">BITIM</span>
                </div>
            </div>

            <div class="flex flex-wrap justify-center gap-8 mb-6">
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-amber-500">70%</div>
                    <div class="text-gray-500 dark:text-gray-400">Tinglash</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold text-amber-500">30%</div>
                    <div class="text-gray-500 dark:text-gray-400">Gapirish</div>
                </div>
            </div>

            <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto leading-relaxed">
                <strong class="text-amber-600 dark:text-amber-400">Esda tuting:</strong> Eng kuchli texnika – <strong>HAQIQIY QIZIQISH VA YORDAM BERISH ISTAGI</strong>.<br>
                Texnikalar faqat vosita. Mijozga <strong>haqiqiy qiymat</strong> bering!<br><br>
                <em class="text-gray-500">"Sotuvchi sotmaydi – mijoz sotib oladi. Sotuvchi faqat yordam beradi."</em>
            </p>
        </div>
    </div>
</template>

<style scoped>
@media print {
    .print\:hidden {
        display: none !important;
    }
    .print\:break-inside-avoid {
        break-inside: avoid;
    }
}
</style>
