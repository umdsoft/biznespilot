<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { useI18n } from '@/i18n';
import { formatPercent } from '@/utils/formatting';
import { ArrowLeftIcon, ArrowUpIcon, ArrowDownIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    conversionData: {
        type: Object,
        default: () => ({
            overall: 12.5,
            by_stage: [
                { stage: 'Yangi', count: 100, conversion: 100 },
                { stage: "Bog'lanildi", count: 75, conversion: 75 },
                { stage: 'Taklif yuborildi', count: 45, conversion: 60 },
                { stage: 'Muzokara', count: 28, conversion: 62 },
                { stage: 'Yopildi', count: 12, conversion: 43 },
            ],
            trend: 2.3,
        }),
    },
    monthlyConversion: {
        type: Array,
        default: () => [
            { month: 'Okt', rate: 10.2 },
            { month: 'Noy', rate: 11.8 },
            { month: 'Dek', rate: 12.5 },
        ],
    },
});

const maxCount = Math.max(...props.conversionData.by_stage.map(s => s.count));
</script>

<template>
    <SalesHeadLayout :title="t('analytics.conversion_analysis')">
        <Head :title="t('analytics.conversion_analysis')" />

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
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('analytics.conversion_analysis') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('analytics.stage_conversion') }}</p>
                </div>
            </div>

            <!-- Overall Conversion -->
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100">{{ t('analytics.overall_conversion') }}</p>
                        <p class="text-4xl font-bold mt-1">{{ conversionData.overall }}%</p>
                    </div>
                    <div class="flex items-center gap-2" :class="conversionData.trend >= 0 ? 'text-green-300' : 'text-red-300'">
                        <component :is="conversionData.trend >= 0 ? ArrowUpIcon : ArrowDownIcon" class="w-5 h-5" />
                        <span class="text-lg font-medium">{{ Math.abs(conversionData.trend) }}%</span>
                    </div>
                </div>
            </div>

            <!-- Funnel -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-6">{{ t('analytics.sales_funnel') }}</h3>
                <div class="space-y-4">
                    <div
                        v-for="(stage, index) in conversionData.by_stage"
                        :key="stage.stage"
                        class="flex items-center gap-4"
                    >
                        <span class="w-32 text-sm font-medium text-gray-700 dark:text-gray-300">{{ stage.stage }}</span>
                        <div class="flex-1 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
                            <div
                                class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 rounded-lg flex items-center justify-end px-3 transition-all"
                                :style="{ width: `${(stage.count / maxCount) * 100}%` }"
                            >
                                <span class="text-white text-sm font-medium">{{ stage.count }}</span>
                            </div>
                        </div>
                        <span class="w-16 text-sm text-gray-500 dark:text-gray-400 text-right">{{ stage.conversion }}%</span>
                    </div>
                </div>
            </div>

            <!-- Monthly Trend -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-6">{{ t('analytics.monthly_trend') }}</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div
                        v-for="month in monthlyConversion"
                        :key="month.month"
                        class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                    >
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ month.rate }}%</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ month.month }}</p>
                    </div>
                </div>
            </div>
        </div>
    </SalesHeadLayout>
</template>
