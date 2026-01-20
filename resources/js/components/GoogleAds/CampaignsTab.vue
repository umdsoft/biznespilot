<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    businessId: String,
    hasIntegration: Boolean,
});

const emit = defineEmits(['openCampaignModal', 'refresh', 'campaignSelected']);

// State
const loading = ref(false);
const syncing = ref(false);
const campaigns = ref([]);
const summary = ref({
    total_campaigns: 0,
    active_campaigns: 0,
    total_spend: 0,
    total_impressions: 0,
    total_clicks: 0,
    avg_ctr: 0,
    avg_cpc: 0,
});
const pagination = ref(null);
const filters = ref({
    status: '',
    channel_type: '',
    search: '',
    sort: 'total_cost',
    direction: 'desc',
});
const filterOptions = ref({
    statuses: [],
    channel_types: [],
    sort_options: [],
});

// Formatting helpers
const formatNumber = (num) => {
    if (!num) return '0';
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toLocaleString();
};

const formatCurrency = (num) => {
    if (!num) return "0 " + t('common.currency');
    return new Intl.NumberFormat('uz-UZ').format(num) + " " + t('common.currency');
};

const formatPercent = (num) => {
    if (!num) return '0%';
    return Number(num).toFixed(2) + '%';
};

// Load campaigns
const loadCampaigns = async (page = 1) => {
    if (!props.hasIntegration) return;

    loading.value = true;
    try {
        const params = new URLSearchParams({
            page,
            ...filters.value,
        });
        const response = await axios.get(`/business/api/google-ads-campaigns?${params}`);
        campaigns.value = response.data.campaigns || [];
        summary.value = response.data.summary || summary.value;
        pagination.value = response.data.pagination;
    } catch (error) {
        console.error('Error loading campaigns:', error);
    } finally {
        loading.value = false;
    }
};

// Load filter options
const loadFilters = async () => {
    try {
        const response = await axios.get('/business/api/google-ads-campaigns/filters');
        filterOptions.value = response.data;
    } catch (error) {
        console.error('Error loading filters:', error);
    }
};

// Sync campaigns
const syncCampaigns = async () => {
    syncing.value = true;
    try {
        const response = await axios.post('/business/api/google-ads-campaigns/sync');
        if (response.data.success) {
            await loadCampaigns();
            emit('refresh');
        }
    } catch (error) {
        console.error('Error syncing campaigns:', error);
    } finally {
        syncing.value = false;
    }
};

// Toggle campaign status
const toggleStatus = async (campaign) => {
    const newStatus = campaign.status === 'ENABLED' ? 'PAUSED' : 'ENABLED';
    try {
        await axios.patch(`/business/api/google-ads-campaigns/${campaign.id}/status`, {
            status: newStatus,
        });
        campaign.status = newStatus;
    } catch (error) {
        console.error('Error updating status:', error);
    }
};

// Watch filters
watch(filters, () => {
    loadCampaigns();
}, { deep: true });

// Initialize
onMounted(() => {
    loadFilters();
    loadCampaigns();
});

// Expose methods
defineExpose({ loadCampaigns, syncCampaigns });
</script>

