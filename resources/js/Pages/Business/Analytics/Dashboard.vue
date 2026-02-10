<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { useI18n } from '@/i18n';
import axios from 'axios';
import {
    ChartBarIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    CurrencyDollarIcon,
    UserGroupIcon,
    ShoppingBagIcon,
    FunnelIcon,
    TrophyIcon,
    BanknotesIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    metrics: { type: Object, default: () => ({}) },
    revenue_trends: Object,
    top_performers: { type: Object, default: () => ({}) },
    funnel_summary: { type: Object, default: () => ({}) },
    filters: Object,
    lazyLoad: { type: Boolean, default: false },
});

const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const isLoading = ref(false);

// Loaded data from API
const loadedData = ref({
    metrics: null,
    top_performers: null,
    funnel_summary: null,
});

// Computed with fallbacks
const metrics = computed(() => loadedData.value.metrics || props.metrics || {});
const topPerformers = computed(() => loadedData.value.top_performers || props.top_performers || {});
const funnelSummary = computed(() => loadedData.value.funnel_summary || props.funnel_summary || {});

// Format price
const formatPrice = (price) => {
    if (!price) return '0 so\'m';
    return new Intl.NumberFormat('uz-UZ').format(price) + ' so\'m';
};

// Format percentage
const formatPercent = (value) => {
    return value?.toFixed(1) || '0';
};

// Trend color
const getTrendColor = (value) => {
    if (value > 0) return 'text-green-600 dark:text-green-400';
    if (value < 0) return 'text-red-600 dark:text-red-400';
    return 'text-gray-500';
};

