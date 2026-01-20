<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { useI18n } from '@/i18n';
import { formatFullCurrency, formatDate } from '@/utils/formatting';
import {
    ArrowLeftIcon,
    ArrowDownTrayIcon,
    PhoneIcon,
    UserGroupIcon,
    CurrencyDollarIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_calls: 45,
            successful_calls: 32,
            new_leads: 12,
            closed_deals: 5,
            revenue: 25000000,
        }),
    },
    activities: {
        type: Array,
        default: () => [],
    },
});

const statCards = computed(() => [
    { label: t('calls.total_calls'), value: props.stats.total_calls, icon: PhoneIcon, color: 'blue' },
    { label: t('calls.successful'), value: props.stats.successful_calls, icon: CheckCircleIcon, color: 'green' },
    { label: t('reports.new_leads'), value: props.stats.new_leads, icon: UserGroupIcon, color: 'purple' },
    { label: t('reports.closed_deals'), value: props.stats.closed_deals, icon: CurrencyDollarIcon, color: 'emerald' },
]);
</script>

<template>
    <SalesHeadLayout :title="t('reports.daily_report')">
        <Head :title="t('reports.daily_report')" />

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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('reports.daily_report') }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(new Date()) }}</p>
                    </div>
                </div>
                <button class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors">
                    <ArrowDownTrayIcon class="w-5 h-5" />
                    {{ t('common.download') }}
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="stat in statCards"
                    :key="stat.label"
                    class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-10 h-10 rounded-lg flex items-center justify-center"
                            :class="{
                                'bg-blue-100 dark:bg-blue-900/30': stat.color === 'blue',
                                'bg-green-100 dark:bg-green-900/30': stat.color === 'green',
                                'bg-purple-100 dark:bg-purple-900/30': stat.color === 'purple',
                                'bg-emerald-100 dark:bg-emerald-900/30': stat.color === 'emerald',
                            }"
                        >
                            <component
                                :is="stat.icon"
                                class="w-5 h-5"
                                :class="{
                                    'text-blue-600 dark:text-blue-400': stat.color === 'blue',
                                    'text-green-600 dark:text-green-400': stat.color === 'green',
                                    'text-purple-600 dark:text-purple-400': stat.color === 'purple',
                                    'text-emerald-600 dark:text-emerald-400': stat.color === 'emerald',
                                }"
                            />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ stat.label }}</p>
                </div>
            </div>

            <!-- Revenue -->
            <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl p-6 text-white">
                <p class="text-emerald-100 text-sm">{{ t('reports.today_revenue') }}</p>
                <p class="text-3xl font-bold mt-1">{{ formatFullCurrency(stats.revenue) }}</p>
            </div>

            <!-- Empty state for activities -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                <PhoneIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ t('reports.activities') }}</h3>
                <p class="text-gray-500 dark:text-gray-400">{{ t('reports.today_activities_list') }}</p>
            </div>
        </div>
    </SalesHeadLayout>
</template>
