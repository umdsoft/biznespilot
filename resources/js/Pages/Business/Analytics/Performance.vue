<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();
import {
    TrophyIcon,
    UserGroupIcon,
    GiftIcon,
    SignalIcon,
    ChartBarIcon,
    ArrowDownTrayIcon,
    FunnelIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    dream_buyer_performance: {
        type: Array,
        default: () => [],
    },
    offer_performance: {
        type: Array,
        default: () => [],
    },
    source_analysis: {
        type: Array,
        default: () => [],
    },
    filters: Object,
});

const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const activeTab = ref('dream-buyers');

// Format price
const formatPrice = (price) => {
    return new Intl.NumberFormat('uz-UZ').format(price) + ' so\'m';
};

// Format percentage
const formatPercent = (value) => {
    return value?.toFixed(1) || '0';
};

// Get performance badge color
const getPerformanceBadge = (conversionRate) => {
    if (conversionRate >= 30) return { bg: 'bg-green-100 dark:bg-green-900/50', text: 'text-green-800 dark:text-green-300', label: 'Excellent' };
    if (conversionRate >= 20) return { bg: 'bg-blue-100 dark:bg-blue-900/50', text: 'text-blue-800 dark:text-blue-300', label: 'Good' };
    if (conversionRate >= 10) return { bg: 'bg-yellow-100 dark:bg-yellow-900/50', text: 'text-yellow-800 dark:text-yellow-300', label: 'Average' };
    return { bg: 'bg-red-100 dark:bg-red-900/50', text: 'text-red-800 dark:text-red-300', label: 'Poor' };
};

// Get ROI badge color
const getRoiBadge = (roi) => {
    if (roi >= 200) return { bg: 'bg-green-100 dark:bg-green-900/50', text: 'text-green-800 dark:text-green-300', label: 'Excellent' };
    if (roi >= 100) return { bg: 'bg-blue-100 dark:bg-blue-900/50', text: 'text-blue-800 dark:text-blue-300', label: 'Good' };
    if (roi >= 0) return { bg: 'bg-yellow-100 dark:bg-yellow-900/50', text: 'text-yellow-800 dark:text-yellow-300', label: 'Break Even' };
    return { bg: 'bg-red-100 dark:bg-red-900/50', text: 'text-red-800 dark:text-red-300', label: 'Loss' };
};

const applyFilters = () => {
    window.location.href = route('business.analytics.performance', {
        date_from: dateFrom.value,
        date_to: dateTo.value,
    });
};

const clearFilters = () => {
    dateFrom.value = '';
    dateTo.value = '';
    window.location.href = route('business.analytics.performance');
};

const exportingPDF = ref(false);
const exportingExcel = ref(false);

const exportToPDF = async () => {
    exportingPDF.value = true;
    try {
        const response = await fetch(route('business.analytics.export.pdf'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                report_type: 'performance',
                date_from: dateFrom.value,
                date_to: dateTo.value,
            }),
        });

        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'performance_report.pdf';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } else {
            alert(t('common.error'));
        }
    } catch (error) {
        console.error('Export error:', error);
        alert(t('common.error'));
    } finally {
        exportingPDF.value = false;
    }
};

const exportToExcel = async () => {
    exportingExcel.value = true;
    try {
        const response = await fetch(route('business.analytics.export.excel'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                report_type: 'performance',
                date_from: dateFrom.value,
                date_to: dateTo.value,
            }),
        });

        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'performance_report.xlsx';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } else {
            alert(t('common.error'));
        }
    } catch (error) {
        console.error('Export error:', error);
        alert(t('common.error'));
    } finally {
        exportingExcel.value = false;
    }
};
</script>