// Fetch data lazily
const fetchData = async () => {
    if (!props.lazyLoad) return;
    isLoading.value = true;
    try {
        const params = {};
        if (dateFrom.value) params.date_from = dateFrom.value;
        if (dateTo.value) params.date_to = dateTo.value;
        const response = await axios.get('/business/analytics/api/initial', { params });
        if (response.data) {
            loadedData.value = {
                metrics: response.data.metrics,
                top_performers: response.data.top_performers,
                funnel_summary: response.data.funnel_summary,
            };
        }
    } catch (error) {
        console.error('Analytics data loading error:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchData();
});

const applyFilters = () => {
    // Re-fetch with filters
    fetchData();
};

const clearFilters = () => {
    dateFrom.value = '';
    dateTo.value = '';
    fetchData();
};
</script>

<template>
    <BusinessLayout :title="t('analytics.sales_analytics')">
        <Head :title="t('analytics.dashboard')" />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-3">
                    <ChartBarIcon class="w-7 h-7 text-blue-600 dark:text-blue-400" />
                    {{ t('analytics.sales_analytics') }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ t('analytics.track_sales_conversion') }}
                </p>
            </div>

            <!-- Quick Links -->
            <div class="flex gap-2">
                <Link
                    href="/business/analytics/funnel"
                    class="inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-all gap-2"
                >
                    <FunnelIcon class="w-4 h-4" />
                    {{ t('analytics.funnel') }}
                </Link>
                <Link
                    href="/business/analytics/performance"
                    class="inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-all gap-2"
                >
                    <TrophyIcon class="w-4 h-4" />
                    {{ t('analytics.performance') }}
                </Link>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6">
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">
                        {{ t('reports.filter.start_date') }}
                    </label>
                    <input
                        v-model="dateFrom"
                        type="date"
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">
                        {{ t('reports.filter.end_date') }}
                    </label>
                    <input
                        v-model="dateTo"
                        type="date"
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>
                <button
                    @click="applyFilters"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
                >
                    {{ t('common.filter') }}
                </button>
                <button
                    @click="clearFilters"
                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors"
                >
                    {{ t('reports.filter.reset') }}
                </button>
            </div>
        </div>

        <!-- Key Metrics Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Revenue -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <BanknotesIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('analytics.total_revenue') }}</span>
                </div>
                <p v-if="isLoading" class="text-2xl font-bold text-gray-900 dark:text-gray-100 animate-pulse">---</p>
                <p v-else class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatPrice(metrics.total_revenue) }}</p>
                <p v-if="!isLoading && metrics.revenue_growth" class="text-xs mt-1" :class="getTrendColor(metrics.revenue_growth)">
                    <component :is="metrics.revenue_growth > 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" class="w-3.5 h-3.5 inline" />
                    {{ metrics.revenue_growth > 0 ? '+' : '' }}{{ formatPercent(metrics.revenue_growth) }}% {{ t('analytics.vs_previous_period') }}
                </p>
            </div>

            <!-- Total Leads -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                        <UserGroupIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('dashboard.total_leads') }}</span>
                </div>
                <p v-if="isLoading" class="text-2xl font-bold text-gray-900 dark:text-gray-100 animate-pulse">---</p>
                <p v-else class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ metrics.total_leads || 0 }}</p>
                <p v-if="!isLoading" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ metrics.new_leads || 0 }} {{ t('analytics.new') }} &middot; {{ metrics.active_pipeline_deals || 0 }} {{ t('analytics.active_pipeline') }}
                </p>
            </div>

            <!-- Won Deals -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                        <ShoppingBagIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('analytics.won_deals') }}</span>
                </div>
                <p v-if="isLoading" class="text-2xl font-bold text-gray-900 dark:text-gray-100 animate-pulse">---</p>
                <p v-else class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ metrics.won_deals || 0 }}</p>
                <p v-if="!isLoading" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ formatPercent(metrics.conversion_rate) }}% CR &middot; {{ t('analytics.average') }}: {{ formatPrice(metrics.avg_deal_size) }}
                </p>
            </div>

            <!-- Pipeline Value -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                        <FunnelIcon class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('analytics.pipeline_value') }}</span>
                </div>
                <p v-if="isLoading" class="text-2xl font-bold text-gray-900 dark:text-gray-100 animate-pulse">---</p>
                <p v-else class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatPrice(metrics.pipeline_value) }}</p>
                <p v-if="!isLoading" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ t('analytics.potential_revenue') }}
                </p>
            </div>
        </div>

        <!-- Top Performers -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <TrophyIcon class="w-5 h-5 text-yellow-500" />
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ t('analytics.top_performers') }}</h3>
            </div>
            <div class="p-5">
                <div v-if="isLoading" class="text-center py-6 text-gray-400 animate-pulse">{{ t('dashboard.loading') }}</div>
                <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Top Ideal Customer -->
                    <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">{{ t('analytics.top_ideal_customer') }}</p>
                        <div v-if="topPerformers.top_dream_buyer">
                            <p class="font-bold text-gray-900 dark:text-gray-100 mb-2">{{ topPerformers.top_dream_buyer.dream_buyer_name }}</p>
                            <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                <p>Leads: {{ topPerformers.top_dream_buyer.total_leads }}</p>
                                <p class="text-green-600 dark:text-green-400 font-semibold">
                                    CR: {{ formatPercent(topPerformers.top_dream_buyer.conversion_rate) }}%
                                </p>
                                <p class="text-blue-600 dark:text-blue-400 font-semibold">
                                    {{ formatPrice(topPerformers.top_dream_buyer.total_revenue) }}
                                </p>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400 dark:text-gray-500">{{ t('reports.no_data') }}</p>
                    </div>

                    <!-- Top Offer -->
                    <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">{{ t('analytics.top_offer') }}</p>
                        <div v-if="topPerformers.top_offer">
                            <p class="font-bold text-gray-900 dark:text-gray-100 mb-2">{{ topPerformers.top_offer.offer_name }}</p>
                            <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                <p>Value Score: {{ topPerformers.top_offer.value_score }}</p>
                                <p class="text-green-600 dark:text-green-400 font-semibold">
                                    CR: {{ formatPercent(topPerformers.top_offer.conversion_rate) }}%
                                </p>
                                <p class="text-blue-600 dark:text-blue-400 font-semibold">
                                    {{ formatPrice(topPerformers.top_offer.total_revenue) }}
                                </p>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400 dark:text-gray-500">{{ t('reports.no_data') }}</p>
                    </div>

                    <!-- Top Source -->
                    <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">{{ t('analytics.top_source') }}</p>
                        <div v-if="topPerformers.top_source">
                            <p class="font-bold text-gray-900 dark:text-gray-100 mb-2">{{ topPerformers.top_source.source_name }}</p>
                            <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                <p>Type: {{ topPerformers.top_source.channel_type }}</p>
                                <p class="text-green-600 dark:text-green-400 font-semibold">
                                    ROI: {{ formatPercent(topPerformers.top_source.roi) }}%
                                </p>
                                <p class="text-blue-600 dark:text-blue-400 font-semibold">
                                    {{ formatPrice(topPerformers.top_source.total_revenue) }}
                                </p>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400 dark:text-gray-500">{{ t('reports.no_data') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Funnel Overview -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <FunnelIcon class="w-5 h-5 text-blue-500" />
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ t('analytics.conversion_funnel_overview') }}</h3>
                </div>
                <Link
                    href="/business/analytics/funnel"
                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium"
                >
                    {{ t('common.view') }} &rarr;
                </Link>
            </div>
            <div class="p-5">
                <div v-if="isLoading" class="text-center py-6 text-gray-400 animate-pulse">{{ t('dashboard.loading') }}</div>
                <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ funnelSummary.total_leads || 0 }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ t('analytics.total_leads_label') }}</p>
                    </div>
                    <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ funnelSummary.won_leads || 0 }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ t('analytics.won_label') }}</p>
                    </div>
                    <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ funnelSummary.active_leads || 0 }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ t('analytics.active_label') }}</p>
                    </div>
                    <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ formatPercent(funnelSummary.overall_conversion_rate) }}%</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ t('analytics.conversion_rate_label') }}</p>
                    </div>
                </div>
            </div>
        </div>

    </BusinessLayout>
</template>
