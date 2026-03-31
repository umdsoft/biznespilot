<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    dreamBuyers: Array,
    panelType: {
        type: String,
        required: true,
        validator: (value) => ['business', 'marketing', 'finance', 'operator', 'saleshead'].includes(value),
    },
});

// Route helpers based on panel type
const getRoute = (name, params = null) => {
    const prefix = props.panelType + '.';
    return params ? route(prefix + name, params) : route(prefix + name);
};

// Check if panel is read-only (operator and saleshead can only view)
const isReadOnly = computed(() => ['operator', 'saleshead'].includes(props.panelType));

const deletingBuyer = ref(null);
const selectedBuyerId = ref(null);
const searchQuery = ref('');

const initializeSelection = () => {
    if (!props.dreamBuyers?.length) return null;
    const primary = props.dreamBuyers.find(b => b.is_primary);
    return primary?.id || props.dreamBuyers[0]?.id;
};
selectedBuyerId.value = initializeSelection();

const selectedBuyer = computed(() => {
    return props.dreamBuyers?.find(b => b.id === selectedBuyerId.value) || null;
});

const filteredBuyers = computed(() => {
    if (!searchQuery.value) return props.dreamBuyers || [];
    const q = searchQuery.value.toLowerCase();
    return (props.dreamBuyers || []).filter(b =>
        b.name?.toLowerCase().includes(q) ||
        b.description?.toLowerCase().includes(q)
    );
});

const getCompletion = (buyer) => {
    const fields = ['name', 'description', 'where_spend_time', 'info_sources', 'frustrations', 'dreams', 'fears', 'communication_preferences', 'language_style'];
    const filled = fields.filter(f => buyer[f] && buyer[f].trim()).length;
    return Math.round((filled / fields.length) * 100);
};

const parseField = (text) => {
    if (!text) return [];
    return text.split('\n').filter(item => item.trim());
};

const whereSpendTime = computed(() => parseField(selectedBuyer.value?.where_spend_time));
const infoSources = computed(() => parseField(selectedBuyer.value?.info_sources));
const frustrations = computed(() => parseField(selectedBuyer.value?.frustrations));
const dreams = computed(() => parseField(selectedBuyer.value?.dreams));
const fears = computed(() => parseField(selectedBuyer.value?.fears));
const communicationPrefs = computed(() => parseField(selectedBuyer.value?.communication_preferences));
const languageStyle = computed(() => parseField(selectedBuyer.value?.language_style));
const dailyRoutine = computed(() => parseField(selectedBuyer.value?.daily_routine));
const happinessTriggers = computed(() => parseField(selectedBuyer.value?.happiness_triggers));

const hasData = computed(() => {
    return whereSpendTime.value.length > 0 ||
           frustrations.value.length > 0 ||
           dreams.value.length > 0 ||
           fears.value.length > 0;
});

const setPrimary = (dreamBuyer) => {
    router.post(getRoute('dream-buyer.set-primary', dreamBuyer.id), {}, {
        preserveScroll: true,
    });
};

const confirmDelete = (dreamBuyer) => {
    deletingBuyer.value = dreamBuyer;
};

const deleteBuyer = () => {
    if (deletingBuyer.value) {
        const deletedId = deletingBuyer.value.id;
        router.delete(getRoute('dream-buyer.destroy', deletedId), {
            preserveScroll: true,
            onSuccess: () => {
                if (selectedBuyerId.value === deletedId) {
                    selectedBuyerId.value = props.dreamBuyers?.find(b => b.id !== deletedId)?.id || null;
                }
                deletingBuyer.value = null;
            },
        });
    }
};

const cancelDelete = () => {
    deletingBuyer.value = null;
};