<template>
    <BusinessLayout :title="t('analytics.performance_reports')">
        <Head :title="t('analytics.performance_reports')" />

        <div class="py-12">
            <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-3">
                            <TrophyIcon class="w-10 h-10 text-yellow-600 dark:text-yellow-400" />
                            {{ t('analytics.performance_reports') }}
                        </h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            {{ t('analytics.performance_desc') }}
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <Link
                            :href="route('business.analytics.index')"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors gap-2"
                        >
                            <ChartBarIcon class="w-4 h-4" />
                            Dashboard
                        </Link>
                        <button
                            @click="exportToExcel"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors gap-2"
                        >
                            <ArrowDownTrayIcon class="w-4 h-4" />
                            Excel
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-8">
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ t('reports.filter.start_date') }}
                            </label>
                            <input
                                v-model="dateFrom"
                                type="date"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                            />
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ t('reports.filter.end_date') }}
                            </label>
                            <input
                                v-model="dateTo"
                                type="date"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                            />
                        </div>
                        <button
                            @click="applyFilters"
                            class="px-6 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-medium rounded-lg transition-colors"
                        >
                            {{ t('common.filter') }}
                        </button>
                        <button
                            @click="clearFilters"
                            class="px-6 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors"
                        >
                            {{ t('reports.filter.reset') }}
                        </button>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md mb-8">
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="flex -mb-px">
                            <button
                                @click="activeTab = 'dream-buyers'"
                                :class="[
                                    activeTab === 'dream-buyers'
                                        ? 'border-yellow-600 text-yellow-600 dark:text-yellow-400'
                                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300',
                                    'flex-1 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors'
                                ]"
                            >
                                <div class="flex items-center justify-center gap-2">
                                    <UserGroupIcon class="w-5 h-5" />
                                    <span>{{ t('dashboard.ideal_customers') }}</span>
                                </div>
                            </button>
                            <button
                                @click="activeTab = 'offers'"
                                :class="[
                                    activeTab === 'offers'
                                        ? 'border-yellow-600 text-yellow-600 dark:text-yellow-400'
                                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300',
                                    'flex-1 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors'
                                ]"
                            >
                                <div class="flex items-center justify-center gap-2">
                                    <GiftIcon class="w-5 h-5" />
                                    <span>Offers</span>
                                </div>
                            </button>
                            <button
                                @click="activeTab = 'sources'"
                                :class="[
                                    activeTab === 'sources'
                                        ? 'border-yellow-600 text-yellow-600 dark:text-yellow-400'
                                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300',
                                    'flex-1 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors'
                                ]"
                            >
                                <div class="flex items-center justify-center gap-2">
                                    <SignalIcon class="w-5 h-5" />
                                    <span>Lead Sources</span>
                                </div>
                            </button>
                        </nav>
                    </div>

                    <!-- Ideal Mijozlar Tab -->
                    <div v-show="activeTab === 'dream-buyers'" class="p-6">
                        <div v-if="dream_buyer_performance && dream_buyer_performance.length > 0">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:border-gray-700">
                                            <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Ideal Mijoz</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Total Leads</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Won Deals</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Conversion Rate</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Total Revenue</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Avg Deal Size</th>
                                            <th class="text-center py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Performance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(buyer, index) in dream_buyer_performance"
                                            :key="buyer.dream_buyer_id"
                                            :class="[
                                                index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700/50',
                                                'hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors'
                                            ]"
                                        >
                                            <td class="py-4 px-4">
                                                <div class="flex items-center gap-2">
                                                    <div v-if="index === 0" class="w-6 h-6 bg-yellow-100 dark:bg-yellow-900/50 rounded-full flex items-center justify-center">
                                                        <TrophyIcon class="w-4 h-4 text-yellow-600 dark:text-yellow-400" />
                                                    </div>
                                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ buyer.dream_buyer_name }}</span>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 text-right text-gray-700 dark:text-gray-300">{{ buyer.total_leads }}</td>
                                            <td class="py-4 px-4 text-right font-semibold text-green-600 dark:text-green-400">{{ buyer.won_leads }}</td>
                                            <td class="py-4 px-4 text-right">
                                                <span class="font-semibold text-purple-600 dark:text-purple-400">{{ formatPercent(buyer.conversion_rate) }}%</span>
                                            </td>
                                            <td class="py-4 px-4 text-right font-semibold text-blue-600 dark:text-blue-400">
                                                {{ formatPrice(buyer.total_revenue) }}
                                            </td>
                                            <td class="py-4 px-4 text-right text-gray-700 dark:text-gray-300">
                                                {{ formatPrice(buyer.avg_deal_size) }}
                                            </td>
                                            <td class="py-4 px-4 text-center">
                                                <span
                                                    :class="[
                                                        getPerformanceBadge(buyer.conversion_rate).bg,
                                                        getPerformanceBadge(buyer.conversion_rate).text,
                                                        'inline-block px-3 py-1 rounded-full text-xs font-semibold'
                                                    ]"
                                                >
                                                    {{ getPerformanceBadge(buyer.conversion_rate).label }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div v-else class="text-center py-12">
                            <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
                            <p class="mt-4 text-gray-500 dark:text-gray-400">{{ t('reports.no_data') }}</p>
                        </div>
                    </div>

                    <!-- Offers Tab -->
                    <div v-show="activeTab === 'offers'" class="p-6">
                        <div v-if="offer_performance && offer_performance.length > 0">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:border-gray-700">
                                            <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Offer</th>
                                            <th class="text-center py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Value Score</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Total Leads</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Won Deals</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Conversion Rate</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Total Revenue</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">ROI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(offer, index) in offer_performance"
                                            :key="offer.offer_id"
                                            :class="[
                                                index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700/50',
                                                'hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors'
                                            ]"
                                        >
                                            <td class="py-4 px-4">
                                                <div class="flex items-center gap-2">
                                                    <div v-if="index === 0" class="w-6 h-6 bg-yellow-100 dark:bg-yellow-900/50 rounded-full flex items-center justify-center">
                                                        <TrophyIcon class="w-4 h-4 text-yellow-600 dark:text-yellow-400" />
                                                    </div>
                                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ offer.offer_name }}</span>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 text-center">
                                                <span class="inline-block px-3 py-1 bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300 rounded-full text-sm font-bold">
                                                    {{ offer.value_score || 'N/A' }}
                                                </span>
                                            </td>
                                            <td class="py-4 px-4 text-right text-gray-700 dark:text-gray-300">{{ offer.total_leads }}</td>
                                            <td class="py-4 px-4 text-right font-semibold text-green-600 dark:text-green-400">{{ offer.won_leads }}</td>
                                            <td class="py-4 px-4 text-right">
                                                <span class="font-semibold text-purple-600 dark:text-purple-400">{{ formatPercent(offer.conversion_rate) }}%</span>
                                            </td>
                                            <td class="py-4 px-4 text-right font-semibold text-blue-600 dark:text-blue-400">
                                                {{ formatPrice(offer.total_revenue) }}
                                            </td>
                                            <td class="py-4 px-4 text-right">
                                                <span
                                                    :class="[
                                                        getRoiBadge(offer.roi).text,
                                                        'font-semibold'
                                                    ]"
                                                >
                                                    {{ formatPercent(offer.roi) }}%
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div v-else class="text-center py-12">
                            <GiftIcon class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
                            <p class="mt-4 text-gray-500 dark:text-gray-400">{{ t('reports.no_data') }}</p>
                        </div>
                    </div>

                    <!-- Sources Tab -->
                    <div v-show="activeTab === 'sources'" class="p-6">
                        <div v-if="source_analysis && source_analysis.length > 0">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:border-gray-700">
                                            <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Source</th>
                                            <th class="text-center py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Type</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Total Leads</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Won Deals</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Conversion Rate</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Total Revenue</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Cost per Lead</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">ROI</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">ROAS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(source, index) in source_analysis"
                                            :key="source.source_id"
                                            :class="[
                                                index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700/50',
                                                'hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors'
                                            ]"
                                        >
                                            <td class="py-4 px-4">
                                                <div class="flex items-center gap-2">
                                                    <div v-if="index === 0" class="w-6 h-6 bg-yellow-100 dark:bg-yellow-900/50 rounded-full flex items-center justify-center">
                                                        <TrophyIcon class="w-4 h-4 text-yellow-600 dark:text-yellow-400" />
                                                    </div>
                                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ source.source_name }}</span>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 text-center">
                                                <span class="inline-block px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs font-medium">
                                                    {{ source.channel_type }}
                                                </span>
                                            </td>
                                            <td class="py-4 px-4 text-right text-gray-700 dark:text-gray-300">{{ source.total_leads }}</td>
                                            <td class="py-4 px-4 text-right font-semibold text-green-600 dark:text-green-400">{{ source.won_leads }}</td>
                                            <td class="py-4 px-4 text-right">
                                                <span class="font-semibold text-purple-600 dark:text-purple-400">{{ formatPercent(source.conversion_rate) }}%</span>
                                            </td>
                                            <td class="py-4 px-4 text-right font-semibold text-blue-600 dark:text-blue-400">
                                                {{ formatPrice(source.total_revenue) }}
                                            </td>
                                            <td class="py-4 px-4 text-right text-gray-700 dark:text-gray-300">
                                                {{ formatPrice(source.cost_per_lead) }}
                                            </td>
                                            <td class="py-4 px-4 text-right">
                                                <span
                                                    :class="[
                                                        getRoiBadge(source.roi).text,
                                                        'font-semibold'
                                                    ]"
                                                >
                                                    {{ formatPercent(source.roi) }}%
                                                </span>
                                            </td>
                                            <td class="py-4 px-4 text-right">
                                                <span class="font-semibold text-orange-600 dark:text-orange-400">{{ source.roas?.toFixed(2) || '0.00' }}x</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div v-else class="text-center py-12">
                            <SignalIcon class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
                            <p class="mt-4 text-gray-500 dark:text-gray-400">{{ t('reports.no_data') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
