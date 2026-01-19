<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { formatFullCurrency } from '@/utils/formatting';
import {
    ArrowLeftIcon,
    ArrowDownTrayIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    weekData: {
        type: Object,
        default: () => ({
            total_calls: 245,
            successful_deals: 28,
            total_revenue: 180000000,
            conversion_rate: 11.4,
        }),
    },
    dailyBreakdown: {
        type: Array,
        default: () => [
            { day: 'Dushanba', calls: 42, deals: 5, revenue: 28000000 },
            { day: 'Seshanba', calls: 38, deals: 4, revenue: 22000000 },
            { day: 'Chorshanba', calls: 45, deals: 6, revenue: 35000000 },
            { day: 'Payshanba', calls: 40, deals: 5, revenue: 30000000 },
            { day: 'Juma', calls: 48, deals: 5, revenue: 38000000 },
            { day: 'Shanba', calls: 32, deals: 3, revenue: 27000000 },
        ],
    },
});

const maxRevenue = Math.max(...props.dailyBreakdown.map(d => d.revenue));
</script>

<template>
    <SalesHeadLayout title="Haftalik Hisobot">
        <Head title="Haftalik Hisobot" />

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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Haftalik Hisobot</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Shu haftaning natijalari</p>
                    </div>
                </div>
                <button class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors">
                    <ArrowDownTrayIcon class="w-5 h-5" />
                    Yuklab olish
                </button>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Jami qo'ng'iroqlar</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ weekData.total_calls }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Yopilgan bitimlar</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ weekData.successful_deals }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Jami daromad</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ formatFullCurrency(weekData.total_revenue) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Konversiya</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ weekData.conversion_rate }}%</p>
                </div>
            </div>

            <!-- Daily Breakdown -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Kunlar bo'yicha</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div
                            v-for="day in dailyBreakdown"
                            :key="day.day"
                            class="flex items-center gap-4"
                        >
                            <span class="w-24 text-sm font-medium text-gray-700 dark:text-gray-300">{{ day.day }}</span>
                            <div class="flex-1 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
                                <div
                                    class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-lg flex items-center px-3"
                                    :style="{ width: `${(day.revenue / maxRevenue) * 100}%` }"
                                >
                                    <span class="text-xs text-white font-medium">{{ formatFullCurrency(day.revenue) }}</span>
                                </div>
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400 w-20 text-right">{{ day.deals }} bitim</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SalesHeadLayout>
</template>
