<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { SparklesIcon, DocumentTextIcon, MegaphoneIcon, StarIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    dreamBuyer: { type: Object, required: true },
    panelType: {
        type: String,
        default: 'business',
        validator: (v) => ['business', 'marketing', 'finance', 'operator', 'saleshead'].includes(v),
    },
});

const layoutComponent = computed(() => {
    const layouts = {
        business: BusinessLayout,
        marketing: MarketingLayout,
        finance: FinanceLayout,
        operator: OperatorLayout,
        saleshead: SalesHeadLayout,
    };
    return layouts[props.panelType] || BaseLayout;
});

const getRoute = (name, params = null) => {
    const prefix = props.panelType + '.';
    return params ? route(prefix + name, params) : route(prefix + name);
};

const showDeleteModal = ref(false);

// Parse text fields
const parseField = (text) => {
    if (!text) return [];
    return text.split('\n').filter(item => item.trim());
};

const whereSpendTime = computed(() => parseField(props.dreamBuyer.where_spend_time));
const infoSources = computed(() => parseField(props.dreamBuyer.info_sources));
const frustrations = computed(() => parseField(props.dreamBuyer.frustrations));
const dreams = computed(() => parseField(props.dreamBuyer.dreams));
const fears = computed(() => parseField(props.dreamBuyer.fears));
const communicationPreferences = computed(() => parseField(props.dreamBuyer.communication_preferences));
const dailyRoutine = computed(() => parseField(props.dreamBuyer.daily_routine));
const happinessTriggers = computed(() => parseField(props.dreamBuyer.happiness_triggers));

const hasData = computed(() => {
    return whereSpendTime.value.length > 0 ||
           frustrations.value.length > 0 ||
           dreams.value.length > 0 ||
           fears.value.length > 0;
});

