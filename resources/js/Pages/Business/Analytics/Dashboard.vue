<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import {
    ChartBarIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    CurrencyDollarIcon,
    UserGroupIcon,
    ShoppingBagIcon,
    FunnelIcon,
    TrophyIcon,
    CalendarIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    metrics: Object,
    revenue_trends: Object,
    top_performers: Object,
    funnel_summary: Object,
    filters: Object,
});

const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

// Format price
const formatPrice = (price) => {
    return new Intl.NumberFormat('uz-UZ').format(price) + ' so\'m';
};

// Format percentage
const formatPercent = (value) => {
    return value?.toFixed(1) || '0';
};

// Get trend color
const getTrendColor = (value) => {
    if (value > 0) return 'text-green-600';
    if (value < 0) return 'text-red-600';
    return 'text-gray-600';
};

// Get trend icon
const getTrendIcon = (value) => {
    return value > 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon;
};

// Chart data for revenue trends
const revenueChartData = computed(() => {
    if (!props.revenue_trends?.chart_data) return null;

    return {
        labels: props.revenue_trends.chart_data.labels,
        datasets: props.revenue_trends.chart_data.datasets.map(dataset => ({
            ...dataset,
            fill: true,
        })),
    };
});

const dealCountChartData = computed(() => {
    if (!props.revenue_trends?.deal_count_chart) return null;

    return {
        labels: props.revenue_trends.deal_count_chart.labels,
        datasets: props.revenue_trends.deal_count_chart.datasets.map(dataset => ({
            ...dataset,
            fill: true,
        })),
    };
});

// Chart options
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'top',
        },
        tooltip: {
            mode: 'index',
            intersect: false,
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(0, 0, 0, 0.05)',
            },
        },
        x: {
            grid: {
                display: false,
            },
        },
    },
};

const applyFilters = () => {
    window.location.href = route('business.analytics.index', {
        date_from: dateFrom.value,
        date_to: dateTo.value,
    });
};

const clearFilters = () => {
    dateFrom.value = '';
    dateTo.value = '';
    window.location.href = route('business.analytics.index');
};
</script>

