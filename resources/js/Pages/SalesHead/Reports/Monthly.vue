<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { formatFullCurrency, formatPercent, getMonthName } from '@/utils/formatting';
import {
    ArrowLeftIcon,
    ArrowDownTrayIcon,
    ArrowUpIcon,
    ArrowDownIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    monthData: {
        type: Object,
        default: () => ({
            total_calls: 980,
            successful_deals: 112,
            total_revenue: 750000000,
            target_revenue: 800000000,
            conversion_rate: 11.4,
            avg_deal_size: 6700000,
        }),
    },
    comparison: {
        type: Object,
        default: () => ({
            calls_change: 12.5,
            deals_change: 8.2,
            revenue_change: 15.3,
        }),
    },
    weeklyTrend: {
        type: Array,
        default: () => [
            { week: '1-hafta', revenue: 165000000 },
            { week: '2-hafta', revenue: 195000000 },
            { week: '3-hafta', revenue: 180000000 },
            { week: '4-hafta', revenue: 210000000 },
        ],
    },
});

const currentMonth = getMonthName(new Date().getMonth());
const targetProgress = (props.monthData.total_revenue / props.monthData.target_revenue) * 100;
</script>

<template>
    <SalesHeadLayout title="Oylik Hisobot">
        <Head title="Oylik Hisobot" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        href="/sales-head/reports"
                        class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                    >
                        <ArrowLeftIcon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Oylik Hisobot</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ currentMonth }} oyi natijalari</p>
                    </div>
                </div>
                <button class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors">
                    <ArrowDownTrayIcon class="w-5 h-5" />
                    Yuklab olish
                </button>
            </div>

            <!-- Target Progress -->
            <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-emerald-100">Oylik maqsad</p>
                        <p class="text-3xl font-bold mt-1">{{ formatFullCurrency(monthData.total_revenue) }}</p>
                        <p class="text-emerald-100 text-sm mt-1">{{ formatFullCurrency(monthData.target_revenue) }} dan</p>
                    </div>
                    <div class="text-right">
                        <p class="text-4xl font-bold">{{ formatPercent(targetProgress) }}</p>
                        <p class="text-emerald-100 text-sm">bajarildi</p>
                    </div>
                </div>
                <div class="h-3 bg-white/20 rounded-full overflow-hidden">
                    <div
                        class="h-full bg-white rounded-full transition-all duration-500"
                        :style="{ width: `${Math.min(targetProgress, 100)}%` }"
                    ></div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Jami qo'ng'iroqlar</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ monthData.total_calls }}</p>
                    <div class="flex items-center gap-1 mt-2" :class="comparison.calls_change >= 0 ? 'text-green-600' : 'text-red-600'">
                        <component :is="comparison.calls_change >= 0 ? ArrowUpIcon : ArrowDownIcon" class="w-4 h-4" />
                        <span class="text-sm">{{ Math.abs(comparison.calls_change) }}%</span>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Yopilgan bitimlar</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ monthData.successful_deals }}</p>
                    <div class="flex items-center gap-1 mt-2" :class="comparison.deals_change >= 0 ? 'text-green-600' : 'text-red-600'">
                        <component :is="comparison.deals_change >= 0 ? ArrowUpIcon : ArrowDownIcon" class="w-4 h-4" />
                        <span class="text-sm">{{ Math.abs(comparison.deals_change) }}%</span>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Konversiya</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ monthData.conversion_rate }}%</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha bitim</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ formatFullCurrency(monthData.avg_deal_size) }}</p>
                </div>
            </div>

            <!-- Weekly Trend -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Haftalik trend</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-4 gap-4">
                        <div
                            v-for="week in weeklyTrend"
                            :key="week.week"
                            class="text-center"
                        >
                            <div class="h-32 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-end justify-center p-2">
                                <div
                                    class="w-full bg-gradient-to-t from-emerald-500 to-teal-400 rounded"
                                    :style="{ height: `${(week.revenue / Math.max(...weeklyTrend.map(w => w.revenue))) * 100}%` }"
                                ></div>
                            </div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-2">{{ week.week }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatFullCurrency(week.revenue) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SalesHeadLayout>
</template>
