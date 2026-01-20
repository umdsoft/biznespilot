<template>
    <AppLayout :title="t('generated_reports.title')">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    {{ t('generated_reports.title') }}
                </h2>
                <div class="flex items-center space-x-3">
                    <Link
                        href="/business/generated-reports/schedules"
                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <ClockIcon class="w-4 h-4 mr-2" />
                        {{ t('generated_reports.schedule') }}
                    </Link>
                    <div class="relative" ref="generateDropdownRef">
                        <button
                            @click="showGenerateMenu = !showGenerateMenu"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium"
                        >
                            <PlusIcon class="w-4 h-4 mr-2" />
                            {{ t('generated_reports.new_report') }}
                            <ChevronDownIcon class="w-4 h-4 ml-2" />
                        </button>

                        <div
                            v-if="showGenerateMenu"
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-10"
                        >
                            <button
                                @click="generateReport('daily')"
                                class="block w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                {{ t('generated_reports.type.daily') }}
                            </button>
                            <button
                                @click="generateReport('weekly')"
                                class="block w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                {{ t('generated_reports.type.weekly') }}
                            </button>
                            <button
                                @click="generateReport('monthly')"
                                class="block w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                {{ t('generated_reports.type.monthly') }}
                            </button>
                            <button
                                @click="generateReport('quarterly')"
                                class="block w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                {{ t('generated_reports.type.quarterly') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Reports List -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ t('generated_reports.table.report') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ t('generated_reports.table.type') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ t('generated_reports.table.period') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ t('generated_reports.table.created') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ t('generated_reports.table.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="report in reports.data"
                                :key="report.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <DocumentChartBarIcon class="w-5 h-5 text-gray-400 mr-3" />
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ report.title }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ report.summary }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="[
                                            'px-2 py-1 rounded text-xs font-medium',
                                            getTypeBadgeClass(report.report_type)
                                        ]"
                                    >
                                        {{ getTypeLabel(report.report_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatDate(report.period_start) }} - {{ formatDate(report.period_end) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatDateTime(report.created_at) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <Link
                                            :href="`/business/generated-reports/${report.id}`"
                                            class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
                                            :title="t('generated_reports.view')"
                                        >
                                            <EyeIcon class="w-5 h-5" />
                                        </Link>
                                        <a
                                            v-if="report.pdf_path"
                                            :href="`/business/generated-reports/${report.id}/download`"
                                            class="p-2 text-gray-400 hover:text-green-600 dark:hover:text-green-400"
                                            :title="t('generated_reports.download')"
                                        >
                                            <ArrowDownTrayIcon class="w-5 h-5" />
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="reports.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <DocumentChartBarIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                                    <p class="text-gray-500 dark:text-gray-400">{{ t('generated_reports.empty') }}</p>
                                    <button
                                        @click="showGenerateMenu = true"
                                        class="mt-4 text-blue-600 hover:text-blue-700 text-sm font-medium"
                                    >
                                        {{ t('generated_reports.create_new') }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="reports.last_page > 1" class="mt-6">
                    <Pagination :links="reports.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import {
    ClockIcon,
    PlusIcon,
    ChevronDownIcon,
    DocumentChartBarIcon,
    EyeIcon,
    ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline';
import { format } from 'date-fns';
import { uz } from 'date-fns/locale';
import { useI18n } from '@/i18n';

const { t } = useI18n();

interface Props {
    reports: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    scheduledReports: any[];
}

const props = defineProps<Props>();

const showGenerateMenu = ref(false);
const generateDropdownRef = ref<HTMLElement | null>(null);
const isGenerating = ref(false);

function generateReport(type: string) {
    showGenerateMenu.value = false;
    isGenerating.value = true;

    router.post(`/business/generated-reports/generate/${type}`, {}, {
        preserveScroll: true,
        onFinish: () => {
            isGenerating.value = false;
        },
    });
}

function getTypeLabel(type: string): string {
    const typeKey = `generated_reports.type_label.${type}`;
    return t(typeKey) || type;
}

function getTypeBadgeClass(type: string): string {
    const classes: Record<string, string> = {
        daily_brief: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        weekly_summary: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        monthly_report: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        quarterly_review: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    };
    return classes[type] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
}

function formatDate(dateString: string): string {
    try {
        return format(new Date(dateString), 'dd.MM.yyyy', { locale: uz });
    } catch {
        return dateString;
    }
}

function formatDateTime(dateString: string): string {
    try {
        return format(new Date(dateString), 'dd.MM.yyyy HH:mm', { locale: uz });
    } catch {
        return dateString;
    }
}

// Close dropdown when clicking outside
function handleClickOutside(event: MouseEvent) {
    if (generateDropdownRef.value && !generateDropdownRef.value.contains(event.target as Node)) {
        showGenerateMenu.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>
