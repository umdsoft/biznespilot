<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';

const { t } = useI18n();

const props = defineProps({
    insights: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    actionItems: { type: Array, default: () => [] },
    lastGenerated: { type: String, default: null },
    panelType: { type: String, required: true },
});

const layoutComponent = computed(() => {
    const layouts = {
        business: BusinessLayout,
        marketing: MarketingLayout,
        saleshead: SalesHeadLayout,
    };
    return layouts[props.panelType] || BaseLayout;
});

const routePrefix = computed(() => {
    const prefix = props.panelType === 'saleshead' ? 'sales-head' : props.panelType;
    return '/' + prefix;
});

// Filter
const activeFilter = ref('all');
const filters = computed(() => [
    { key: 'all', label: t('insights.filter_all') },
    { key: 'high', label: t('insights.filter_important') },
    { key: 'positioning', label: t('insights.filter_position') },
    { key: 'opportunity', label: t('insights.filter_opportunities') },
    { key: 'threat', label: t('insights.filter_warnings') },
    { key: 'price', label: t('insights.filter_price') },
    { key: 'marketing', label: t('insights.filter_marketing') },
    { key: 'content', label: t('insights.filter_content') },
]);

const filteredInsights = computed(() => {
    if (activeFilter.value === 'all') return props.insights;
    if (activeFilter.value === 'high') return props.insights.filter(i => i.priority === 'high');
    return props.insights.filter(i => i.type === activeFilter.value);
});

// Generatsiya qilish
const generating = ref(false);
const generateInsights = () => {
    generating.value = true;
    router.post(`${routePrefix.value}/competitor-insights/generate`, {}, {
        preserveScroll: true,
        onFinish: () => {
            generating.value = false;
        },
    });
};

// Tavsiyani bajarish
const completeInsight = async (insight) => {
    await router.post(`${routePrefix.value}/competitor-insights/${insight.id}/complete`, {}, {
        preserveScroll: true,
    });
};

// Tavsiyani rad etish
const dismissInsight = async (insight) => {
    await router.post(`${routePrefix.value}/competitor-insights/${insight.id}/dismiss`, {}, {
        preserveScroll: true,
    });
};

// Icon komponenti
const getIcon = (type) => {
    const icons = {
        price: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />`,
        marketing: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />`,
        opportunity: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />`,
        threat: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />`,
        sales_script: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />`,
        product: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />`,
        positioning: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />`,
        content: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />`,
    };
    return icons[type] || icons.opportunity;
};

// Rang olish
const getColorClasses = (priority) => {
    const colors = {
        high: {
            bg: 'bg-red-50 dark:bg-red-900/20',
            border: 'border-red-200 dark:border-red-800',
            icon: 'text-red-600 dark:text-red-400',
            badge: 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400',
        },
        medium: {
            bg: 'bg-yellow-50 dark:bg-yellow-900/20',
            border: 'border-yellow-200 dark:border-yellow-800',
            icon: 'text-yellow-600 dark:text-yellow-400',
            badge: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-400',
        },
        low: {
            bg: 'bg-green-50 dark:bg-green-900/20',
            border: 'border-green-200 dark:border-green-800',
            icon: 'text-green-600 dark:text-green-400',
            badge: 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400',
        },
    };
    return colors[priority] || colors.medium;
};