<template>
    <div class="space-y-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ t('googleads.total') }}</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ summary.total_campaigns }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ t('googleads.active') }}</p>
                <p class="text-lg font-bold text-green-500">{{ summary.active_campaigns }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ t('googleads.spend') }}</p>
                <p class="text-lg font-bold text-orange-500">{{ formatCurrency(summary.total_spend) }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ t('googleads.impressions') }}</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatNumber(summary.total_impressions) }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ t('googleads.clicks') }}</p>
                <p class="text-lg font-bold text-blue-500">{{ formatNumber(summary.total_clicks) }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ t('googleads.conversions') }}</p>
                <p class="text-lg font-bold text-green-500">{{ formatNumber(summary.total_conversions) }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ t('googleads.ctr') }}</p>
                <p class="text-lg font-bold text-purple-500">{{ formatPercent(summary.avg_ctr) }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ t('googleads.cpc') }}</p>
                <p class="text-lg font-bold text-cyan-500">{{ formatCurrency(summary.avg_cpc) }}</p>
            </div>
        </div>

        <!-- Actions Bar -->
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <!-- Search -->
                <div class="relative">
                    <input
                        v-model="filters.search"
                        type="text"
                        :placeholder="t('common.search') + '...'"
                        class="pl-9 pr-4 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white"
                    >
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Status Filter -->
                <select
                    v-model="filters.status"
                    class="px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:text-white"
                >
                    <option value="">{{ t('googleads.all_statuses') }}</option>
                    <option v-for="status in filterOptions.statuses" :key="status.value" :value="status.value">
                        {{ status.label }}
                    </option>
                </select>

                <!-- Channel Type Filter -->
                <select
                    v-model="filters.channel_type"
                    class="px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:text-white"
                >
                    <option value="">{{ t('googleads.all_types') }}</option>
                    <option v-for="type in filterOptions.channel_types" :key="type.value" :value="type.value">
                        {{ type.label }}
                    </option>
                </select>

                <!-- Sort -->
                <select
                    v-model="filters.sort"
                    class="px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:text-white"
                >
                    <option v-for="option in filterOptions.sort_options" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </div>

            <div class="flex items-center gap-3">
                <!-- Sync Button -->
                <button
                    @click="syncCampaigns"
                    :disabled="syncing"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors disabled:opacity-50"
                >
                    <svg
                        class="w-4 h-4 mr-2"
                        :class="{ 'animate-spin': syncing }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ syncing ? t('googleads.syncing') : t('googleads.sync') }}
                </button>

                <!-- Create Campaign Button -->
                <button
                    @click="$emit('openCampaignModal')"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ t('googleads.create_campaign') }}
                </button>
            </div>
        </div>

        <!-- Campaigns List -->
        <div class="space-y-3">
            <!-- Loading State -->
            <div v-if="loading" class="text-center py-12">
                <svg class="w-8 h-8 text-blue-500 animate-spin mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">{{ t('common.loading') }}</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="campaigns.length === 0" class="text-center py-12">
                <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <p class="text-gray-500 dark:text-gray-400 mb-4">{{ t('googleads.no_campaigns') }}</p>
                <button
                    @click="syncCampaigns"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ t('googleads.sync_data') }}
                </button>
            </div>

            <!-- Campaign Cards -->
            <template v-else>
                <div
                    v-for="campaign in campaigns"
                    :key="campaign.id"
                    @click="$emit('campaignSelected', campaign)"
                    class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                >
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ campaign.name }}</h4>
                            <span
                                class="px-2 py-0.5 text-xs font-medium rounded-full"
                                :class="{
                                    'bg-green-500/20 text-green-500': campaign.status === 'ENABLED',
                                    'bg-yellow-500/20 text-yellow-600': campaign.status === 'PAUSED',
                                    'bg-red-500/20 text-red-500': campaign.status === 'REMOVED',
                                }"
                            >
                                {{ campaign.status === 'ENABLED' ? t('googleads.status_active') : campaign.status === 'PAUSED' ? t('googleads.status_paused') : t('googleads.status_removed') }}
                            </span>
                            <span class="px-2 py-0.5 text-xs font-medium bg-blue-500/20 text-blue-500 rounded-full">
                                {{ campaign.advertising_channel_type }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Toggle Status -->
                            <button
                                @click.stop="toggleStatus(campaign)"
                                class="p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                                :title="campaign.status === 'ENABLED' ? t('googleads.pause') : t('googleads.activate')"
                            >
                                <svg v-if="campaign.status === 'ENABLED'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="grid grid-cols-3 sm:grid-cols-6 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('googleads.impressions') }}</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatNumber(campaign.total_impressions) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('googleads.clicks') }}</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatNumber(campaign.total_clicks) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('googleads.ctr') }}</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatPercent(campaign.avg_ctr) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('googleads.spend') }}</p>
                            <p class="text-sm font-semibold text-orange-500">{{ formatCurrency(campaign.total_cost) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('googleads.conversions') }}</p>
                            <p class="text-sm font-semibold text-green-500">{{ formatNumber(campaign.total_conversions) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('googleads.budget') }}</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatCurrency(campaign.daily_budget) }}/{{ t('googleads.day') }}</p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-center gap-2">
            <button
                @click="loadCampaigns(pagination.current_page - 1)"
                :disabled="pagination.current_page === 1"
                class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg disabled:opacity-50"
            >
                {{ t('common.previous') }}
            </button>
            <span class="text-sm text-gray-600 dark:text-gray-400">
                {{ pagination.current_page }} / {{ pagination.last_page }}
            </span>
            <button
                @click="loadCampaigns(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
                class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg disabled:opacity-50"
            >
                {{ t('common.next') }}
            </button>
        </div>
    </div>
</template>
