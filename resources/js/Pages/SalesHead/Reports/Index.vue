<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { useI18n } from '@/i18n';
import {
    DocumentTextIcon,
    CalendarDaysIcon,
    ArrowDownTrayIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    reports: {
        type: Array,
        default: () => [],
    },
});

const reportTypes = computed(() => [
    { id: 'daily', name: t('reports.daily_report'), description: t('reports.daily_description'), href: '/sales-head/reports/daily', icon: CalendarDaysIcon },
    { id: 'weekly', name: t('reports.weekly_report'), description: t('reports.weekly_description'), href: '/sales-head/reports/weekly', icon: CalendarDaysIcon },
    { id: 'monthly', name: t('reports.monthly_report'), description: t('reports.monthly_description'), href: '/sales-head/reports/monthly', icon: ChartBarIcon },
]);

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('uz-UZ');
};
</script>

<template>
    <SalesHeadLayout :title="t('nav.reports')">
        <Head :title="t('nav.reports')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('nav.reports') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('reports.sales_reports_analysis') }}</p>
                </div>
                <Link
                    href="/sales-head/reports/export"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors"
                >
                    <ArrowDownTrayIcon class="w-5 h-5" />
                    {{ t('common.export') }}
                </Link>
            </div>

            <!-- Report Types -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Link
                    v-for="report in reportTypes"
                    :key="report.id"
                    :href="report.href"
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg hover:border-emerald-300 dark:hover:border-emerald-600 transition-all group"
                >
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center group-hover:bg-emerald-200 dark:group-hover:bg-emerald-900/50 transition-colors">
                            <component :is="report.icon" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ report.name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ report.description }}</p>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Recent Reports -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ t('reports.recent_reports') }}</h2>
                </div>

                <div v-if="reports.length === 0" class="p-12 text-center">
                    <DocumentTextIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ t('reports.no_reports') }}</h3>
                    <p class="text-gray-500 dark:text-gray-400">{{ t('reports.no_reports_created') }}</p>
                </div>

                <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="report in reports" :key="report.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                    <DocumentTextIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ report.name }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(report.created_at) }}</p>
                                </div>
                            </div>
                            <button class="p-2 text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                <ArrowDownTrayIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SalesHeadLayout>
</template>