<template>
    <BusinessLayout title="Sales Analytics">
        <Head title="Analytics Dashboard" />

        <div class="py-12 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-3">
                            <ChartBarIcon class="w-10 h-10 text-blue-600 dark:text-blue-400" />
                            Sales Analytics
                        </h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Sotuv va konversiya ko'rsatkichlarini kuzatish
                        </p>
                    </div>

                    <!-- Quick Links -->
                    <div class="flex gap-2">
                        <Link
                            :href="route('business.analytics.funnel')"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors gap-2"
                        >
                            <FunnelIcon class="w-4 h-4" />
                            Funnel
                        </Link>
                        <Link
                            :href="route('business.analytics.performance')"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors gap-2"
                        >
                            <TrophyIcon class="w-4 h-4" />
                            Performance
                        </Link>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-8">
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Boshlanish sanasi
                            </label>
                            <input
                                v-model="dateFrom"
                                type="date"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tugash sanasi
                            </label>
                            <input
                                v-model="dateTo"
                                type="date"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                        <button
                            @click="applyFilters"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                        >
                            Qo'llash
                        </button>
                        <button
                            @click="clearFilters"
                            class="px-6 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors"
                        >
                            Tozalash
                        </button>
                    </div>
                </div>

                <!-- Key Metrics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Revenue -->
                    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between mb-2">
                            <CurrencyDollarIcon class="w-8 h-8 opacity-80" />
                            <component
                                :is="getTrendIcon(metrics.revenue_growth)"
                                :class="['w-6 h-6', metrics.revenue_growth > 0 ? 'text-green-200' : 'text-red-200']"
                            />
                        </div>
                        <p class="text-sm font-medium opacity-90 mb-1">Total Revenue</p>
                        <p class="text-3xl font-bold mb-1">{{ formatPrice(metrics.total_revenue || 0) }}</p>
                        <p class="text-xs opacity-80">
                            {{ metrics.revenue_growth > 0 ? '+' : '' }}{{ formatPercent(metrics.revenue_growth) }}% vs oldingi davr
                        </p>
                    </div>

                    <!-- Total Leads -->
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between mb-2">
                            <UserGroupIcon class="w-8 h-8 opacity-80" />
                            <span class="text-sm font-medium opacity-90">{{ metrics.new_leads || 0 }} yangi</span>
                        </div>
                        <p class="text-sm font-medium opacity-90 mb-1">Total Leads</p>
                        <p class="text-3xl font-bold mb-1">{{ metrics.total_leads || 0 }}</p>
                        <p class="text-xs opacity-80">
                            {{ metrics.active_pipeline_deals || 0 }} active pipeline
                        </p>
                    </div>

                    <!-- Won Deals -->
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between mb-2">
                            <ShoppingBagIcon class="w-8 h-8 opacity-80" />
                            <span class="text-sm font-medium opacity-90">
                                {{ formatPercent(metrics.conversion_rate) }}% CR
                            </span>
                        </div>
                        <p class="text-sm font-medium opacity-90 mb-1">Won Deals</p>
                        <p class="text-3xl font-bold mb-1">{{ metrics.won_deals || 0 }}</p>
                        <p class="text-xs opacity-80">
                            O'rtacha: {{ formatPrice(metrics.avg_deal_size || 0) }}
                        </p>
                    </div>

                    <!-- Pipeline Value -->
                    <div class="bg-gradient-to-br from-orange-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between mb-2">
                            <FunnelIcon class="w-8 h-8 opacity-80" />
                            <CalendarIcon class="w-6 h-6 opacity-80" />
                        </div>
                        <p class="text-sm font-medium opacity-90 mb-1">Pipeline Value</p>
                        <p class="text-3xl font-bold mb-1">{{ formatPrice(metrics.pipeline_value || 0) }}</p>
                        <p class="text-xs opacity-80">
                            Potensial daromad
                        </p>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Revenue Trends Chart -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Revenue Trends</h3>
                            <Link
                                :href="route('business.analytics.revenue')"
                                class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium"
                            >
                                Batafsil →
                            </Link>
                        </div>
                        <div class="h-64">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>

                    <!-- Deals Won Chart -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Deals Won</h3>
                            <span class="text-sm text-gray-500 dark:text-gray-400">So'nggi 30 kun</span>
                        </div>
                        <div class="h-64">
                            <canvas id="dealsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top Performers -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-8">
                    <div class="flex items-center gap-3 mb-6">
                        <TrophyIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Top Performers</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Top Ideal Mijoz -->
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">TOP IDEAL MIJOZ</p>
                            <div v-if="top_performers.top_dream_buyer">
                                <p class="font-bold text-gray-900 dark:text-gray-100 mb-2">{{ top_performers.top_dream_buyer.dream_buyer_name }}</p>
                                <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <p>Leads: {{ top_performers.top_dream_buyer.total_leads }}</p>
                                    <p>Won: {{ top_performers.top_dream_buyer.won_leads }}</p>
                                    <p class="text-green-600 dark:text-green-400 font-semibold">
                                        CR: {{ formatPercent(top_performers.top_dream_buyer.conversion_rate) }}%
                                    </p>
                                    <p class="text-blue-600 dark:text-blue-400 font-semibold">
                                        {{ formatPrice(top_performers.top_dream_buyer.total_revenue) }}
                                    </p>
                                </div>
                            </div>
                            <p v-else class="text-sm text-gray-400 dark:text-gray-500">Ma'lumot yo'q</p>
                        </div>

                        <!-- Top Offer -->
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">TOP OFFER</p>
                            <div v-if="top_performers.top_offer">
                                <p class="font-bold text-gray-900 dark:text-gray-100 mb-2">{{ top_performers.top_offer.offer_name }}</p>
                                <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <p>Value Score: {{ top_performers.top_offer.value_score }}</p>
                                    <p>Won: {{ top_performers.top_offer.won_leads }}</p>
                                    <p class="text-green-600 dark:text-green-400 font-semibold">
                                        CR: {{ formatPercent(top_performers.top_offer.conversion_rate) }}%
                                    </p>
                                    <p class="text-blue-600 dark:text-blue-400 font-semibold">
                                        {{ formatPrice(top_performers.top_offer.total_revenue) }}
                                    </p>
                                </div>
                            </div>
                            <p v-else class="text-sm text-gray-400 dark:text-gray-500">Ma'lumot yo'q</p>
                        </div>

                        <!-- Top Source -->
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">TOP SOURCE</p>
                            <div v-if="top_performers.top_source">
                                <p class="font-bold text-gray-900 dark:text-gray-100 mb-2">{{ top_performers.top_source.source_name }}</p>
                                <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <p>Type: {{ top_performers.top_source.channel_type }}</p>
                                    <p>Won: {{ top_performers.top_source.won_leads }}</p>
                                    <p class="text-green-600 dark:text-green-400 font-semibold">
                                        ROI: {{ formatPercent(top_performers.top_source.roi) }}%
                                    </p>
                                    <p class="text-blue-600 dark:text-blue-400 font-semibold">
                                        {{ formatPrice(top_performers.top_source.total_revenue) }}
                                    </p>
                                </div>
                            </div>
                            <p v-else class="text-sm text-gray-400 dark:text-gray-500">Ma'lumot yo'q</p>
                        </div>
                    </div>
                </div>

                <!-- Funnel Overview -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <FunnelIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Conversion Funnel Overview</h3>
                        </div>
                        <Link
                            :href="route('business.analytics.funnel')"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium"
                        >
                            Batafsil ko'rish →
                        </Link>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ funnel_summary.total_leads || 0 }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Total Leads</p>
                        </div>
                        <div class="text-center p-4 bg-green-50 dark:bg-green-900/30 rounded-lg">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ funnel_summary.won_leads || 0 }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Won</p>
                        </div>
                        <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ funnel_summary.active_leads || 0 }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Active</p>
                        </div>
                        <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ formatPercent(funnel_summary.overall_conversion_rate) }}%</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Conversion Rate</p>
                        </div>
                    </div>
                </div>
            </div>
    </BusinessLayout>
</template>

<script>
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

export default {
    mounted() {
        // Initialize charts after component is mounted
        this.$nextTick(() => {
            this.initializeCharts();
        });
    },
    methods: {
        initializeCharts() {
            // Revenue Chart
            if (this.revenueChartData) {
                const revenueCtx = document.getElementById('revenueChart');
                if (revenueCtx) {
                    new Chart(revenueCtx, {
                        type: 'line',
                        data: this.revenueChartData,
                        options: this.chartOptions,
                    });
                }
            }

            // Deals Chart
            if (this.dealCountChartData) {
                const dealsCtx = document.getElementById('dealsChart');
                if (dealsCtx) {
                    new Chart(dealsCtx, {
                        type: 'bar',
                        data: this.dealCountChartData,
                        options: this.chartOptions,
                    });
                }
            }
        },
    },
};
</script>
