<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';

const { t } = useI18n();

const props = defineProps({
    competitor: { type: Object, required: true },
    metrics: { type: Array, default: () => [] },
    latest_metric: { type: Object, default: null },
    swot_analysis: { type: Object, default: null },
    panelType: {
        type: String,
        required: true,
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

// SalesHead faqat ko'rish uchun
const isReadOnly = computed(() => props.panelType === 'saleshead');

// Route prefix (saleshead -> sales-head URL conversion)
const routePrefix = computed(() => {
    const prefix = props.panelType === 'saleshead' ? 'sales-head' : props.panelType;
    return '/' + prefix;
});

// SWOT ma'lumotlari - effective_swot_data yoki swot_analysis dan
const swotData = computed(() => {
    if (props.swot_analysis) {
        return props.swot_analysis;
    }
    if (props.competitor.swot_data) {
        return props.competitor.swot_data;
    }
    if (props.competitor.effective_swot_data) {
        return props.competitor.effective_swot_data;
    }
    return {
        strengths: props.competitor.strengths || [],
        weaknesses: props.competitor.weaknesses || [],
        opportunities: [],
        threats: [],
    };
});

const hasSwotData = computed(() => {
    const data = swotData.value;
    return (
        (data.strengths && data.strengths.length > 0) ||
        (data.weaknesses && data.weaknesses.length > 0) ||
        (data.opportunities && data.opportunities.length > 0) ||
        (data.threats && data.threats.length > 0)
    );
});

// Tahdid darajasi badge
const getThreatBadge = (level) => {
    const badges = {
        low: { text: t('competitors.threat_low'), class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
        medium: { text: t('competitors.threat_medium'), class: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' },
        high: { text: t('competitors.threat_high'), class: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' },
        critical: { text: t('competitors.threat_critical'), class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
    };
    return badges[level] || badges.medium;
};

// Status badge
const getStatusBadge = (status) => {
    const badges = {
        active: { text: t('common.active'), class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
        inactive: { text: t('common.inactive'), class: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' },
        archived: { text: t('common.archived'), class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
    };
    return badges[status] || badges.inactive;
};

// Raqamlarni formatlash
const formatNumber = (num) => {
    if (num === null || num === undefined) return '—';
    if (num >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
    return num.toString();
};

// SWOT generatsiya
const generating = ref(false);
const generateSwot = async () => {
    if (isReadOnly.value) return;
    generating.value = true;
    try {
        await router.post(`${routePrefix.value}/competitors/${props.competitor.id}/generate-swot`, {}, {
            preserveScroll: true,
            onFinish: () => {
                generating.value = false;
            },
        });
    } catch (e) {
        generating.value = false;
    }
};
</script>

<template>
    <component :is="layoutComponent" :title="competitor.name + ' - ' + t('swot.title')">
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-4">
                    <Link
                        :href="`${routePrefix}/competitors`"
                        class="p-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                                {{ competitor.name.substring(0, 2).toUpperCase() }}
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ competitor.name }}</h1>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ competitor.industry || competitor.location || 'Raqobatchi' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span :class="getThreatBadge(competitor.threat_level).class" class="px-3 py-1.5 rounded-full text-sm font-medium">
                        {{ getThreatBadge(competitor.threat_level).text }} tahdid
                    </span>
                    <span :class="getStatusBadge(competitor.status).class" class="px-2.5 py-1 rounded-lg text-xs font-medium">
                        {{ getStatusBadge(competitor.status).text }}
                    </span>
                </div>
            </div>

            <!-- SWOT Analysis - Asosiy qism -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">SWOT Tahlili</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ competitor.name }} ning strategik tahlili
                                </p>
                            </div>
                        </div>
                        <button
                            v-if="!isReadOnly && !hasSwotData"
                            @click="generateSwot"
                            :disabled="generating"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white text-sm font-medium rounded-xl transition-colors"
                        >
                            <svg v-if="generating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            {{ generating ? t('common.loading') : t('swot.generate') }}
                        </button>
                    </div>
                </div>

                <div v-if="hasSwotData" class="grid grid-cols-1 md:grid-cols-2 gap-0">
                    <!-- Kuchli tomonlar (Strengths) -->
                    <div class="p-6 border-b md:border-r border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-green-700 dark:text-green-400">{{ t('swot.strengths') }}</h3>
                        </div>
                        <ul v-if="swotData.strengths?.length" class="space-y-2">
                            <li v-for="(item, index) in swotData.strengths" :key="index" class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full mt-2 flex-shrink-0"></span>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ item }}</span>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-400 dark:text-gray-500 italic">{{ t('common.no_data') }}</p>
                    </div>

                    <!-- Zaif tomonlar (Weaknesses) -->
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-red-700 dark:text-red-400">{{ t('swot.weaknesses') }}</h3>
                        </div>
                        <ul v-if="swotData.weaknesses?.length" class="space-y-2">
                            <li v-for="(item, index) in swotData.weaknesses" :key="index" class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full mt-2 flex-shrink-0"></span>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ item }}</span>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-400 dark:text-gray-500 italic">{{ t('common.no_data') }}</p>
                    </div>

                    <!-- Imkoniyatlar (Opportunities) -->
                    <div class="p-6 md:border-r border-b md:border-b-0 border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-blue-700 dark:text-blue-400">{{ t('swot.opportunities') }}</h3>
                        </div>
                        <ul v-if="swotData.opportunities?.length" class="space-y-2">
                            <li v-for="(item, index) in swotData.opportunities" :key="index" class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 bg-blue-500 rounded-full mt-2 flex-shrink-0"></span>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ item }}</span>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-400 dark:text-gray-500 italic">{{ t('common.no_data') }}</p>
                    </div>

                    <!-- Tahdidlar (Threats) -->
                    <div class="p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-orange-700 dark:text-orange-400">{{ t('swot.threats') }}</h3>
                        </div>
                        <ul v-if="swotData.threats?.length" class="space-y-2">
                            <li v-for="(item, index) in swotData.threats" :key="index" class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 bg-orange-500 rounded-full mt-2 flex-shrink-0"></span>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ item }}</span>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-400 dark:text-gray-500 italic">{{ t('common.no_data') }}</p>
                    </div>
                </div>

                <!-- SWOT ma'lumoti yo'q -->
                <div v-else class="p-12 text-center">
                    <div class="mx-auto w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ t('swot.no_analysis') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        {{ isReadOnly ? t('swot.no_analysis_readonly') : t('swot.no_analysis_desc') }}
                    </p>
                </div>
            </div>

            <!-- Qo'shimcha Ma'lumotlar -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Asosiy Ma'lumotlar -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ t('competitors.basic_info') }}</h3>
                    <div class="space-y-4">
                        <div v-if="competitor.description" class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Tavsif</p>
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ competitor.description }}</p>
                            </div>
                        </div>
                        <div v-if="competitor.website" class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Veb-sayt</p>
                                <a :href="competitor.website" target="_blank" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ competitor.website }}
                                </a>
                            </div>
                        </div>
                        <div v-if="competitor.industry" class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Soha</p>
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ competitor.industry }}</p>
                            </div>
                        </div>
                        <div v-if="competitor.location || competitor.region" class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Joylashuv</p>
                                <p class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ [competitor.location, competitor.region, competitor.district].filter(Boolean).join(', ') || 'Noma\'lum' }}
                                </p>
                            </div>
                        </div>
                        <div v-if="competitor.notes" class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Izohlar</p>
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ competitor.notes }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ijtimoiy tarmoqlar va Metrikalar -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ t('competitors.social_networks') }}</h3>
                    <div class="space-y-4">
                        <div v-if="competitor.instagram_handle" class="flex items-center justify-between p-3 bg-gradient-to-r from-pink-50 to-purple-50 dark:from-pink-900/20 dark:to-purple-900/20 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-purple-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ competitor.instagram_handle }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Instagram</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    {{ formatNumber(latest_metric?.instagram_followers) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">obunachilar</p>
                            </div>
                        </div>

                        <div v-if="competitor.telegram_handle" class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ competitor.telegram_handle }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Telegram</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    {{ formatNumber(latest_metric?.telegram_members) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">a'zolar</p>
                            </div>
                        </div>

                        <div v-if="competitor.facebook_page" class="flex items-center justify-between p-3 bg-blue-100 dark:bg-blue-900/20 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ competitor.facebook_page }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Facebook</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="!competitor.instagram_handle && !competitor.telegram_handle && !competitor.facebook_page" class="text-center py-6">
                            <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                            </svg>
                            <p class="text-sm text-gray-400 dark:text-gray-500">{{ t('competitors.no_social_networks') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