// Vaqtni formatlash
const formatDate = (dateStr) => {
    if (!dateStr) return t('insights.not_generated');
    const date = new Date(dateStr);
    return date.toLocaleDateString('uz-UZ', {
        day: 'numeric',
        month: 'long',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Priority va Type nomlarini tarjima qilish
const getPriorityName = (priority) => {
    return t(`priority.${priority}`) || priority;
};

const getTypeName = (type) => {
    return t(`type.${type}`) || type;
};
</script>

<template>
    <component :is="layoutComponent" :title="t('insights.title')">
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ t('insights.title') }}</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ t('insights.subtitle') }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ t('insights.last_update') }}: {{ formatDate(lastGenerated) }}
                    </span>
                    <button
                        @click="generateInsights"
                        :disabled="generating"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white text-sm font-medium rounded-xl transition-colors"
                    >
                        <svg v-if="generating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ generating ? t('insights.analyzing') : t('insights.refresh') }}
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('insights.total') }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.high_priority }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('insights.important') }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.opportunities }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('insights.opportunities') }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.threats }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('insights.warnings') }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.unread }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('insights.unread') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bu Hafta Qilish Kerak -->
            <div v-if="actionItems.length > 0" class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-6 text-white">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold">{{ t('insights.this_week') }}</h2>
                </div>
                <div class="space-y-3">
                    <div
                        v-for="(item, index) in actionItems"
                        :key="item.id"
                        class="flex items-start gap-3 bg-white/10 rounded-xl p-4"
                    >
                        <span class="flex-shrink-0 w-6 h-6 bg-white/20 rounded-full flex items-center justify-center text-sm font-bold">
                            {{ index + 1 }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium">{{ item.title }}</p>
                            <p class="text-sm text-white/80 mt-1">{{ item.action_text || item.recommendation }}</p>
                            <p v-if="item.competitor_name" class="text-xs text-white/60 mt-1">
                                {{ t('insights.competitor') }}: {{ item.competitor_name }}
                            </p>
                        </div>
                        <button
                            @click="completeInsight(item)"
                            class="flex-shrink-0 px-3 py-1.5 bg-white/20 hover:bg-white/30 rounded-lg transition-colors text-sm font-medium flex items-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ t('insights.completed') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="filter in filters"
                    :key="filter.key"
                    @click="activeFilter = filter.key"
                    :class="[
                        'px-4 py-2 rounded-xl text-sm font-medium transition-colors',
                        activeFilter === filter.key
                            ? 'bg-indigo-600 text-white'
                            : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                    ]"
                >
                    {{ filter.label }}
                </button>
            </div>

            <!-- Insights List -->
            <div v-if="filteredInsights.length > 0" class="space-y-4">
                <div
                    v-for="insight in filteredInsights"
                    :key="insight.id"
                    :class="[
                        'rounded-xl border p-5 transition-all hover:shadow-md',
                        getColorClasses(insight.priority).bg,
                        getColorClasses(insight.priority).border,
                    ]"
                >
                    <div class="flex items-start gap-4">
                        <!-- Icon -->
                        <div :class="['p-3 rounded-xl bg-white dark:bg-gray-800', getColorClasses(insight.priority).icon]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="getIcon(insight.type)"></svg>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ insight.title }}</h3>
                                <span :class="['px-2 py-0.5 text-xs font-medium rounded-full', getColorClasses(insight.priority).badge]">
                                    {{ getPriorityName(insight.priority) }}
                                </span>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                    {{ getTypeName(insight.type) }}
                                </span>
                            </div>

                            <p v-if="insight.competitor_name" class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                {{ t('insights.competitor') }}: {{ insight.competitor_name }}
                            </p>

                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">{{ insight.description }}</p>

                            <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ t('insights.recommendation') }}:</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ insight.action_text || insight.recommendation }}</p>

                                <!-- Qo'shimcha ma'lumotlar -->
                                <div v-if="insight.action_data?.content_ideas" class="mt-3">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ t('insights.content_ideas') }}:</p>
                                    <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                        <li v-for="idea in insight.action_data.content_ideas" :key="idea" class="flex items-center gap-1">
                                            <span class="w-1 h-1 bg-indigo-500 rounded-full"></span>
                                            {{ idea }}
                                        </li>
                                    </ul>
                                </div>

                                <div v-if="insight.action_data?.steps" class="mt-3">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ t('insights.steps') }}:</p>
                                    <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                        <li v-for="step in insight.action_data.steps" :key="step">{{ step }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col gap-2">
                            <button
                                @click="completeInsight(insight)"
                                class="group relative p-2 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-900/50 rounded-lg transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="absolute right-full mr-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                    {{ t('insights.completed') }}
                                </span>
                            </button>
                            <button
                                @click="dismissInsight(insight)"
                                class="group relative p-2 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span class="absolute right-full mr-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                    {{ t('insights.dismiss') }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                <div class="mx-auto w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ t('insights.no_insights') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    {{ t('insights.no_insights_desc') }}
                </p>
                <button
                    @click="generateInsights"
                    :disabled="generating"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ t('insights.analyze') }}
                </button>
            </div>
        </div>
    </component>
</template>
