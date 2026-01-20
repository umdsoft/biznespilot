<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { Chart, registerables } from 'chart.js';
import { useI18n } from '@/i18n';
import {
    ArrowLeftIcon,
    ChartBarIcon,
    EyeIcon,
    HandThumbUpIcon,
    ArrowTrendingUpIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

// Register Chart.js components
Chart.register(...registerables);

const props = defineProps({
    panelType: {
        type: String,
        required: true,
        validator: (value) => ['business', 'marketing'].includes(value),
    },
    channel: {
        type: Object,
        required: true
    },
    metrics: {
        type: Array,
        default: () => []
    },
    chartData: {
        type: Object,
        default: () => ({ labels: [], datasets: [] })
    },
    summary: {
        type: Object,
        default: () => ({})
    },
    period: {
        type: Number,
        default: 30
    }
});

// Helper to generate correct href based on panel type
const getHref = (path) => {
    const prefix = props.panelType === 'business' ? '/business/marketing' : '/marketing';
    return prefix + path;
};

// Helper to get route name based on panel type
const getRouteName = (name) => {
    const prefix = props.panelType === 'business' ? 'business.marketing.' : 'marketing.';
    return prefix + name;
};

const chartCanvas = ref(null);
let chartInstance = null;

// Format numbers
const formatNumber = (num) => {
    if (!num && num !== 0) return '0';
    return new Intl.NumberFormat('uz-UZ').format(num);
};

// Format percentage
const formatPercentage = (num) => {
    if (!num) return '0.00';
    return Number(num).toFixed(2);
};

// Format currency (from kopeks)
const formatCurrency = (kopeks) => {
    if (!kopeks) return '0';
    const amount = kopeks / 100;
    return new Intl.NumberFormat('uz-UZ', {
        style: 'currency',
        currency: 'UZS',
        minimumFractionDigits: 0
    }).format(amount);
};

// Get platform name
const getPlatformName = () => {
    const names = {
        instagram: 'Instagram',
        telegram: 'Telegram',
        facebook: 'Facebook',
        google_ads: 'Google Ads'
    };
    return names[props.channel.type] || props.channel.type;
};

// Get platform icon
const getPlatformIcon = () => {
    const icons = {
        instagram: 'instagram',
        telegram: 'telegram',
        facebook: 'facebook',
        google_ads: 'google_ads'
    };
    return icons[props.channel.type] || 'default';
};

// Initialize chart
onMounted(() => {
    if (chartCanvas.value && props.chartData.labels.length > 0) {
        const ctx = chartCanvas.value.getContext('2d');

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: props.chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatNumber(value);
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }
});

// Cleanup
onUnmounted(() => {
    if (chartInstance) {
        chartInstance.destroy();
    }
});

// Period options for filter
const periodOptions = [
    { value: 7, label: t('marketing.days_7') },
    { value: 14, label: t('marketing.days_14') },
    { value: 30, label: t('marketing.days_30') },
    { value: 60, label: t('marketing.days_60') },
    { value: 90, label: t('marketing.days_90') },
];

const changePeriod = (days) => {
    window.location.href = `${getHref('/channels/' + props.channel.id)}?period=${days}`;
};
</script>

<template>
    <Head :title="`${channel.name} - Analytics`" />

    <div>
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <Link
                    :href="getHref('/channels')"
                    class="inline-flex items-center text-gray-600 hover:text-gray-900"
                >
                    <ArrowLeftIcon class="w-5 h-5 mr-2" />
                    {{ t('common.back') }}
                </Link>
                <div>
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center"
                             :class="{
                                 'bg-gradient-to-br from-pink-500 to-purple-600': getPlatformIcon() === 'instagram',
                                 'bg-gradient-to-br from-blue-400 to-blue-600': getPlatformIcon() === 'telegram',
                                 'bg-blue-600': getPlatformIcon() === 'facebook',
                                 'bg-gradient-to-br from-red-500 to-yellow-500': getPlatformIcon() === 'google_ads',
                                 'bg-gray-500': getPlatformIcon() === 'default'
                             }">
                            <svg v-if="getPlatformIcon() === 'instagram'" class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069z"/>
                            </svg>
                            <svg v-else-if="getPlatformIcon() === 'telegram'" class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                            </svg>
                            <svg v-else-if="getPlatformIcon() === 'facebook'" class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            <ChartBarIcon v-else class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                                {{ channel.name }}
                            </h2>
                            <p class="text-sm text-gray-600">
                                {{ getPlatformName() }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Period Filter -->
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-600">{{ t('marketing.period') }}:</span>
                <select
                    @change="(e) => changePeriod(e.target.value)"
                    :value="period"
                    class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option v-for="option in periodOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </div>
        </div>

        <div class="max-w-8xl mx-auto">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Reach / Impressions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <EyeIcon class="h-10 w-10 text-blue-600" />
                            </div>
                            <div class="ml-4 flex-1">
                                <p class="text-sm font-medium text-gray-500 truncate">
                                    {{ channel.type === 'google_ads' ? t('marketing.total_impressions') : t('marketing.total_reach') }}
                                </p>
                                <p class="text-2xl font-bold text-gray-900">
                                    {{ formatNumber(summary.total_reach || summary.total_impressions || 0) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Engagement / Clicks -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <HandThumbUpIcon class="h-10 w-10 text-green-600" />
                            </div>
                            <div class="ml-4 flex-1">
                                <p class="text-sm font-medium text-gray-500 truncate">
                                    {{ channel.type === 'google_ads' ? t('marketing.total_clicks') : t('marketing.total_engagement') }}
                                </p>
                                <p class="text-2xl font-bold text-gray-900">
                                    {{ formatNumber(summary.total_engagement || summary.total_clicks || 0) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Engagement Rate / CTR -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <ChartBarIcon class="h-10 w-10 text-purple-600" />
                            </div>
                            <div class="ml-4 flex-1">
                                <p class="text-sm font-medium text-gray-500 truncate">
                                    {{ channel.type === 'google_ads' ? t('marketing.avg_ctr') : t('marketing.avg_engagement') }}
                                </p>
                                <p class="text-2xl font-bold text-gray-900">
                                    {{ formatPercentage(summary.avg_engagement_rate || summary.avg_ctr || 0) }}%
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Growth / ROAS -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <ArrowTrendingUpIcon
                                    :class="[
                                        'h-10 w-10',
                                        (summary.growth || 0) >= 0 ? 'text-green-600' : 'text-red-600'
                                    ]"
                                />
                            </div>
                            <div class="ml-4 flex-1">
                                <p class="text-sm font-medium text-gray-500 truncate">
                                    {{ channel.type === 'google_ads' ? t('marketing.avg_roas') : t('marketing.growth') }}
                                </p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <span v-if="channel.type === 'google_ads'">
                                        {{ formatPercentage(summary.avg_roas || 0) }}
                                    </span>
                                    <span v-else>
                                        {{ (summary.growth || 0) >= 0 ? '+' : '' }}{{ formatNumber(summary.growth || 0) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        {{ t('marketing.performance_chart') }} ({{ period }} {{ t('marketing.days') }})
                    </h3>
                    <div v-if="chartData.labels.length > 0" style="height: 400px;">
                        <canvas ref="chartCanvas"></canvas>
                    </div>
                    <div v-else class="text-center py-12 text-gray-500">
                        <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400 mb-2" />
                        <p>{{ t('marketing.no_data_for_period') }}</p>
                    </div>
                </div>
            </div>

            <!-- Google Ads Specific Stats -->
            <div v-if="channel.type === 'google_ads' && summary.total_cost" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        {{ t('marketing.cost_and_conversion') }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="border-l-4 border-red-500 pl-4">
                            <p class="text-sm text-gray-600">{{ t('marketing.total_cost') }}</p>
                            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(summary.total_cost) }}</p>
                        </div>
                        <div class="border-l-4 border-green-500 pl-4">
                            <p class="text-sm text-gray-600">{{ t('marketing.total_conversions') }}</p>
                            <p class="text-2xl font-bold text-gray-900">{{ formatNumber(summary.total_conversions || 0) }}</p>
                        </div>
                        <div class="border-l-4 border-blue-500 pl-4">
                            <p class="text-sm text-gray-600">{{ t('marketing.avg_cpa') }}</p>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ summary.total_conversions > 0 ? formatCurrency(summary.total_cost / summary.total_conversions * 100) : '0' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Metrics Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        {{ t('marketing.recent_data') }}
                    </h3>
                    <div v-if="metrics.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ t('common.date') }}
                                    </th>
                                    <th v-if="channel.type === 'instagram'" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ t('marketing.followers') }}
                                    </th>
                                    <th v-if="channel.type === 'telegram'" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ t('marketing.members') }}
                                    </th>
                                    <th v-if="channel.type === 'facebook'" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ t('marketing.page_followers') }}
                                    </th>
                                    <th v-if="channel.type === 'google_ads'" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ t('marketing.impressions') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ channel.type === 'google_ads' ? t('marketing.clicks') : t('marketing.reach') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ channel.type === 'google_ads' ? t('marketing.conversions') : t('marketing.engagement') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ channel.type === 'google_ads' ? t('marketing.ctr') + ' %' : t('marketing.engagement_rate') + ' %' }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="metric in metrics.slice(0, 10)" :key="metric.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ new Date(metric.metric_date).toLocaleDateString('uz-UZ') }}
                                    </td>
                                    <td v-if="channel.type === 'instagram'" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatNumber(metric.followers_count) }}
                                    </td>
                                    <td v-if="channel.type === 'telegram'" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatNumber(metric.members_count) }}
                                    </td>
                                    <td v-if="channel.type === 'facebook'" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatNumber(metric.page_followers) }}
                                    </td>
                                    <td v-if="channel.type === 'google_ads'" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatNumber(metric.impressions) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span v-if="channel.type === 'google_ads'">{{ formatNumber(metric.clicks) }}</span>
                                        <span v-else>{{ formatNumber(metric.reach || metric.total_views) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ channel.type === 'google_ads' ? formatNumber(metric.conversions) : formatNumber(metric.likes + metric.comments + (metric.shares || 0)) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span v-if="channel.type === 'google_ads'">{{ formatPercentage(metric.ctr) }}%</span>
                                        <span v-else>{{ formatPercentage(metric.engagement_rate) }}%</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500">
                        {{ t('common.no_data') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
