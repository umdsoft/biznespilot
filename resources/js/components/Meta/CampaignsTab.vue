<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    businessId: String,
    currency: {
        type: String,
        default: 'USD'
    }
});

const emit = defineEmits(['sync-started', 'sync-completed', 'error']);

// State
const loading = ref(false);
const syncing = ref(false);
const campaigns = ref([]);
const summary = ref({
    total_campaigns: 0,
    active_campaigns: 0,
    total_spend: 0,
    total_impressions: 0,
    total_reach: 0,
    total_clicks: 0,
    avg_ctr: 0,
    avg_cpc: 0
});
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
    from: null,
    to: null
});

// Filters
const filters = ref({
    status: '',
    objective: '',
    search: '',
    date_from: '',
    date_to: '',
    sort: 'total_spend',
    direction: 'desc'
});

// Filter options
const filterOptions = ref({
    statuses: [],
    objectives: [],
    sort_options: []
});

// Sort options
const sortOptions = [
    { value: 'total_spend', label: "Sarflangan (ko'p → kam)" },
    { value: 'total_impressions', label: "Ko'rishlar" },
    { value: 'total_clicks', label: 'Kliklar' },
    { value: 'avg_ctr', label: 'CTR' },
    { value: 'avg_cpc', label: 'CPC' },
    { value: 'name', label: 'Nomi (A-Z)' },
    { value: 'created_time', label: 'Yaratilgan sana' }
];

// Format helpers
const formatNumber = (num) => new Intl.NumberFormat('uz-UZ').format(Number(num) || 0);
const formatCurrency = (amount, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(Number(amount) || 0);
};
const formatPercent = (value) => (Number(value) || 0).toFixed(2) + '%';

const formatStatus = (status) => {
    const statuses = {
        'ACTIVE': 'Faol',
        'PAUSED': 'Pauza',
        'DELETED': "O'chirilgan",
        'ARCHIVED': 'Arxivlangan',
        'IN_PROCESS': 'Jarayonda',
        'WITH_ISSUES': 'Muammoli',
        'CAMPAIGN_PAUSED': 'Kampaniya pauzada',
        'ADSET_PAUSED': 'AdSet pauzada',
        'PENDING_REVIEW': "Ko'rib chiqilmoqda",
        'DISAPPROVED': 'Rad etilgan'
    };
    return statuses[status] || status;
};

const formatObjective = (objective) => {
    const objectives = {
        'OUTCOME_AWARENESS': 'Xabardorlik',
        'OUTCOME_ENGAGEMENT': 'Engagement',
        'OUTCOME_LEADS': 'Lidlar',
        'OUTCOME_SALES': 'Sotuvlar',
        'OUTCOME_TRAFFIC': 'Trafik',
        'OUTCOME_APP_PROMOTION': 'App Promotion',
        'LINK_CLICKS': 'Link Clicks',
        'POST_ENGAGEMENT': 'Post Engagement',
        'PAGE_LIKES': 'Page Likes',
        'CONVERSIONS': 'Konversiyalar',
        'MESSAGES': 'Xabarlar',
        'VIDEO_VIEWS': 'Video Views'
    };
    return objectives[objective] || objective || "Noma'lum";
};

