<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { useI18n } from '@/i18n';
import { formatFullCurrency, formatPercent } from '@/utils/formatting';
import { ArrowLeftIcon, ArrowUpIcon, ArrowDownIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    revenueData: {
        type: Object,
        default: () => ({
            total: 850000000,
            target: 1000000000,
            average_deal: 7500000,
            trend: 15.3,
        }),
    },
    monthlyRevenue: {
        type: Array,
        default: () => [
            { month: 'Okt', revenue: 220000000, deals: 28 },
            { month: 'Noy', revenue: 280000000, deals: 35 },
            { month: 'Dek', revenue: 350000000, deals: 42 },
        ],
    },
    topSources: {
        type: Array,
        default: () => [
            { name: 'Veb-sayt', revenue: 320000000, percentage: 38 },
            { name: 'Referral', revenue: 250000000, percentage: 29 },
            { name: 'Telegram', revenue: 180000000, percentage: 21 },
            { name: 'Boshqa', revenue: 100000000, percentage: 12 },
        ],
    },
});

const targetProgress = (props.revenueData.total / props.revenueData.target) * 100;
const maxMonthlyRevenue = Math.max(...props.monthlyRevenue.map(m => m.revenue));
</script>

<template>
    <SalesHeadLayout :title="t('analytics.revenue_analysis')">
        <Head :title="t('analytics.revenue_analysis')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    href="/sales-head/analytics"
                    class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                >
                    <ArrowLeftIcon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('analytics.revenue_analysis') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('analytics.detailed_revenue_stats') }}</p>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-emerald-100">{{ t('analytics.total_revenue') }}</p>
                        <p class="text-3xl font-bold mt-1">{{ formatFullCurrency(revenueData.total) }}</p>
                        <p class="text-emerald-100 text-sm mt-1">{{ t('saleshead.target') }}: {{ formatFullCurrency(revenueData.target) }}</p>
                    </div>
                    <div class="text-right">
                        <div class="flex items-center gap-2 justify-end" :class="revenueData.trend >= 0 ? 'text-green-300' : 'text-red-300'">
                            <component :is="revenueData.trend >= 0 ? ArrowUpIcon : ArrowDownIcon" class="w-5 h-5" />
                            <span class="text-lg font-medium">{{ Math.abs(revenueData.trend) }}%</span>
                        </div>
                        <p class="text-emerald-100 text-sm">{{ t('analytics.compared_to_last_month') }}</p>
                    </div>
                </div>
                <div class="h-3 bg-white/20 rounded-full overflow-hidden">
                    <div
                        class="h-full bg-white rounded-full"
                        :style="{ width: `${Math.min(targetProgress, 100)}%` }"
                    ></div>
                </div>
                <p class="text-emerald-100 text-sm mt-2">{{ formatPercent(targetProgress) }} {{ t('analytics.completed') }}</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('analytics.avg_deal') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ formatFullCurrency(revenueData.average_deal) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('analytics.total_deals') }}</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ monthlyRevenue.reduce((sum, m) => sum + m.deals, 0) }}</p>
                </div>
            </div>

            <!-- Monthly Revenue -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-6">{{ t('analytics.monthly_revenue') }}</h3>
                <div class="space-y-4">
                    <div
                        v-for="month in monthlyRevenue"
                        :key="month.month"
                        class="flex items-center gap-4"
                    >
                        <span class="w-16 text-sm font-medium text-gray-700 dark:text-gray-300">{{ month.month }}</span>
                        <div class="flex-1 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
                            <div
                                class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-lg flex items-center justify-end px-3"
                                :style="{ width: `${(month.revenue / maxMonthlyRevenue) * 100}%` }"
                            >
                                <span class="text-white text-sm font-medium">{{ formatFullCurrency(month.revenue) }}</span>
                            </div>
                        </div>
                        <span class="w-20 text-sm text-gray-500 dark:text-gray-400 text-right">{{ month.deals }} {{ t('saleshead.deal') }}</span>
                    </div>
                </div>
            </div>

            <!-- Revenue Sources -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-6">{{ t('analytics.revenue_sources') }}</h3>
                <div class="space-y-4">
                    <div
                        v-for="source in topSources"
                        :key="source.name"
                        class="flex items-center justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                            <span class="text-gray-700 dark:text-gray-300">{{ source.name }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="font-medium text-gray-900 dark:text-white">{{ formatFullCurrency(source.revenue) }}</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400 w-12 text-right">{{ source.percentage }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SalesHeadLayout>
</template>
