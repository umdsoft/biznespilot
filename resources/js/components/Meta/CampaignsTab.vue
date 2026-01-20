<script setup>
import { ref, computed, watch, onUnmounted, onMounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AdCreatorWizard from './AdCreatorWizard.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

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
const togglingCampaigns = ref(new Set()); // Track campaigns being toggled
const canManage = ref(false);
const managementChecked = ref(false);

// Full Ad Creator Wizard
const showWizard = ref(false);

// Campaign creation modal (simple)
const showCreateModal = ref(false);
const creatingCampaign = ref(false);
const creationOptions = ref({
    objectives: [],
    pages: [],
    ad_account: null
});
const newCampaign = ref({
    name: '',
    objective: '',
    daily_budget: '',
    status: 'PAUSED'
});

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
    sort: 'created_time',
    direction: 'desc'
});

// Filter options
const filterOptions = ref({
    statuses: [],
    objectives: [],
    sort_options: []
});

// Sort options
const sortOptions = computed(() => [
    { value: 'created_time', label: t('meta.sort_created_time') },
    { value: 'total_spend', label: t('meta.sort_spend') },
    { value: 'total_impressions', label: t('meta.sort_impressions') },
    { value: 'total_clicks', label: t('meta.sort_clicks') },
    { value: 'avg_ctr', label: t('meta.sort_ctr') },
    { value: 'avg_cpc', label: t('meta.sort_cpc') },
    { value: 'name', label: t('meta.sort_name') }
]);

