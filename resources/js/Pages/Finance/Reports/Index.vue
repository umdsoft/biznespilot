<script setup>
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ChartBarIcon,
    ScaleIcon,
    CurrencyDollarIcon,
    UsersIcon,
    BuildingOfficeIcon,
    ReceiptPercentIcon,
    ArrowRightIcon,
    DocumentArrowDownIcon,
} from '@heroicons/vue/24/outline';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    reports: {
        type: Array,
        default: () => [],
    },
});

const getIcon = (iconName) => {
    const icons = {
        'chart-bar': ChartBarIcon,
        'scale': ScaleIcon,
        'currency-dollar': CurrencyDollarIcon,
        'users': UsersIcon,
        'building-office': BuildingOfficeIcon,
        'receipt-percent': ReceiptPercentIcon,
    };
    return icons[iconName] || ChartBarIcon;
};

const getReportUrl = (reportId) => {
    const routes = {
        'profit-loss': '/finance/reports/profit-loss',
        'balance-sheet': '/finance/reports/balance-sheet',
        'cash-flow': '/finance/reports/cash-flow',
        'accounts-receivable': '/finance/reports/accounts-receivable',
        'accounts-payable': '/finance/reports/accounts-payable',
        'expense-summary': '/finance/reports/expense-summary',
    };
    return routes[reportId] || '/finance/reports';
};
</script>

<template>
    <FinanceLayout :title="t('finance.reports')">
        <Head :title="t('finance.financial_reports')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('finance.financial_reports') }}</h1>
                    <p class="mt-1 text-gray-500 dark:text-gray-400">{{ t('finance.reports_desc') }}</p>
                </div>
            </div>

            <!-- Reports Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="report in reports"
                    :key="report.id"
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-all group"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center">
                            <component :is="getIcon(report.icon)" class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                            {{ report.period }}
                        </span>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ report.name }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ report.description }}</p>

                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                        <Link
                            :href="getReportUrl(report.id)"
                            class="inline-flex items-center text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 group-hover:underline"
                        >
                            {{ t('common.view') }}
                            <ArrowRightIcon class="w-4 h-4 ml-1" />
                        </Link>
                        <button
                            class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                        >
                            <DocumentArrowDownIcon class="w-4 h-4 mr-1" />
                            {{ t('finance.download') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="reports.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center">
                <ChartBarIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ t('finance.no_reports') }}</h3>
                <p class="text-gray-500 dark:text-gray-400">{{ t('finance.no_reports_desc') }}</p>
            </div>

            <!-- Quick Stats -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h3 class="text-lg font-semibold mb-1">{{ t('finance.quick_analysis') }}</h3>
                        <p class="text-indigo-100 text-sm">{{ t('finance.quick_analysis_desc') }}</p>
                    </div>
                    <Link
                        href="/finance"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition-colors"
                    >
                        {{ t('finance.go_to_dashboard') }}
                        <ArrowRightIcon class="w-4 h-4" />
                    </Link>
                </div>
            </div>
        </div>
    </FinanceLayout>
</template>
