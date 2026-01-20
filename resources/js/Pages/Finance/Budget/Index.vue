<script setup>
import { ref, computed } from 'vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    CurrencyDollarIcon,
    ChartBarIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    PlusIcon,
} from '@heroicons/vue/24/outline';
import { formatFullCurrency, formatPercent } from '@/utils/formatting';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    budgets: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({
            total_allocated: 0,
            total_spent: 0,
            total_remaining: 0,
            overall_percentage: 0,
            on_track_count: 0,
            warning_count: 0,
            over_budget_count: 0,
        }),
    },
});

const getStatusColor = (status) => {
    switch (status) {
        case 'on_track':
            return 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400';
        case 'warning':
            return 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'over_budget':
            return 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400';
        default:
            return 'text-gray-600 bg-gray-100 dark:bg-gray-800 dark:text-gray-400';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'on_track':
            return t('finance.budget_on_track');
        case 'warning':
            return t('finance.budget_warning');
        case 'over_budget':
            return t('finance.budget_over');
        default:
            return t('finance.budget_unknown');
    }
};

const getProgressBarColor = (percentage) => {
    if (percentage > 100) return 'bg-red-500';
    if (percentage > 80) return 'bg-yellow-500';
    return 'bg-green-500';
};
</script>

<template>
    <FinanceLayout :title="t('finance.budget')">
        <Head :title="t('finance.budget_management')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('finance.budget_management') }}</h1>
                    <p class="mt-1 text-gray-500 dark:text-gray-400">{{ t('finance.budget_desc') }}</p>
                </div>
                <a
                    href="/finance/budget/create"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                >
                    <PlusIcon class="w-5 h-5" />
                    {{ t('finance.new_budget') }}
                </a>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <CurrencyDollarIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('finance.total_allocated') }}</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatFullCurrency(summary.total_allocated) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                            <ChartBarIcon class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('finance.spent') }}</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatFullCurrency(summary.total_spent) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <CheckCircleIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('finance.remaining') }}</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatFullCurrency(summary.total_remaining) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <ChartBarIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('finance.overall_percent') }}</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatPercent(summary.overall_percentage) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Summary -->
            <div class="flex gap-4 flex-wrap">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-sm">
                    <CheckCircleIcon class="w-4 h-4" />
                    {{ summary.on_track_count }} {{ t('finance.budget_on_track') }}
                </div>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full text-sm">
                    <ExclamationTriangleIcon class="w-4 h-4" />
                    {{ summary.warning_count }} {{ t('finance.budget_warning') }}
                </div>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-sm">
                    <ExclamationTriangleIcon class="w-4 h-4" />
                    {{ summary.over_budget_count }} {{ t('finance.budget_over') }}
                </div>
            </div>

            <!-- Budget List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('finance.category') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('finance.allocated') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('finance.spent') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('finance.remaining') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('finance.progress') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('common.status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="budget in budgets" :key="budget.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ budget.category }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ formatFullCurrency(budget.allocated) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ formatFullCurrency(budget.spent) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm" :class="budget.remaining < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'">
                                    {{ formatFullCurrency(budget.remaining) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden w-24">
                                            <div
                                                class="h-full rounded-full transition-all"
                                                :class="getProgressBarColor(budget.percentage)"
                                                :style="{ width: Math.min(budget.percentage, 100) + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-sm text-gray-600 dark:text-gray-400 w-12 text-right">{{ budget.percentage }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full" :class="getStatusColor(budget.status)">
                                        {{ getStatusLabel(budget.status) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="budgets.length === 0" class="p-12 text-center">
                    <CurrencyDollarIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ t('finance.no_budgets') }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">{{ t('finance.no_budgets_desc') }}</p>
                    <a
                        href="/finance/budget/create"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                    >
                        <PlusIcon class="w-5 h-5" />
                        {{ t('finance.create_budget') }}
                    </a>
                </div>
            </div>
        </div>
    </FinanceLayout>
</template>