// Format helpers
const formatNumber = (num) => new Intl.NumberFormat('uz-UZ').format(Number(num) || 0);
const formatCurrency = (amount, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(Number(amount) || 0);
};
const formatPercent = (value) => (Number(value) || 0).toFixed(2) + '%';
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '-';
    return date.toLocaleDateString('uz-UZ', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const formatStatus = (status) => {
    const statuses = {
        'ACTIVE': t('meta.status_active'),
        'PAUSED': t('meta.status_paused'),
        'DELETED': t('meta.status_deleted'),
        'ARCHIVED': t('meta.status_archived'),
        'IN_PROCESS': t('meta.status_in_process'),
        'WITH_ISSUES': t('meta.status_with_issues'),
        'CAMPAIGN_PAUSED': t('meta.status_campaign_paused'),
        'ADSET_PAUSED': t('meta.status_adset_paused'),
        'PENDING_REVIEW': t('meta.status_pending_review'),
        'DISAPPROVED': t('meta.status_disapproved')
    };
    return statuses[status] || status;
};

const formatObjective = (objective) => {
    const objectives = {
        'OUTCOME_AWARENESS': t('meta.objective_awareness'),
        'OUTCOME_ENGAGEMENT': t('meta.objective_engagement'),
        'OUTCOME_LEADS': t('meta.objective_leads'),
        'OUTCOME_SALES': t('meta.objective_sales'),
        'OUTCOME_TRAFFIC': t('meta.objective_traffic'),
        'OUTCOME_APP_PROMOTION': t('meta.objective_app_promotion'),
        'LINK_CLICKS': t('meta.objective_link_clicks'),
        'POST_ENGAGEMENT': t('meta.objective_post_engagement'),
        'PAGE_LIKES': t('meta.objective_page_likes'),
        'CONVERSIONS': t('meta.objective_conversions'),
        'MESSAGES': t('meta.objective_messages'),
        'VIDEO_VIEWS': t('meta.objective_video_views')
    };
    return objectives[objective] || objective || t('meta.unknown');
};

const getStatusColor = (status) => {
    switch (status) {
        case 'ACTIVE':
            return 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400';
        case 'PAUSED':
        case 'CAMPAIGN_PAUSED':
        case 'ADSET_PAUSED':
            return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400';
        case 'DELETED':
        case 'ARCHIVED':
            return 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300';
        case 'WITH_ISSUES':
        case 'DISAPPROVED':
            return 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400';
        case 'PENDING_REVIEW':
        case 'IN_PROCESS':
            return 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400';
        default:
            return 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300';
    }
};

// Get result value based on objective
const getResultValue = (campaign) => {
    const objective = campaign.objective;
    switch (objective) {
        case 'OUTCOME_LEADS':
        case 'LEAD_GENERATION':
            return campaign.total_leads || 0;
        case 'OUTCOME_SALES':
        case 'CONVERSIONS':
        case 'PRODUCT_CATALOG_SALES':
            return campaign.total_purchases || campaign.total_conversions || 0;
        case 'MESSAGES':
            return campaign.total_messages || 0;
        case 'OUTCOME_TRAFFIC':
        case 'LINK_CLICKS':
            return campaign.total_link_clicks || 0;
        case 'VIDEO_VIEWS':
            return campaign.total_video_views || 0;
        case 'OUTCOME_ENGAGEMENT':
        case 'POST_ENGAGEMENT':
        case 'PAGE_LIKES':
            return campaign.total_clicks || 0;
        case 'OUTCOME_AWARENESS':
            return campaign.total_reach || 0;
        default:
            return campaign.total_conversions || campaign.total_leads || campaign.total_clicks || 0;
    }
};

// Get result label based on objective
const getResultLabel = (campaign) => {
    const objective = campaign.objective;
    const labels = {
        'OUTCOME_LEADS': t('meta.result_lead'),
        'LEAD_GENERATION': t('meta.result_lead'),
        'OUTCOME_SALES': t('meta.result_sale'),
        'CONVERSIONS': t('meta.result_conversion'),
        'PRODUCT_CATALOG_SALES': t('meta.result_sale'),
        'OUTCOME_TRAFFIC': t('meta.result_click'),
        'LINK_CLICKS': t('meta.result_click'),
        'OUTCOME_ENGAGEMENT': t('meta.result_engagement'),
        'POST_ENGAGEMENT': t('meta.result_engagement'),
        'PAGE_LIKES': t('meta.result_like'),
        'OUTCOME_AWARENESS': t('meta.result_reach'),
        'OUTCOME_APP_PROMOTION': t('meta.result_install'),
        'MESSAGES': t('meta.result_message'),
        'VIDEO_VIEWS': t('meta.result_view'),
    };
    return labels[objective] || t('meta.result');
};

// Get cost per result
const getCostPerResult = (campaign) => {
    const value = getResultValue(campaign);
    if (value <= 0) return 0;
    return campaign.total_spend / value;
};

// Open campaign detail page
const openCampaignDetail = (campaign) => {
    window.location.href = `/business/meta-campaigns/${campaign.id}`;
};

// Check management capabilities
const checkManagementCapabilities = async () => {
    try {
        const response = await axios.get('/integrations/meta/api/management-info', {
            params: { business_id: props.businessId }
        });
        if (response.data.success) {
            canManage.value = response.data.capabilities?.can_manage ?? false;
        }
    } catch (error) {
        console.error('Error checking management capabilities:', error);
        canManage.value = false;
    } finally {
        managementChecked.value = true;
    }
};

// Toggle campaign status
const toggleCampaignStatus = async (campaign, event) => {
    event.stopPropagation();

    if (!canManage.value) {
        emit('error', t('meta.no_manage_permission'));
        return;
    }

    const campaignId = campaign.meta_campaign_id;
    const currentStatus = campaign.effective_status;
    const newStatus = currentStatus === 'ACTIVE' ? 'PAUSED' : 'ACTIVE';

    // Add to toggling set
    togglingCampaigns.value.add(campaignId);

    try {
        const response = await axios.post('/integrations/meta/api/campaign/status', {
            business_id: props.businessId,
            campaign_id: campaignId,
            status: newStatus
        });

        if (response.data.success) {
            // Update local campaign state
            campaign.status = newStatus;
            campaign.effective_status = newStatus;

            // Update summary counts
            if (newStatus === 'ACTIVE') {
                summary.value.active_campaigns++;
            } else {
                summary.value.active_campaigns--;
            }
        } else {
            emit('error', response.data.message || t('meta.status_update_error'));
        }
    } catch (error) {
        console.error('Error toggling campaign status:', error);
        emit('error', error.response?.data?.message || t('meta.status_update_error'));
    } finally {
        togglingCampaigns.value.delete(campaignId);
    }
};

// Check if campaign can be toggled
const canToggleCampaign = (campaign) => {
    const toggleableStatuses = ['ACTIVE', 'PAUSED'];
    return canManage.value && toggleableStatuses.includes(campaign.effective_status);
};

// Check if campaign is currently being toggled
const isToggling = (campaign) => {
    return togglingCampaigns.value.has(campaign.meta_campaign_id);
};

// ==================== CAMPAIGN CREATION ====================

// Open create campaign modal
const openCreateModal = async () => {
    if (!canManage.value) {
        emit('error', t('meta.no_create_permission'));
        return;
    }

    // Load creation options
    try {
        const response = await axios.get('/integrations/meta/api/creation-options', {
            params: { business_id: props.businessId }
        });

        if (response.data.success) {
            creationOptions.value = {
                objectives: response.data.objectives || [],
                pages: response.data.pages || [],
                ad_account: response.data.ad_account
            };
            showCreateModal.value = true;
        } else {
            emit('error', response.data.message || t('meta.load_data_error'));
        }
    } catch (error) {
        console.error('Error loading creation options:', error);
        emit('error', error.response?.data?.message || t('meta.load_data_error'));
    }
};

// Close create modal
const closeCreateModal = () => {
    showCreateModal.value = false;
    newCampaign.value = {
        name: '',
        objective: '',
        daily_budget: '',
        status: 'PAUSED'
    };
};

// Create campaign
const createCampaign = async () => {
    if (!newCampaign.value.name || !newCampaign.value.objective) {
        emit('error', t('meta.enter_name_objective'));
        return;
    }

    creatingCampaign.value = true;

    try {
        const payload = {
            business_id: props.businessId,
            name: newCampaign.value.name,
            objective: newCampaign.value.objective,
            status: newCampaign.value.status,
            special_ad_categories: []
        };

        // Only add budget if provided
        if (newCampaign.value.daily_budget && parseFloat(newCampaign.value.daily_budget) > 0) {
            payload.daily_budget = parseFloat(newCampaign.value.daily_budget);
        }

        console.log('Creating campaign with payload:', payload);

        const response = await axios.post('/integrations/meta/api/campaign/create', payload);

        if (response.data.success) {
            closeCreateModal();
            // Reload campaigns
            await loadCampaigns(1);
            emit('sync-completed', { message: t('meta.campaign_created_success') });
        } else {
            console.error('Campaign creation failed:', response.data);
            emit('error', response.data.message || t('meta.campaign_create_error'));
        }
    } catch (error) {
        console.error('Error creating campaign:', error);
        const errorMessage = error.response?.data?.message
            || error.response?.data?.error
            || error.message
            || t('meta.campaign_create_error');
        emit('error', errorMessage);
    } finally {
        creatingCampaign.value = false;
    }
};

// Handle wizard completion
const handleWizardCreated = async (data) => {
    showWizard.value = false;
    emit('sync-completed', { message: t('meta.ad_created_success') });
    // Reload campaigns to show the new one
    await loadCampaigns(1);
};

// Get objective icon
const getObjectiveIcon = (iconName) => {
    const icons = {
        'eye': 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
        'cursor-click': 'M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122',
        'heart': 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
        'user-plus': 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
        'shopping-cart': 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
        'device-mobile': 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'
    };
    return icons[iconName] || icons['eye'];
};

// Load campaigns from API
const loadCampaigns = async (page = 1) => {
    if (!props.businessId) {
        return;
    }

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

        const response = await axios.get('/business/api/meta-campaigns', { params });

        if (response.data.success) {
            campaigns.value = response.data.data;
            summary.value = response.data.summary;
            pagination.value = response.data.pagination;
        } else {
            emit('error', response.data.message || t('meta.load_data_error'));
        }
    } catch (error) {
        emit('error', error.response?.data?.message || t('meta.load_data_error'));
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
        emit('error', error.response?.data?.message || t('meta.sync_error'));
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
        checkManagementCapabilities();
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

// Cleanup on unmount
onUnmounted(() => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
});
</script>

<template>
    <div class="space-y-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('meta.total') }}</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ summary.total_campaigns }}</p>
                <p class="text-xs text-green-600 dark:text-green-400">{{ summary.active_campaigns }} {{ t('meta.active') }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('meta.spent') }}</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(summary.total_spend, currency) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('meta.impressions') }}</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatNumber(summary.total_impressions) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('meta.reach') }}</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatNumber(summary.total_reach) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('meta.clicks') }}</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatNumber(summary.total_clicks) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('meta.ctr') }}</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatPercent(summary.avg_ctr) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('meta.cpc') }}</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(summary.avg_cpc, currency) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center gap-2">
                <button @click="syncCampaigns" :disabled="syncing"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center justify-center gap-2 text-sm font-medium">
                    <svg :class="{ 'animate-spin': syncing }" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ syncing ? t('meta.syncing') : t('meta.sync') }}
                </button>
                <!-- Full Ad Creator Page Link -->
                <Link v-if="canManage" href="/business/meta-campaigns/create"
                    class="px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg hover:from-green-700 hover:to-emerald-700 flex items-center justify-center gap-2 text-sm font-medium shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ t('meta.create_ad') }}
                </Link>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex flex-wrap items-center gap-3">
                <!-- Status Filter -->
                <div class="flex-shrink-0">
                    <select v-model="filters.status"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">{{ t('meta.all_statuses') }}</option>
                        <option v-for="status in filterOptions.statuses" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>
                </div>

                <!-- Objective Filter -->
                <div class="flex-shrink-0">
                    <select v-model="filters.objective"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">{{ t('meta.all_objectives') }}</option>
                        <option v-for="obj in filterOptions.objectives" :key="obj.value" :value="obj.value">
                            {{ obj.label }}
                        </option>
                    </select>
                </div>

                <!-- Sort -->
                <div class="flex-shrink-0">
                    <select v-model="filters.sort"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </div>

                <!-- Direction Toggle -->
                <button @click="filters.direction = filters.direction === 'desc' ? 'asc' : 'desc'"
                    class="p-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                    <svg v-if="filters.direction === 'desc'" class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    <svg v-else class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                </button>

                <!-- Search -->
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" v-model="filters.search" :placeholder="t('meta.search_campaign')"
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400">
                    </div>
                </div>

                <!-- Reset -->
                <button @click="resetFilters" class="px-3 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white text-sm font-medium">
                    {{ t('meta.clear') }}
                </button>
            </div>
        </div>

        <!-- Campaigns Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <!-- Loading Overlay -->
            <div v-if="loading" class="p-12 flex items-center justify-center">
                <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>

            <!-- Table -->
            <div v-else-if="campaigns.length > 0" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th v-if="canManage" class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-16">On/Off</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('meta.campaign') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('common.status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('meta.objective') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('meta.spent') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('meta.impressions') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('meta.clicks') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('meta.ctr') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('meta.cpc') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('meta.result') }}</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ t('meta.created') }}</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="campaign in campaigns" :key="campaign.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer" @click="openCampaignDetail(campaign)">
                            <!-- Toggle Switch Column -->
                            <td v-if="canManage" class="px-3 py-4 whitespace-nowrap text-center" @click.stop>
                                <div class="flex items-center justify-center">
                                    <!-- Toggle Switch -->
                                    <button
                                        v-if="canToggleCampaign(campaign)"
                                        @click="(e) => toggleCampaignStatus(campaign, e)"
                                        :disabled="isToggling(campaign)"
                                        :class="[
                                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                                            campaign.effective_status === 'ACTIVE' ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600',
                                            isToggling(campaign) ? 'opacity-50 cursor-not-allowed' : ''
                                        ]"
                                        role="switch"
                                        :aria-checked="campaign.effective_status === 'ACTIVE'"
                                    >
                                        <span
                                            :class="[
                                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                                campaign.effective_status === 'ACTIVE' ? 'translate-x-5' : 'translate-x-0'
                                            ]"
                                        >
                                            <!-- Loading indicator -->
                                            <span v-if="isToggling(campaign)" class="absolute inset-0 flex items-center justify-center">
                                                <svg class="animate-spin h-3 w-3 text-blue-500" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                </svg>
                                            </span>
                                        </span>
                                    </button>
                                    <!-- Non-toggleable status indicator -->
                                    <span v-else class="text-gray-400 dark:text-gray-500 text-xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ campaign.name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ campaign.meta_campaign_id }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="getStatusColor(campaign.effective_status)" class="px-2.5 py-1 text-xs font-semibold rounded-full">
                                    {{ formatStatus(campaign.effective_status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                {{ formatObjective(campaign.objective) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right font-medium">
                                {{ formatCurrency(campaign.total_spend, currency) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300 text-right">
                                {{ formatNumber(campaign.total_impressions) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300 text-right">
                                {{ formatNumber(campaign.total_clicks) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right font-medium">
                                {{ formatPercent(campaign.avg_ctr) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300 text-right">
                                {{ formatCurrency(campaign.avg_cpc, currency) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div v-if="getResultValue(campaign) > 0" class="text-sm">
                                    <span class="font-bold text-green-600 dark:text-green-400">{{ formatNumber(getResultValue(campaign)) }}</span>
                                    <span class="text-gray-500 dark:text-gray-400 text-xs ml-1">{{ getResultLabel(campaign) }}</span>
                                    <div class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ formatCurrency(getCostPerResult(campaign), currency) }}/{{ getResultLabel(campaign) }}
                                    </div>
                                </div>
                                <span v-else class="text-gray-400 dark:text-gray-500 text-sm">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-600 dark:text-gray-300">
                                {{ formatDate(campaign.created_time) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 p-1" @click.stop="openCampaignDetail(campaign)">
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
            <div v-else class="p-12 text-center text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="text-lg font-medium mb-2 text-gray-700 dark:text-gray-300">{{ t('meta.no_campaigns_found') }}</p>
                <p class="text-sm">{{ t('meta.try_changing_filters') }}</p>
            </div>

            <!-- Pagination -->
            <div v-if="campaigns.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ t('meta.showing') }}: {{ pagination.from }}-{{ pagination.to }} / {{ pagination.total }}
                </div>
                <div class="flex items-center gap-1">
                    <!-- Previous -->
                    <button @click="goToPage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <!-- Page Numbers -->
                    <template v-for="page in paginationPages" :key="page">
                        <span v-if="page === '...'" class="px-3 py-1 text-gray-500 dark:text-gray-400">...</span>
                        <button v-else @click="goToPage(page)"
                            :class="[
                                'px-3 py-1 rounded-lg text-sm font-medium transition-colors',
                                page === pagination.current_page
                                    ? 'bg-blue-600 text-white'
                                    : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400'
                            ]">
                            {{ page }}
                        </button>
                    </template>

                    <!-- Next -->
                    <button @click="goToPage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Create Campaign Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" @click="closeCreateModal"></div>

            <!-- Modal panel -->
            <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-white dark:bg-gray-800 rounded-2xl shadow-xl z-10">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between sticky top-0 bg-white dark:bg-gray-800 z-10">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ t('meta.create_new_campaign') }}</h3>
                    <button @click="closeCreateModal" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="px-6 py-4 space-y-6">
                    <!-- Campaign Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ t('meta.campaign_name') }}</label>
                        <input v-model="newCampaign.name" type="text" :placeholder="t('meta.campaign_name_placeholder')"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>

                    <!-- Objective Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ t('meta.campaign_objective') }}</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button v-for="objective in creationOptions.objectives" :key="objective.value"
                                @click="newCampaign.objective = objective.value"
                                :class="[
                                    'p-4 border rounded-xl text-left transition-all',
                                    newCampaign.objective === objective.value
                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                        : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                                ]">
                                <div class="flex items-center gap-3">
                                    <div :class="[
                                        'w-10 h-10 rounded-lg flex items-center justify-center',
                                        newCampaign.objective === objective.value
                                            ? 'bg-blue-500 text-white'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                                    ]">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getObjectiveIcon(objective.icon)" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white text-sm">{{ objective.label }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ objective.description }}</div>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Budget -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ t('meta.daily_budget_optional') }}</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">$</span>
                            <input v-model="newCampaign.daily_budget" type="number" step="0.01" min="1" placeholder="0.00"
                                class="w-full pl-8 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ t('meta.budget_hint') }}</p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ t('meta.initial_status') }}</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="newCampaign.status" value="PAUSED" class="text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ t('meta.paused_safe') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="newCampaign.status" value="ACTIVE" class="text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ t('meta.status_active') }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3 sticky bottom-0">
                    <button @click="closeCreateModal"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white font-medium">
                        {{ t('common.cancel') }}
                    </button>
                    <button @click="createCampaign" :disabled="creatingCampaign || !newCampaign.name || !newCampaign.objective"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 font-medium">
                        <svg v-if="creatingCampaign" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ creatingCampaign ? t('meta.creating') : t('common.create') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Full Ad Creator Wizard -->
        <AdCreatorWizard
            :show="showWizard"
            :business-id="businessId"
            @close="showWizard = false"
            @created="handleWizardCreated"
            @error="(msg) => emit('error', msg)"
        />
    </div>
</template>
