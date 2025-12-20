<script setup>
import { Head, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { Chart, registerables } from 'chart.js';
import {
    ArrowLeftIcon,
    ChartBarIcon,
    EyeIcon,
    HandThumbUpIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
} from '@heroicons/vue/24/outline';

// Register Chart.js components
Chart.register(...registerables);

const props = defineProps({
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
        instagram: '📸',
        telegram: '✈️',
        facebook: '👥',
        google_ads: '🎯'
    };
    return icons[props.channel.type] || '📊';
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
const cleanup = () => {
    if (chartInstance) {
        chartInstance.destroy();
    }
};

// Period options for filter
const periodOptions = [
    { value: 7, label: '7 kun' },
    { value: 14, label: '14 kun' },
    { value: 30, label: '30 kun' },
    { value: 60, label: '60 kun' },
    { value: 90, label: '90 kun' },
];

const changePeriod = (days) => {
    window.location.href = `${route('business.marketing.channels.show', props.channel.id)}?period=${days}`;
};
</script>

<template>
    <Head :title="`${channel.name} - Analytics`" />

    <BusinessLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('business.marketing.channels')"
                        class="inline-flex items-center text-gray-600 hover:text-gray-900"
                    >
                        <ArrowLeftIcon class="w-5 h-5 mr-2" />
                        Orqaga
                    </Link>
                    <div>
                        <div class="flex items-center space-x-3">
                            <span class="text-3xl">{{ getPlatformIcon() }}</span>
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
                    <span class="text-sm text-gray-600">Davr:</span>
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
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                                        {{ channel.type === 'google_ads' ? 'Total Impressions' : 'Total Reach' }}
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
                                        {{ channel.type === 'google_ads' ? 'Total Clicks' : 'Total Engagement' }}
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
                                        {{ channel.type === 'google_ads' ? 'Avg CTR' : 'Avg Engagement' }}
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
                                        {{ channel.type === 'google_ads' ? 'Avg ROAS' : 'Growth' }}
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
                            Performance Grafigi ({{ period }} kun)
                        </h3>
                        <div v-if="chartData.labels.length > 0" style="height: 400px;">
                            <canvas ref="chartCanvas"></canvas>
                        </div>
                        <div v-else class="text-center py-12 text-gray-500">
                            <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400 mb-2" />
                            <p>Bu davr uchun ma'lumotlar yo'q</p>
                        </div>
                    </div>
                </div>

                <!-- Google Ads Specific Stats -->
                <div v-if="channel.type === 'google_ads' && summary.total_cost" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Xarajat va Konversiya
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="border-l-4 border-red-500 pl-4">
                                <p class="text-sm text-gray-600">Jami Xarajat</p>
                                <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(summary.total_cost) }}</p>
                            </div>
                            <div class="border-l-4 border-green-500 pl-4">
                                <p class="text-sm text-gray-600">Jami Konversiyalar</p>
                                <p class="text-2xl font-bold text-gray-900">{{ formatNumber(summary.total_conversions || 0) }}</p>
                            </div>
                            <div class="border-l-4 border-blue-500 pl-4">
                                <p class="text-sm text-gray-600">Avg CPA</p>
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
                            Oxirgi Ma'lumotlar
                        </h3>
                        <div v-if="metrics.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Sana
                                        </th>
                                        <th v-if="channel.type === 'instagram'" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Followers
                                        </th>
                                        <th v-if="channel.type === 'telegram'" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Members
                                        </th>
                                        <th v-if="channel.type === 'facebook'" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Page Followers
                                        </th>
                                        <th v-if="channel.type === 'google_ads'" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Impressions
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ channel.type === 'google_ads' ? 'Clicks' : 'Reach' }}
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ channel.type === 'google_ads' ? 'Conversions' : 'Engagement' }}
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ channel.type === 'google_ads' ? 'CTR %' : 'Eng. Rate %' }}
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
                            Ma'lumotlar yo'q
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
