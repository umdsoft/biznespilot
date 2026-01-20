<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { useI18n } from '@/i18n';
import {
    ChartBarIcon,
    ChartPieIcon,
    ArrowTrendingUpIcon,
    CurrencyDollarIcon,
    UsersIcon,
    FunnelIcon,
    ArrowDownTrayIcon,
    DocumentTextIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    analytics: {
        type: Object,
        default: () => ({
            conversion_rate: 0,
            avg_deal_size: 0,
            total_revenue: 0,
            leads_count: 0,
            won_count: 0,
            lost_count: 0,
        }),
    },
    period: {
        type: String,
        default: 'month',
    },
});

const selectedPeriod = ref(props.period);
const showExportMenu = ref(false);

const formatCurrency = (amount) => {
    if (!amount) return '0 so\'m';
    return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
};

const formatPercent = (value) => {
    return (value || 0).toFixed(1) + '%';
};

const handlePeriodChange = (period) => {
    selectedPeriod.value = period;
    router.get('/sales-head/analytics', { period }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const exportReport = (format) => {
    showExportMenu.value = false;
    window.location.href = `/sales-head/analytics/export?period=${selectedPeriod.value}&format=${format}`;
};
</script>

<template>
    <SalesHeadLayout :title="t('nav.analytics')">
        <Head :title="t('nav.analytics')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('nav.analytics') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('analytics.sales_analysis') }}</p>
                </div>
                <div class="flex gap-2">
                    <Link
                        href="/sales-head/analytics/conversion"
                        class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        {{ t('analytics.conversion') }}
                    </Link>
                    <Link
                        href="/sales-head/analytics/revenue"
                        class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        {{ t('analytics.revenue') }}
                    </Link>
                    <!-- Export Dropdown -->
                    <div class="relative">
                        <button
                            @click="showExportMenu = !showExportMenu"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors"
                        >
                            <ArrowDownTrayIcon class="w-5 h-5" />
                            {{ t('common.download') }}
                        </button>
                        <div
                            v-if="showExportMenu"
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50"
                        >
                            <button
                                @click="exportReport('pdf')"
                                class="w-full px-4 py-2 text-left text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                            >
                                <DocumentTextIcon class="w-5 h-5 text-red-500" />
                                {{ t('common.pdf_format') }}
                            </button>
                            <button
                                @click="exportReport('excel')"
                                class="w-full px-4 py-2 text-left text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                            >
                                <DocumentTextIcon class="w-5 h-5 text-green-500" />
                                {{ t('common.excel_format') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Period Selector -->
            <div class="flex gap-2">
                <button
                    v-for="p in ['week', 'month', 'quarter', 'year']"
                    :key="p"
                    @click="handlePeriodChange(p)"
                    :class="[
                        'px-4 py-2 rounded-lg font-medium transition-colors',
                        selectedPeriod === p
                            ? 'bg-emerald-600 text-white'
                            : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                    ]"
                >
                    {{ p === 'week' ? t('common.week') : p === 'month' ? t('common.month') : p === 'quarter' ? t('common.quarter') : t('common.year') }}
                </button>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <UsersIcon class="w-5 h-5 text-blue-500" />
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ analytics.leads_count }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('analytics.total_leads') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <ChartPieIcon class="w-5 h-5 text-emerald-500" />
                    </div>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatPercent(analytics.conversion_rate) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('analytics.conversion') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <ArrowTrendingUpIcon class="w-5 h-5 text-green-500" />
                    </div>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ analytics.won_count }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('saleshead.deal_won') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <FunnelIcon class="w-5 h-5 text-red-500" />
                    </div>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ analytics.lost_count }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('saleshead.deal_lost') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <CurrencyDollarIcon class="w-5 h-5 text-purple-500" />
                    </div>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ formatCurrency(analytics.avg_deal_size) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('analytics.avg_deal') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <CurrencyDollarIcon class="w-5 h-5 text-yellow-500" />
                    </div>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ formatCurrency(analytics.total_revenue) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('analytics.total_revenue') }}</p>
                </div>
            </div>

            <!-- Charts Placeholder -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ t('analytics.sales_dynamics') }}</h3>
                    <div class="h-64 flex items-center justify-center text-gray-400">
                        <div class="text-center">
                            <ChartBarIcon class="w-12 h-12 mx-auto mb-2" />
                            <p>{{ t('analytics.chart_coming_soon') }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ t('analytics.conversion_funnel') }}</h3>
                    <div class="h-64 flex items-center justify-center text-gray-400">
                        <div class="text-center">
                            <FunnelIcon class="w-12 h-12 mx-auto mb-2" />
                            <p>{{ t('analytics.chart_coming_soon') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SalesHeadLayout>
</template>