const insightSections = computed(() => [
    { key: 'whereSpendTime', title: t('dream_buyer.where_spend_time'), subtitle: t('dream_buyer.where_spend_time_sub'), icon: '📍', color: 'blue', data: whereSpendTime, type: 'tags' },
    { key: 'infoSources', title: t('dream_buyer.info_sources'), subtitle: t('dream_buyer.info_sources_sub'), icon: '🔍', color: 'indigo', data: infoSources, type: 'tags' },
    { key: 'frustrations', title: t('dream_buyer.frustrations'), subtitle: t('dream_buyer.frustrations_sub'), icon: '😤', color: 'red', data: frustrations, type: 'list' },
    { key: 'dreams', title: t('dream_buyer.dreams'), subtitle: t('dream_buyer.dreams_sub'), icon: '✨', color: 'green', data: dreams, type: 'list' },
    { key: 'fears', title: t('dream_buyer.fears'), subtitle: t('dream_buyer.fears_sub'), icon: '😰', color: 'amber', data: fears, type: 'list' },
    { key: 'communicationPrefs', title: t('dream_buyer.communication_prefs'), subtitle: t('dream_buyer.communication_prefs_sub'), icon: '💬', color: 'purple', data: communicationPrefs, type: 'tags' },
    { key: 'languageStyle', title: t('dream_buyer.language_style'), subtitle: t('dream_buyer.language_style_sub'), icon: '🗣️', color: 'pink', data: languageStyle, type: 'tags' },
    { key: 'dailyRoutine', title: t('dream_buyer.daily_routine'), subtitle: t('dream_buyer.daily_routine_sub'), icon: '📅', color: 'cyan', data: dailyRoutine, type: 'list' },
    { key: 'happinessTriggers', title: t('dream_buyer.happiness_triggers'), subtitle: t('dream_buyer.happiness_triggers_sub'), icon: '😊', color: 'emerald', data: happinessTriggers, type: 'list' }
]);

// ========== Umumiy tahlil algoritmi ==========
const aggregateAnalysis = computed(() => {
    const buyers = props.dreamBuyers || [];
    if (buyers.length === 0) return null;

    // Vergul yoki yangi qator bilan ajratish (ikki formatni ham qo'llab-quvvatlash, double-count qilmaslik)
    const parseAny = (text) => {
        if (!text) return [];
        // Avval vergul bilan ajratamiz, keyin har birini trim
        const items = text.split(/[,\n]/).map(i => i.trim().toLowerCase()).filter(Boolean);
        return [...new Set(items)]; // dublikatlarni olib tashlash
    };

    const allPlatforms = {};
    const allFrustrations = {};
    const allDreams = {};
    const allFears = {};
    const allComms = {};

    buyers.forEach(b => {
        parseAny(b.where_spend_time).forEach(p => { allPlatforms[p] = (allPlatforms[p] || 0) + 1; });
        parseAny(b.frustrations).forEach(f => { allFrustrations[f] = (allFrustrations[f] || 0) + 1; });
        parseAny(b.dreams).forEach(d => { allDreams[d] = (allDreams[d] || 0) + 1; });
        parseAny(b.fears).forEach(f => { allFears[f] = (allFears[f] || 0) + 1; });
        parseAny(b.communication_preferences).forEach(c => { allComms[c] = (allComms[c] || 0) + 1; });
    });

    const sortByCount = (obj) => Object.entries(obj).sort((a, b) => b[1] - a[1]).map(([name, count]) => ({ name, count, percent: Math.round(count / buyers.length * 100) }));

    // Profil to'liqligi
    const fields = ['where_spend_time', 'info_sources', 'frustrations', 'dreams', 'fears', 'communication_preferences'];
    const totalFields = buyers.length * fields.length;
    const filledFields = buyers.reduce((sum, b) => sum + fields.filter(f => b[f] && b[f].trim()).length, 0);
    const completionPercent = Math.round(filledFields / totalFields * 100);

    // Ishonchlilik — profillar soni asosida
    const confidence = buyers.length >= 10 ? 'Yuqori' : buyers.length >= 5 ? "O'rta" : buyers.length >= 2 ? 'Past' : 'Juda past';
    const confidenceColor = buyers.length >= 10 ? 'emerald' : buyers.length >= 5 ? 'blue' : buyers.length >= 2 ? 'amber' : 'red';
    const confidenceDesc = buyers.length >= 10 ? 'Statistik jihatdan ishonchli' : buyers.length >= 5 ? "Yaxshi boshlang'ich baza" : "Ko'proq profil qo'shing";

    return {
        totalBuyers: buyers.length,
        completion: completionPercent,
        confidence,
        confidenceColor,
        confidenceDesc,
        topPlatforms: sortByCount(allPlatforms).slice(0, 5),
        topFrustrations: sortByCount(allFrustrations).slice(0, 5),
        topDreams: sortByCount(allDreams).slice(0, 5),
        topFears: sortByCount(allFears).slice(0, 3),
        topComms: sortByCount(allComms).slice(0, 4),
    };
});