// Sections for data grid
const sections = computed(() => {
    const all = [
        { key: 'where', icon: '📍', title: t('dream_buyer.where_spend_time_title'), items: whereSpendTime.value, type: 'tags', iconBg: 'bg-blue-50 dark:bg-blue-900/20', tagClass: 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300', dotClass: 'bg-blue-500' },
        { key: 'sources', icon: '🔍', title: t('dream_buyer.info_sources_title'), items: infoSources.value, type: 'tags', iconBg: 'bg-indigo-50 dark:bg-indigo-900/20', tagClass: 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300', dotClass: 'bg-indigo-500' },
        { key: 'frustrations', icon: '😤', title: t('dream_buyer.frustrations_title'), items: frustrations.value, type: 'list', iconBg: 'bg-red-50 dark:bg-red-900/20', tagClass: '', dotClass: 'bg-red-500' },
        { key: 'dreams', icon: '✨', title: t('dream_buyer.dreams_title'), items: dreams.value, type: 'list', iconBg: 'bg-emerald-50 dark:bg-emerald-900/20', tagClass: '', dotClass: 'bg-emerald-500' },
        { key: 'fears', icon: '😰', title: t('dream_buyer.fears_title'), items: fears.value, type: 'list', iconBg: 'bg-amber-50 dark:bg-amber-900/20', tagClass: '', dotClass: 'bg-amber-500' },
        { key: 'comm', icon: '💬', title: t('dream_buyer.communication_title'), items: communicationPreferences.value, type: 'tags', iconBg: 'bg-purple-50 dark:bg-purple-900/20', tagClass: 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300', dotClass: 'bg-purple-500' },
        { key: 'daily', icon: '📅', title: t('dream_buyer.daily_routine_title'), items: dailyRoutine.value, type: 'list', iconBg: 'bg-cyan-50 dark:bg-cyan-900/20', tagClass: '', dotClass: 'bg-cyan-500' },
        { key: 'happy', icon: '😊', title: t('dream_buyer.happiness_title'), items: happinessTriggers.value, type: 'list', iconBg: 'bg-yellow-50 dark:bg-yellow-900/20', tagClass: '', dotClass: 'bg-yellow-500' },
    ];
    return all.filter(s => s.items.length > 0);
});

// ========== Psixologik portret algoritmi ==========

// Sotib olish qaror turi
const buyerDecisionType = computed(() => {
    const f = frustrations.value.join(' ').toLowerCase();
    const d = dreams.value.join(' ').toLowerCase();
    const fe = fears.value.join(' ').toLowerCase();

    const emotional = ['xohlaydi', 'orzu', 'baxt', 'hurmat', 'erkinlik', 'oila', 'sayohat'].filter(w => d.includes(w) || f.includes(w)).length;
    const rational = ['pul', 'daromad', 'natija', 'vaqt', 'samaradorlik', 'xarajat', 'foyda'].filter(w => d.includes(w) || f.includes(w)).length;
    const fearDriven = ['yo\'qotish', 'sharmanda', 'muvaffaqiyatsiz', 'xato', 'orqada'].filter(w => fe.includes(w) || f.includes(w)).length;

    if (fearDriven >= 2) return { type: 'fear', label: "Xavfdan qochuvchi", desc: "Qarorlarni xavf-xatarni kamaytirish asosida qabul qiladi. Kafolatlar va ishonch muhim.", color: 'amber', icon: '🛡️', marketing: "Kafolat, bepul sinov, risk-free taklif ishlating" };
    if (emotional > rational) return { type: 'emotional', label: "Hissiy qaror qiluvchi", desc: "Qarorlarni his-tuyg'ular asosida qabul qiladi. Hikoyalar va ijtimoiy isbotlar ta'sir qiladi.", color: 'pink', icon: '❤️', marketing: "Muvaffaqiyat hikoyalari, testimoniallar, hayot o'zgarishi va'dalari ishlating" };
    return { type: 'rational', label: "Mantiqiy qaror qiluvchi", desc: "Raqamlar, faktlar va ROI asosida qaror qabul qiladi. Natijalar va statistika muhim.", color: 'blue', icon: '🧠', marketing: "Raqamlar, case study, ROI kalkulyator, bepul audit ishlating" };
});

// Sotib olish tayyorligi
const buyerReadiness = computed(() => {
    let score = 0;
    if (frustrations.value.length >= 3) score += 30; // Ko'p muammo = kuchli ehtiyoj
    else if (frustrations.value.length >= 1) score += 15;
    if (dreams.value.length >= 3) score += 25; // Aniq maqsadlar
    else if (dreams.value.length >= 1) score += 10;
    if (fears.value.length >= 2) score += 15; // Qo'rquvlar = harakat qilmaydi
    else if (fears.value.length === 0) score += 20; // Qo'rquv kam = tezroq qaror
    if (whereSpendTime.value.length >= 3) score += 15; // Ko'p kanal = faol
    if (communicationPreferences.value.length >= 2) score += 15;
    return Math.min(score, 100);
});

const readinessLevel = computed(() => {
    const s = buyerReadiness.value;
    if (s >= 75) return { label: 'Yuqori', desc: "Bu mijoz sotib olishga tayyor — faqat to'g'ri taklif kerak", color: 'emerald' };
    if (s >= 45) return { label: "O'rta", desc: "Qiziqish bor, lekin ishontirish kerak — testimonial va kafolatlar ishlating", color: 'amber' };
    return { label: 'Past', desc: "Hali muammoni tan olmagan — ta'lim beruvchi kontent yarating", color: 'red' };
});

// Kanal tavsiyalari
const channelRecommendations = computed(() => {
    const platforms = whereSpendTime.value.join(', ').toLowerCase();
    const comms = communicationPreferences.value.join(', ').toLowerCase();
    const channels = [];

    const channelData = [
        { name: 'Telegram', keys: ['telegram'], priority: 0, svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>', color: '#0088cc', type: 'Xabar + Kanal', tip: "Funnel bot + kanal orqali warm-up qiling" },
        { name: 'Instagram', keys: ['instagram'], priority: 0, svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>', color: '#E4405F', type: 'Vizual kontent', tip: "Reels + Stories orqali muammolarni ko'rsating" },
        { name: 'YouTube', keys: ['youtube'], priority: 0, svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>', color: '#FF0000', type: 'Uzun kontent', tip: "How-to va case study videolar ishlang" },
        { name: 'Facebook', keys: ['facebook'], priority: 0, svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>', color: '#1877F2', type: 'Guruhlar', tip: "Guruh yaratib, community quring" },
        { name: 'LinkedIn', keys: ['linkedin'], priority: 0, svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>', color: '#0A66C2', type: 'B2B tarmoq', tip: "Ekspert kontent va networking" },
        { name: 'TikTok', keys: ['tiktok'], priority: 0, svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>', color: '#000000', type: 'Qisqa video', tip: "Og'riq nuqtalarni qisqa videoda ko'rsating" },
        { name: 'WhatsApp', keys: ['whatsapp'], priority: 0, svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>', color: '#25D366', type: 'Shaxsiy aloqa', tip: "1-on-1 suhbat va follow-up uchun" },
        { name: 'Email', keys: ['email', 'pochta'], priority: 0, svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>', color: '#6366F1', type: 'Nurturing', tip: "Ketma-ket email seriyasi yarating" },
        { name: 'Offline', keys: ['offline', 'yuzma-yuz', 'tadbirlar'], priority: 0, svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>', color: '#8B5CF6', type: 'Jonli uchrashuvlar', tip: "Seminarlar va networking tadbirlari" },
    ];

    channelData.forEach(ch => {
        let score = 0;
        ch.keys.forEach(k => {
            if (platforms.includes(k)) score += 40;
            if (comms.includes(k)) score += 30;
        });
        if (score > 0) channels.push({ ...ch, priority: score });
    });

    return channels.sort((a, b) => b.priority - a.priority).slice(0, 5);
});

// Xabar strategiyasi
const messagingStrategy = computed(() => {
    const dt = buyerDecisionType.value.type;
    const strategies = [];

    if (frustrations.value.length > 0) {
        strategies.push({ label: "Og'riq nuqta", desc: frustrations.value[0], approach: dt === 'emotional' ? "Hikoya orqali ko'rsating" : "Raqamlar bilan isbotlang", color: 'red' });
    }
    if (dreams.value.length > 0) {
        strategies.push({ label: "Orzu/maqsad", desc: dreams.value[0], approach: dt === 'rational' ? "ROI va natija ko'rsating" : "Transformatsiya va'da qiling", color: 'emerald' });
    }
    if (fears.value.length > 0) {
        strategies.push({ label: "Qo'rquv bartaraf", desc: fears.value[0], approach: "Kafolat va bepul sinov taklif qiling", color: 'amber' });
    }
    return strategies;
});

// AI Generation States
const generatingContentIdeas = ref(false);
const generatingAdCopy = ref(false);
const contentIdeas = ref([]);
const adCopy = ref(null);
const adCopyProduct = ref('');
const showAdCopyModal = ref(false);

// Generate Content Ideas
const generateContentIdeas = async () => {
    generatingContentIdeas.value = true;
    try {
        const response = await fetch(getRoute('dream-buyer.content-ideas', props.dreamBuyer.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const data = await response.json();
        if (data.success) {
            contentIdeas.value = data.content_ideas;
        }
    } catch (error) {
        console.error('Content Ideas xatosi:', error);
    } finally {
        generatingContentIdeas.value = false;
    }
};

// Generate Ad Copy
const generateAdCopy = async () => {
    if (!adCopyProduct.value.trim()) return;
    generatingAdCopy.value = true;
    try {
        const response = await fetch(getRoute('dream-buyer.ad-copy', props.dreamBuyer.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ product: adCopyProduct.value }),
        });
        const data = await response.json();
        if (data.success) {
            adCopy.value = data.ad_copy;
            showAdCopyModal.value = false;
        }
    } catch (error) {
        console.error('Ad Copy xatosi:', error);
    } finally {
        generatingAdCopy.value = false;
    }
};

// Set as Primary
const setPrimary = () => {
    router.post(getRoute('dream-buyer.set-primary', props.dreamBuyer.id));
};

const deleteBuyer = () => {
    router.delete(getRoute('dream-buyer.destroy', props.dreamBuyer.id));
    showDeleteModal.value = false;
};

const copyLink = () => {
    if (props.dreamBuyer.survey) {
        const link = `${window.location.origin}/s/${props.dreamBuyer.survey.slug}`;
        navigator.clipboard.writeText(link);
    }
};
</script>

<template>
    <component :is="layoutComponent" :title="dreamBuyer.name">
        <Head :title="dreamBuyer.name" />

        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="getRoute('dream-buyer.index')" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ dreamBuyer.name }}</h1>
                            <span v-if="dreamBuyer.is_primary" class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded text-[10px] font-bold">
                                <StarIcon class="w-3 h-3" /> Asosiy
                            </span>
                        </div>
                        <p v-if="dreamBuyer.description" class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ dreamBuyer.description }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    <button v-if="!dreamBuyer.is_primary" @click="setPrimary" class="p-2 text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors" :title="t('dream_buyer.set_primary')">
                        <StarIcon class="w-4 h-4" />
                    </button>
                    <Link :href="getRoute('dream-buyer.edit', dreamBuyer.id)" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        {{ t('dream_buyer.edit') }}
                    </Link>
                    <button @click="showDeleteModal = true" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>
            </div>

            <!-- Survey Link -->
            <div v-if="dreamBuyer.survey" class="bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-200/50 dark:border-indigo-800/50 rounded-xl p-4 flex items-center justify-between">
                <div class="flex items-center gap-3 text-sm">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    <span class="font-medium text-indigo-700 dark:text-indigo-400">{{ t('dream_buyer.custdev_survey') }}:</span>
                    <code class="text-indigo-600 dark:text-indigo-300">/s/{{ dreamBuyer.survey.slug }}</code>
                </div>
                <div class="flex gap-2">
                    <button @click="copyLink" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-medium transition-colors">{{ t('dream_buyer.copy_link') }}</button>
                    <Link :href="getRoute('custdev.results', { custdev: dreamBuyer.survey.id })" class="px-3 py-1.5 bg-white dark:bg-gray-800 border border-indigo-300 dark:border-indigo-700 text-indigo-700 dark:text-indigo-400 rounded-lg text-xs font-medium hover:bg-indigo-50 transition-colors">{{ t('dream_buyer.results') }}</Link>
                </div>
            </div>

            <!-- AI Tools (compact) -->
            <div class="flex flex-wrap gap-2">
                <button @click="generateContentIdeas" :disabled="generatingContentIdeas" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 border border-purple-200/50 dark:border-purple-700/50 rounded-lg hover:bg-purple-100 transition-colors disabled:opacity-50">
                    <SparklesIcon class="w-4 h-4" />
                    <span v-if="generatingContentIdeas">{{ t('dream_buyer.generating') }}</span>
                    <span v-else>{{ t('dream_buyer.content_ideas') }}</span>
                </button>
                <button @click="showAdCopyModal = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200/50 dark:border-indigo-700/50 rounded-lg hover:bg-indigo-100 transition-colors">
                    <MegaphoneIcon class="w-4 h-4" />
                    {{ t('dream_buyer.create_ad_copy') }}
                </button>
            </div>

            <!-- Generated Content -->
            <div v-if="contentIdeas.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2"><DocumentTextIcon class="w-4 h-4 text-purple-600" /> {{ t('dream_buyer.content_ideas') }}</h3>
                <div class="space-y-2">
                    <div v-for="(idea, i) in contentIdeas" :key="i" class="p-3 bg-purple-50 dark:bg-purple-900/10 rounded-lg text-sm text-gray-800 dark:text-gray-200">{{ idea }}</div>
                </div>
            </div>

            <div v-if="adCopy" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 space-y-3">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2"><MegaphoneIcon class="w-4 h-4 text-indigo-600" /> {{ t('dream_buyer.ad_copy') }}</h3>
                <div v-if="adCopy.headline" class="p-3 bg-indigo-50 dark:bg-indigo-900/10 rounded-lg"><p class="text-[10px] text-indigo-500 font-semibold mb-0.5">{{ t('dream_buyer.headline') }}</p><p class="text-sm font-bold text-gray-900 dark:text-white">{{ adCopy.headline }}</p></div>
                <div v-if="adCopy.body" class="p-3 bg-gray-50 dark:bg-gray-900/30 rounded-lg"><p class="text-[10px] text-gray-500 font-semibold mb-0.5">{{ t('dream_buyer.main_text') }}</p><p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ adCopy.body }}</p></div>
                <div v-if="adCopy.cta" class="p-3 bg-emerald-50 dark:bg-emerald-900/10 rounded-lg"><p class="text-[10px] text-emerald-500 font-semibold mb-0.5">CTA</p><p class="text-sm font-semibold text-gray-900 dark:text-white">{{ adCopy.cta }}</p></div>
            </div>

            <!-- Psixologik Portret -->
            <div v-if="hasData" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Qaror turi -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-lg">{{ buyerDecisionType.icon }}</span>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Qaror qilish turi</h3>
                    </div>
                    <div :class="['px-3 py-2 rounded-lg mb-3', 'bg-' + buyerDecisionType.color + '-50 dark:bg-' + buyerDecisionType.color + '-900/20']">
                        <p :class="['text-sm font-bold', 'text-' + buyerDecisionType.color + '-700 dark:text-' + buyerDecisionType.color + '-400']">{{ buyerDecisionType.label }}</p>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">{{ buyerDecisionType.desc }}</p>
                    <div class="p-2.5 bg-indigo-50 dark:bg-indigo-900/10 rounded-lg">
                        <p class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 mb-0.5">Marketing tavsiya:</p>
                        <p class="text-xs text-indigo-700 dark:text-indigo-300">{{ buyerDecisionType.marketing }}</p>
                    </div>
                </div>

                <!-- Sotib olish tayyorligi -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-lg">📊</span>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Sotib olish tayyorligi</h3>
                    </div>
                    <div class="flex items-end gap-3 mb-3">
                        <span :class="['text-3xl font-black', 'text-' + readinessLevel.color + '-600 dark:text-' + readinessLevel.color + '-400']">{{ buyerReadiness }}%</span>
                        <span :class="['px-2 py-0.5 text-xs font-bold rounded', 'bg-' + readinessLevel.color + '-50 dark:bg-' + readinessLevel.color + '-900/20 text-' + readinessLevel.color + '-700 dark:text-' + readinessLevel.color + '-400']">{{ readinessLevel.label }}</span>
                    </div>
                    <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mb-3">
                        <div :class="['h-full rounded-full transition-all', 'bg-' + readinessLevel.color + '-500']" :style="{ width: buyerReadiness + '%' }"></div>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ readinessLevel.desc }}</p>
                </div>

                <!-- Xabar strategiyasi -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-lg">🎯</span>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Xabar strategiyasi</h3>
                    </div>
                    <div class="space-y-2.5">
                        <div v-for="(s, i) in messagingStrategy" :key="i" class="p-2.5 bg-gray-50 dark:bg-gray-900/30 rounded-lg">
                            <div class="flex items-center gap-1.5 mb-1">
                                <span :class="['w-1.5 h-1.5 rounded-full', 'bg-' + s.color + '-500']"></span>
                                <span class="text-[10px] font-bold text-gray-500 uppercase">{{ s.label }}</span>
                            </div>
                            <p class="text-xs text-gray-900 dark:text-white font-medium mb-0.5">{{ s.desc }}</p>
                            <p class="text-[10px] text-indigo-600 dark:text-indigo-400">→ {{ s.approach }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tavsiya etiladigan kanallar -->
            <div v-if="channelRecommendations.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700/50 flex items-center gap-2">
                    <span class="text-base">📡</span>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Tavsiya etiladigan marketing kanallari</h3>
                    <span class="ml-auto text-xs text-gray-400">Ustuvorlik bo'yicha</span>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                        <div v-for="(ch, i) in channelRecommendations" :key="ch.name" class="relative p-3 bg-gray-50 dark:bg-gray-900/30 rounded-xl">
                            <span v-if="i === 0" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-indigo-600 text-white rounded-full flex items-center justify-center text-[9px] font-bold">1</span>
                            <div class="flex justify-center mb-2">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center" :style="{ backgroundColor: ch.color + '15' }">
                                    <div class="w-5 h-5" :style="{ color: ch.color }" v-html="ch.svg"></div>
                                </div>
                            </div>
                            <p class="text-xs font-bold text-gray-900 dark:text-white text-center">{{ ch.name }}</p>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 text-center mb-2">{{ ch.type }}</p>
                            <div class="w-full h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mb-2">
                                <div class="h-full rounded-full" :style="{ width: Math.min(ch.priority, 70) / 70 * 100 + '%', backgroundColor: ch.color }"></div>
                            </div>
                            <p class="text-[10px] text-gray-600 dark:text-gray-400 leading-tight">{{ ch.tip }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Data Warning -->
            <div v-if="!hasData" class="bg-amber-50/50 dark:bg-amber-900/10 border border-amber-200/50 dark:border-amber-800/50 rounded-xl p-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <p class="text-sm text-amber-700 dark:text-amber-400 flex-1">{{ t('dream_buyer.profile_incomplete') }} — {{ t('dream_buyer.profile_incomplete_desc') }}</p>
                <Link :href="getRoute('dream-buyer.edit', dreamBuyer.id)" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-medium transition-colors flex-shrink-0">{{ t('dream_buyer.fill_profile') }}</Link>
            </div>

            <!-- Data Grid -->
            <div v-if="hasData" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div v-for="section in sections" :key="section.key" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700/50 flex items-center gap-2.5">
                        <div :class="['w-8 h-8 rounded-lg flex items-center justify-center', section.iconBg]">
                            <span class="text-base">{{ section.icon }}</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ section.title }}</h3>
                        </div>
                    </div>
                    <div class="p-4">
                        <div v-if="section.type === 'tags'" class="flex flex-wrap gap-1.5">
                            <span v-for="item in section.items" :key="item" :class="['px-2.5 py-1 rounded-lg text-xs font-medium', section.tagClass]">{{ item }}</span>
                        </div>
                        <ul v-else class="space-y-1.5">
                            <li v-for="item in section.items" :key="item" class="flex items-start gap-2 p-2.5 bg-gray-50 dark:bg-gray-900/30 rounded-lg">
                                <span :class="['w-1.5 h-1.5 mt-1.5 rounded-full flex-shrink-0', section.dotClass]"></span>
                                <span class="text-sm text-gray-800 dark:text-gray-200">{{ item }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ad Copy Modal -->
        <Teleport to="body">
            <div v-if="showAdCopyModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ t('dream_buyer.create_ad_copy') }}</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ t('dream_buyer.product_name') }}
                            </label>
                            <input
                                v-model="adCopyProduct"
                                type="text"
                                :placeholder="t('dream_buyer.product_placeholder')"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div class="flex gap-3 justify-end">
                            <button @click="showAdCopyModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-all">
                                {{ t('common.cancel') }}
                            </button>
                            <button
                                @click="generateAdCopy"
                                :disabled="generatingAdCopy || !adCopyProduct.trim()"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-all disabled:opacity-50"
                            >
                                <span v-if="generatingAdCopy">{{ t('dream_buyer.generating') }}</span>
                                <span v-else>{{ t('dream_buyer.create') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ t('dream_buyer.delete_confirm_title') }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('dream_buyer.delete_warning') }}</p>
                            </div>
                        </div>
                        <p class="text-gray-700 dark:text-gray-300 mb-6">
                            {{ t('dream_buyer.delete_confirm', { name: dreamBuyer.name }) }}
                        </p>
                        <div class="flex gap-3">
                            <button @click="showDeleteModal = false" class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors">
                                {{ t('common.cancel') }}
                            </button>
                            <button @click="deleteBuyer" class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-colors">
                                {{ t('common.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </component>
</template>