const getStatusColor = (status) => {
    switch (status) {
        case 'ACTIVE':
            return 'bg-green-100 text-green-800';
        case 'PAUSED':
        case 'CAMPAIGN_PAUSED':
        case 'ADSET_PAUSED':
            return 'bg-yellow-100 text-yellow-800';
        case 'DELETED':
        case 'ARCHIVED':
            return 'bg-gray-100 text-gray-800';
        case 'WITH_ISSUES':
        case 'DISAPPROVED':
            return 'bg-red-100 text-red-800';
        case 'PENDING_REVIEW':
        case 'IN_PROCESS':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

// Get result label based on objective
const getResultLabel = (objective) => {
    return {
        'OUTCOME_LEADS': 'Lid',
        'OUTCOME_SALES': 'Sotish',
        'OUTCOME_TRAFFIC': 'Klik',
        'OUTCOME_ENGAGEMENT': 'Engagement',
        'OUTCOME_AWARENESS': 'Reach',
        'OUTCOME_APP_PROMOTION': 'Install',
        'LINK_CLICKS': 'Klik',
        'POST_ENGAGEMENT': 'Engagement',
        'PAGE_LIKES': 'Like',
        'CONVERSIONS': 'Konversiya',
        'MESSAGES': 'Xabar',
        'VIDEO_VIEWS': 'Ko\'rish',
    }[objective] || 'Natija';
};

// Open campaign detail page
const openCampaignDetail = (campaign) => {
    console.log('Opening campaign:', campaign.id);
    window.location.href = `/business/meta-campaigns/${campaign.id}`;
};

// Load campaigns from API
const loadCampaigns = async (page = 1) => {
    if (!props.businessId) {
        console.warn('CampaignsTab: businessId is empty, skipping load');
        return;
    }

    console.log('CampaignsTab: Loading campaigns for business:', props.businessId);
    loading.value = true;
    try {
        const params = {
            business_id: props.businessId,
            page,
            per_page: pagination.value.per_page,
            ...filters.value
        };

        // Remove empty filters
        Object.keys(params).forEach(key => {
            if (params[key] === '' || params[key] === null) {
                delete params[key];
            }
        });

        console.log('CampaignsTab: Request params:', params);
        const response = await axios.get('/business/api/meta-campaigns', { params });
        console.log('CampaignsTab: Response:', response.data);

        if (response.data.success) {
            campaigns.value = response.data.data;
            summary.value = response.data.summary;
            pagination.value = response.data.pagination;
            console.log('CampaignsTab: Loaded', campaigns.value.length, 'campaigns');
        } else {
            console.warn('CampaignsTab: API returned success=false:', response.data.message);
            emit('error', response.data.message || "Ma'lumotlarni yuklashda xatolik");
        }
    } catch (error) {
        console.error('CampaignsTab: Error loading campaigns:', error);
        emit('error', error.response?.data?.message || "Ma'lumotlarni yuklashda xatolik");
    } finally {
        loading.value = false;
    }
};

// Load filter options
const loadFilterOptions = async () => {
    try {
        const response = await axios.get('/business/api/meta-campaigns/filters', {
            params: { business_id: props.businessId }
        });
        if (response.data.success) {
            filterOptions.value = response.data.data;
        }
    } catch (error) {
        console.error('Error loading filter options:', error);
    }
};

// Sync campaigns
const syncCampaigns = async () => {
    syncing.value = true;
    emit('sync-started');
    try {
        const response = await axios.post('/business/api/meta-campaigns/sync', {
            business_id: props.businessId
        });

        if (response.data.success) {
            emit('sync-completed', response.data);
            // Reload campaigns after sync
            await loadCampaigns(1);
            await loadFilterOptions();
        } else {
            emit('error', response.data.message);
        }
    } catch (error) {
        console.error('Error syncing campaigns:', error);
        emit('error', error.response?.data?.message || 'Sinxronlashda xatolik');
    } finally {
        syncing.value = false;
    }
};

// Page change handler
const goToPage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        loadCampaigns(page);
    }
};

// Reset filters
const resetFilters = () => {
    filters.value = {
        status: '',
        objective: '',
        search: '',
        date_from: '',
        date_to: '',
        sort: 'total_spend',
        direction: 'desc'
    };
    loadCampaigns(1);
};

// Watch for businessId changes - reload when it becomes available or changes
watch(() => props.businessId, (newVal) => {
    if (newVal) {
        loadCampaigns(1);
        loadFilterOptions();
    }
}, { immediate: true });

// Watch for filter changes with debounce
let searchTimeout = null;
watch(() => filters.value.search, (newVal) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadCampaigns(1);
    }, 300);
});

watch([
    () => filters.value.status,
    () => filters.value.objective,
    () => filters.value.sort,
    () => filters.value.direction
], () => {
    loadCampaigns(1);
});

// Generate pagination pages
const paginationPages = computed(() => {
    const pages = [];
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;

    if (last <= 7) {
        for (let i = 1; i <= last; i++) {
            pages.push(i);
        }
    } else {
        pages.push(1);
        if (current > 3) {
            pages.push('...');
        }
        for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
            pages.push(i);
        }
        if (current < last - 2) {
            pages.push('...');
        }
        pages.push(last);
    }

    return pages;
});

// Note: Initial load handled by watch with immediate: true
</script>