const getColorClasses = (color) => {
    const colors = {
        blue: { bg: 'bg-blue-500', bgLight: 'bg-blue-50 dark:bg-blue-900/20', text: 'text-blue-800 dark:text-blue-300', border: 'border-blue-100 dark:border-blue-800', gradient: 'from-blue-500 to-blue-600' },
        indigo: { bg: 'bg-indigo-500', bgLight: 'bg-indigo-50 dark:bg-indigo-900/20', text: 'text-indigo-800 dark:text-indigo-300', border: 'border-indigo-100 dark:border-indigo-800', gradient: 'from-indigo-500 to-indigo-600' },
        red: { bg: 'bg-red-500', bgLight: 'bg-red-50 dark:bg-red-900/20', text: 'text-red-800 dark:text-red-300', border: 'border-red-100 dark:border-red-800', gradient: 'from-red-500 to-red-600' },
        green: { bg: 'bg-green-500', bgLight: 'bg-green-50 dark:bg-green-900/20', text: 'text-green-800 dark:text-green-300', border: 'border-green-100 dark:border-green-800', gradient: 'from-green-500 to-green-600' },
        amber: { bg: 'bg-amber-500', bgLight: 'bg-amber-50 dark:bg-amber-900/20', text: 'text-amber-800 dark:text-amber-300', border: 'border-amber-100 dark:border-amber-800', gradient: 'from-amber-500 to-amber-600' },
        purple: { bg: 'bg-purple-500', bgLight: 'bg-purple-50 dark:bg-purple-900/20', text: 'text-purple-800 dark:text-purple-300', border: 'border-purple-100 dark:border-purple-800', gradient: 'from-purple-500 to-purple-600' },
        pink: { bg: 'bg-pink-500', bgLight: 'bg-pink-50 dark:bg-pink-900/20', text: 'text-pink-800 dark:text-pink-300', border: 'border-pink-100 dark:border-pink-800', gradient: 'from-pink-500 to-pink-600' },
        cyan: { bg: 'bg-cyan-500', bgLight: 'bg-cyan-50 dark:bg-cyan-900/20', text: 'text-cyan-800 dark:text-cyan-300', border: 'border-cyan-100 dark:border-cyan-800', gradient: 'from-cyan-500 to-cyan-600' },
        emerald: { bg: 'bg-emerald-500', bgLight: 'bg-emerald-50 dark:bg-emerald-900/20', text: 'text-emerald-800 dark:text-emerald-300', border: 'border-emerald-100 dark:border-emerald-800', gradient: 'from-emerald-500 to-emerald-600' },
    };
    return colors[color] || colors.blue;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ t('dream_buyer.title') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ t('dream_buyer.subtitle') }}</p>
            </div>
            <Link
                v-if="!isReadOnly"
                :href="getRoute('dream-buyer.create')"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                {{ t('dream_buyer.new_profile') }}
            </Link>
        </div>

        <!-- Empty State -->
        <div v-if="!dreamBuyers || dreamBuyers.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700">
            <div class="max-w-2xl mx-auto px-6 py-16 text-center">
                <!-- Icon -->
                <div class="w-16 h-16 mx-auto mb-6 bg-indigo-100 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>

                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">{{ t('dream_buyer.create_title') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-8 max-w-md mx-auto">
                    {{ t('dream_buyer.create_desc') }}
                </p>

                <!-- Steps -->
                <div class="grid grid-cols-3 gap-3 mb-8">
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/30 rounded-xl">
                        <div class="w-8 h-8 mx-auto mb-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </div>
                        <h4 class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ t('dream_buyer.where_find') }}</h4>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ t('dream_buyer.where_find_desc') }}</p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/30 rounded-xl">
                        <div class="w-8 h-8 mx-auto mb-2 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h4 class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ t('dream_buyer.problems') }}</h4>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ t('dream_buyer.problems_desc') }}</p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/30 rounded-xl">
                        <div class="w-8 h-8 mx-auto mb-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                        </div>
                        <h4 class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ t('dream_buyer.wants') }}</h4>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ t('dream_buyer.wants_desc') }}</p>
                    </div>
                </div>

                <Link
                    v-if="!isReadOnly"
                    :href="getRoute('dream-buyer.create')"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ t('dream_buyer.create_button') }}
                </Link>
                <p v-else class="text-sm text-gray-400">{{ t('dream_buyer.not_created') }}</p>
            </div>
        </div>

        <!-- Umumiy Tahlil (profil bor bo'lsa, empty state bo'lmasa) -->
        <div v-if="aggregateAnalysis && dreamBuyers && dreamBuyers.length > 0" class="space-y-4">
            <!-- Stats row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Jami profillar</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ aggregateAnalysis.totalBuyers }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">To'liqlik</p>
                    <div class="flex items-end gap-2">
                        <p class="text-2xl font-bold" :class="aggregateAnalysis.completion >= 70 ? 'text-emerald-600' : aggregateAnalysis.completion >= 40 ? 'text-amber-600' : 'text-red-600'">{{ aggregateAnalysis.completion }}%</p>
                        <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mb-1.5">
                            <div :class="['h-full rounded-full', aggregateAnalysis.completion >= 70 ? 'bg-emerald-500' : aggregateAnalysis.completion >= 40 ? 'bg-amber-500' : 'bg-red-500']" :style="{ width: aggregateAnalysis.completion + '%' }"></div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Ishonchlilik</p>
                    <p class="text-lg font-bold" :class="'text-' + aggregateAnalysis.confidenceColor + '-600'">{{ aggregateAnalysis.confidence }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">{{ aggregateAnalysis.confidenceDesc }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Tavsiya</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ aggregateAnalysis.totalBuyers < 3 ? "Ko'proq profil qo'shing" : aggregateAnalysis.totalBuyers < 10 ? "So'rovnoma o'tkazing" : "Ma'lumotlar yetarli" }}</p>
                </div>
            </div>
            <!-- Insights grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div v-if="aggregateAnalysis.topFrustrations.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-3"><div class="w-7 h-7 bg-red-50 dark:bg-red-900/20 rounded-lg flex items-center justify-center"><svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div><h3 class="text-sm font-semibold text-gray-900 dark:text-white">Eng ko'p muammolar</h3></div>
                    <div class="space-y-2"><div v-for="item in aggregateAnalysis.topFrustrations" :key="item.name" class="flex items-center justify-between"><span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ item.name }}</span><div class="flex items-center gap-2"><div class="w-20 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden"><div class="h-full bg-red-500 rounded-full" :style="{ width: item.percent + '%' }"></div></div><span class="text-xs text-gray-400 w-12 text-right">{{ item.count }}/{{ aggregateAnalysis.totalBuyers }}</span></div></div></div>
                </div>
                <div v-if="aggregateAnalysis.topDreams.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-3"><div class="w-7 h-7 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg flex items-center justify-center"><svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg></div><h3 class="text-sm font-semibold text-gray-900 dark:text-white">Asosiy orzular</h3></div>
                    <div class="space-y-2"><div v-for="item in aggregateAnalysis.topDreams" :key="item.name" class="flex items-center justify-between"><span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ item.name }}</span><div class="flex items-center gap-2"><div class="w-20 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden"><div class="h-full bg-emerald-500 rounded-full" :style="{ width: item.percent + '%' }"></div></div><span class="text-xs text-gray-400 w-12 text-right">{{ item.count }}/{{ aggregateAnalysis.totalBuyers }}</span></div></div></div>
                </div>
                <div v-if="aggregateAnalysis.topPlatforms.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-3"><div class="w-7 h-7 bg-blue-50 dark:bg-blue-900/20 rounded-lg flex items-center justify-center"><svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div><h3 class="text-sm font-semibold text-gray-900 dark:text-white">Faol platformalar</h3></div>
                    <div class="flex flex-wrap gap-1.5"><span v-for="item in aggregateAnalysis.topPlatforms" :key="item.name" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 rounded-lg text-xs font-medium capitalize">{{ item.name }} <span class="text-[9px] bg-blue-200 dark:bg-blue-800 text-blue-800 dark:text-blue-300 px-1 rounded">{{ item.count }}/{{ aggregateAnalysis.totalBuyers }}</span></span></div>
                </div>
                <div v-if="aggregateAnalysis.topComms.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-3"><div class="w-7 h-7 bg-purple-50 dark:bg-purple-900/20 rounded-lg flex items-center justify-center"><svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg></div><h3 class="text-sm font-semibold text-gray-900 dark:text-white">Afzal aloqa usullari</h3></div>
                    <div class="flex flex-wrap gap-1.5"><span v-for="item in aggregateAnalysis.topComms" :key="item.name" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 rounded-lg text-xs font-medium capitalize">{{ item.name }} <span class="text-[9px] bg-purple-200 dark:bg-purple-800 text-purple-800 dark:text-purple-300 px-1 rounded">{{ item.count }}/{{ aggregateAnalysis.totalBuyers }}</span></span></div>
                </div>
            </div>
        </div>

        <!-- Profiles Table -->
        <div v-if="dreamBuyers && dreamBuyers.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Mijoz profillari</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ dreamBuyers.length }} ta profil</p>
                </div>
                <div class="relative">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <input v-model="searchQuery" type="text" placeholder="Qidirish..." class="pl-9 pr-4 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg w-48 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" />
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50/80 dark:bg-gray-900/30">
                            <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Profil</th>
                            <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden md:table-cell">Tavsif</th>
                            <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">To'liqlik</th>
                            <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden sm:table-cell">Manba</th>
                            <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Holat</th>
                            <th class="w-28"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                        <tr
                            v-for="buyer in filteredBuyers"
                            :key="buyer.id"
                            class="group hover:bg-indigo-50/30 dark:hover:bg-indigo-900/5 transition-colors"
                        >
                            <td class="px-5 py-4">
                                <Link :href="getRoute('dream-buyer.show', buyer.id)" class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-sm font-bold text-white flex-shrink-0">
                                        {{ buyer.name?.charAt(0) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate flex items-center gap-1.5">
                                            {{ buyer.name }}
                                            <svg v-if="buyer.is_primary" class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" /></svg>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ buyer.age_range || buyer.occupation || '—' }}</p>
                                    </div>
                                </Link>
                            </td>
                            <td class="px-5 py-4 hidden md:table-cell">
                                <p class="text-sm text-gray-600 dark:text-gray-400 truncate max-w-xs">{{ buyer.description || '—' }}</p>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <div class="w-16 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div :class="['h-full rounded-full', getCompletion(buyer) >= 70 ? 'bg-emerald-500' : getCompletion(buyer) >= 40 ? 'bg-amber-500' : 'bg-red-500']" :style="{ width: getCompletion(buyer) + '%' }"></div>
                                    </div>
                                    <span class="text-xs font-semibold" :class="getCompletion(buyer) >= 70 ? 'text-emerald-600' : getCompletion(buyer) >= 40 ? 'text-amber-600' : 'text-red-600'">{{ getCompletion(buyer) }}%</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center hidden sm:table-cell">
                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                    {{ buyer.source === 'survey' ? "So'rovnoma" : "Qo'lda" }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span v-if="buyer.is_primary" class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-md bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400">Asosiy</span>
                                <span v-else-if="buyer.is_active !== false" class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium rounded-md bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Faol
                                </span>
                                <span v-else class="text-xs text-gray-400">—</span>
                            </td>
                            <td class="px-3 py-4">
                                <div class="flex items-center justify-center gap-1">
                                    <Link :href="getRoute('dream-buyer.show', buyer.id)" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="Ko'rish">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    </Link>
                                    <Link v-if="!isReadOnly" :href="getRoute('dream-buyer.edit', buyer.id)" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors" title="Tahrirlash">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                                    </Link>
                                    <button v-if="!isReadOnly" @click="confirmDelete(buyer)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="O'chirish">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="filteredBuyers.length === 0 && searchQuery" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                Natija topilmadi
            </div>

            <div v-if="dreamBuyers.length > 0" class="px-5 py-3 border-t border-gray-100 dark:border-gray-700/50 bg-gray-50/50 dark:bg-gray-800/30">
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ filteredBuyers.length }} / {{ dreamBuyers.length }} ta profil</p>
            </div>
        </div>

        <!-- (umumiy tahlil yuqoriga ko'chirildi) -->
    </div>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
        <div v-if="deletingBuyer" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="cancelDelete"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ t('dream_buyer.delete_profile') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('dream_buyer.delete_warning') }}</p>
                        </div>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 mb-6">
                        {{ t('dream_buyer.delete_confirm', { name: deletingBuyer.name }) }}
                    </p>
                    <div class="flex gap-3">
                        <button @click="cancelDelete" class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors">
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
</template>