<template>
    <div class="space-y-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <p class="text-xs font-medium text-gray-500 uppercase">Jami</p>
                <p class="text-xl font-bold text-gray-900">{{ summary.total_campaigns }}</p>
                <p class="text-xs text-green-600">{{ summary.active_campaigns }} faol</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <p class="text-xs font-medium text-gray-500 uppercase">Sarflangan</p>
                <p class="text-xl font-bold text-gray-900">{{ formatCurrency(summary.total_spend, currency) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <p class="text-xs font-medium text-gray-500 uppercase">Ko'rishlar</p>
                <p class="text-xl font-bold text-gray-900">{{ formatNumber(summary.total_impressions) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <p class="text-xs font-medium text-gray-500 uppercase">Qamrov</p>
                <p class="text-xl font-bold text-gray-900">{{ formatNumber(summary.total_reach) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <p class="text-xs font-medium text-gray-500 uppercase">Kliklar</p>
                <p class="text-xl font-bold text-gray-900">{{ formatNumber(summary.total_clicks) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <p class="text-xs font-medium text-gray-500 uppercase">CTR</p>
                <p class="text-xl font-bold text-gray-900">{{ formatPercent(summary.avg_ctr) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <p class="text-xs font-medium text-gray-500 uppercase">CPC</p>
                <p class="text-xl font-bold text-gray-900">{{ formatCurrency(summary.avg_cpc, currency) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200 flex items-center justify-center">
                <button @click="syncCampaigns" :disabled="syncing"
                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center justify-center gap-2 text-sm font-medium">
                    <svg :class="{ 'animate-spin': syncing }" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ syncing ? 'Sync...' : 'Sync' }}
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex flex-wrap items-center gap-3">
                <!-- Status Filter -->
                <div class="flex-shrink-0">
                    <select v-model="filters.status"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Barcha statuslar</option>
                        <option v-for="status in filterOptions.statuses" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>
                </div>

                <!-- Objective Filter -->
                <div class="flex-shrink-0">
                    <select v-model="filters.objective"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Barcha maqsadlar</option>
                        <option v-for="obj in filterOptions.objectives" :key="obj.value" :value="obj.value">
                            {{ obj.label }}
                        </option>
                    </select>
                </div>

                <!-- Sort -->
                <div class="flex-shrink-0">
                    <select v-model="filters.sort"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </div>

                <!-- Direction Toggle -->
                <button @click="filters.direction = filters.direction === 'desc' ? 'asc' : 'desc'"
                    class="p-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    <svg v-if="filters.direction === 'desc'" class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    <svg v-else class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                </button>

                <!-- Search -->
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" v-model="filters.search" placeholder="Kampaniya nomini qidirish..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Reset -->
                <button @click="resetFilters" class="px-3 py-2 text-gray-600 hover:text-gray-900 text-sm font-medium">
                    Tozalash
                </button>
            </div>
        </div>

        <!-- Campaigns Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <!-- Loading Overlay -->
            <div v-if="loading" class="p-12 flex items-center justify-center">
                <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>

            <!-- Table -->
            <div v-else-if="campaigns.length > 0" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kampaniya</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Maqsad</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sarflangan</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ko'rishlar</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Kliklar</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">CTR</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">CPC</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Natija</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="campaign in campaigns" :key="campaign.id" class="hover:bg-gray-50 transition-colors cursor-pointer" @click="openCampaignDetail(campaign)">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ campaign.name }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ campaign.meta_campaign_id }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="getStatusColor(campaign.effective_status)" class="px-2.5 py-1 text-xs font-semibold rounded-full">
                                    {{ formatStatus(campaign.effective_status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ formatObjective(campaign.objective) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                {{ formatCurrency(campaign.total_spend, currency) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-right">
                                {{ formatNumber(campaign.total_impressions) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-right">
                                {{ formatNumber(campaign.total_clicks) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                {{ formatPercent(campaign.avg_ctr) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-right">
                                {{ formatCurrency(campaign.avg_cpc, currency) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div v-if="campaign.total_conversions > 0" class="text-sm">
                                    <span class="font-bold text-green-600">{{ formatNumber(campaign.total_conversions) }}</span>
                                    <span class="text-gray-500 text-xs ml-1">{{ getResultLabel(campaign.objective) }}</span>
                                </div>
                                <span v-else class="text-gray-400 text-sm">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button class="text-blue-600 hover:text-blue-800 p-1" @click.stop="openCampaignDetail(campaign)">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div v-else class="p-12 text-center text-gray-500">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="text-lg font-medium mb-2">Kampaniyalar topilmadi</p>
                <p class="text-sm">Filterlarni o'zgartirib ko'ring yoki Sync tugmasini bosing</p>
            </div>

            <!-- Pagination -->
            <div v-if="campaigns.length > 0" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Ko'rsatilmoqda: {{ pagination.from }}-{{ pagination.to }} / {{ pagination.total }} ta
                </div>
                <div class="flex items-center gap-1">
                    <!-- Previous -->
                    <button @click="goToPage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="p-2 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <!-- Page Numbers -->
                    <template v-for="page in paginationPages" :key="page">
                        <span v-if="page === '...'" class="px-3 py-1 text-gray-500">...</span>
                        <button v-else @click="goToPage(page)"
                            :class="[
                                'px-3 py-1 rounded-lg text-sm font-medium transition-colors',
                                page === pagination.current_page
                                    ? 'bg-blue-600 text-white'
                                    : 'hover:bg-gray-100 text-gray-600'
                            ]">
                            {{ page }}
                        </button>
                    </template>

                    <!-- Next -->
                    <button @click="goToPage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="p-2 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
