<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import axios from 'axios';

const props = defineProps({
    business: Object,
    analysis: Object,
    lastUpdated: String,
    error: String,
    // Meta Ads props
    metaIntegration: Object,
    metaAdAccounts: Array,
    selectedMetaAccount: Object,
});

const page = usePage();
const loading = ref(false);
const refreshing = ref(false);
const activeTab = ref('overview');
const showAIInsights = ref(false);
const generatingInsights = ref(false);
const aiInsights = ref(null);

// Meta Ads states
const metaLoading = ref(false);
const metaSyncing = ref(false);
const metaConnecting = ref(false);
const metaDateRange = ref('maximum');
const metaOverview = ref(null);
const metaCampaigns = ref([]);
const metaDemographics = ref({ age: [], gender: [] });
const metaPlacements = ref({ platforms: [], positions: [] });
const metaTrend = ref([]);
const metaAIInsights = ref(null);
const generatingMetaInsights = ref(false);
const showMetaAIModal = ref(false);
const metaDataLoaded = ref(false);
const metaError = ref(null);

// Filters and pagination
const campaignStatusFilter = ref('all');
const campaignSortBy = ref('spend');
const campaignSearch = ref('');
const currentPage = ref(1);
const itemsPerPage = 15;
const selectedCampaign = ref(null);

const dateRangeOptions = [
    { value: 'last_7d', label: 'Oxirgi 7 kun' },
    { value: 'last_14d', label: 'Oxirgi 14 kun' },
    { value: 'last_30d', label: 'Oxirgi 30 kun' },
    { value: 'last_90d', label: 'Oxirgi 90 kun' },
];

// Computed properties for formatted data
const formattedRevenue = computed(() => {
    return new Intl.NumberFormat('uz-UZ').format(props.analysis?.overview?.total_revenue || 0);
});

const formattedAvgLTV = computed(() => {
    return new Intl.NumberFormat('uz-UZ').format(props.analysis?.overview?.avg_ltv || 0);
});

const isMetaConnected = computed(() => {
    return props.metaIntegration?.status === 'connected';
});

const hasMetaAccount = computed(() => {
    return isMetaConnected.value && props.selectedMetaAccount;
});

// Computed for campaign statistics (shown in Overview even without insights)
const campaignStats = computed(() => {
    const active = metaCampaigns.value.filter(c => c.status === 'ACTIVE').length;
    const paused = metaCampaigns.value.filter(c => c.status === 'PAUSED').length;
    const total = metaCampaigns.value.length;
    const totalSpend = metaCampaigns.value.reduce((sum, c) => sum + (c.spend || 0), 0);
    const totalClicks = metaCampaigns.value.reduce((sum, c) => sum + (c.clicks || 0), 0);
    const totalImpressions = metaCampaigns.value.reduce((sum, c) => sum + (c.impressions || 0), 0);

    return {
        active,
        paused,
        total,
        totalSpend,
        totalClicks,
        totalImpressions,
        avgCtr: totalImpressions > 0 ? (totalClicks / totalImpressions * 100) : 0,
    };
});

// Format helpers
const formatNumber = (num) => new Intl.NumberFormat('uz-UZ').format(num || 0);
const formatCurrency = (amount, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(amount || 0);
};
const formatPercent = (value) => (value || 0).toFixed(2) + '%';

const formatObjective = (objective) => {
    const objectives = {
        'OUTCOME_TRAFFIC': 'Trafik',
        'OUTCOME_ENGAGEMENT': 'Jalb qilish',
        'OUTCOME_AWARENESS': 'Xabardorlik',
        'OUTCOME_LEADS': 'Lidlar',
        'OUTCOME_SALES': 'Sotuvlar',
        'LINK_CLICKS': 'Havolaga bosish',
        'REACH': 'Qamrov',
        'BRAND_AWARENESS': 'Brend xabardorligi',
        'VIDEO_VIEWS': 'Video ko\'rishlar',
        'MESSAGES': 'Xabarlar',
        'CONVERSIONS': 'Konversiyalar',
    };
    return objectives[objective] || objective || 'Noma\'lum';
};

// Filtered and sorted campaigns
const filteredCampaigns = computed(() => {
    let campaigns = [...metaCampaigns.value];

    // Filter by status
    if (campaignStatusFilter.value !== 'all') {
        campaigns = campaigns.filter(c => c.status === campaignStatusFilter.value);
    }

    // Filter by search
    if (campaignSearch.value) {
        const search = campaignSearch.value.toLowerCase();
        campaigns = campaigns.filter(c => c.name.toLowerCase().includes(search));
    }

    // Sort
    campaigns.sort((a, b) => {
        switch (campaignSortBy.value) {
            case 'spend': return (b.spend || 0) - (a.spend || 0);
            case 'clicks': return (b.clicks || 0) - (a.clicks || 0);
            case 'impressions': return (b.impressions || 0) - (a.impressions || 0);
            case 'ctr': return (b.ctr || 0) - (a.ctr || 0);
            case 'cpc': return (a.cpc || 0) - (b.cpc || 0); // Lower is better
            case 'created': return new Date(b.created_time || 0) - new Date(a.created_time || 0);
            default: return 0;
        }
    });

    return campaigns;
});

const totalPages = computed(() => Math.ceil(filteredCampaigns.value.length / itemsPerPage));

const paginatedCampaigns = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return filteredCampaigns.value.slice(start, start + itemsPerPage);
});

// Campaign selection
const selectCampaign = (campaign) => {
    selectedCampaign.value = campaign;
};

// Reset pagination when filters change
watch([campaignStatusFilter, campaignSearch, campaignSortBy], () => {
    currentPage.value = 1;
});

// Methods
const refreshAnalysis = async () => {
    refreshing.value = true;
    try {
        const response = await axios.get('/business/api/target-analysis', {
            params: { business_id: props.business.id }
        });
        if (response.data.success) {
            window.location.reload();
        }
    } catch (error) {
        console.error('Error refreshing analysis:', error);
    } finally {
        refreshing.value = false;
    }
};

const generateAIInsights = async () => {
    generatingInsights.value = true;
    showAIInsights.value = true;
    try {
        const response = await axios.post('/business/api/target-analysis/insights/regenerate', {
            business_id: props.business.id
        });
        if (response.data.success) {
            aiInsights.value = response.data.insights;
        }
    } catch (error) {
        console.error('Error generating AI insights:', error);
        aiInsights.value = { success: false, insights: 'AI tahlil yaratishda xatolik yuz berdi' };
    } finally {
        generatingInsights.value = false;
    }
};

const exportAnalysis = async () => {
    try {
        const response = await axios.get('/business/api/target-analysis/export', {
            params: { business_id: props.business.id },
            responseType: 'blob'
        });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `target-analysis-${Date.now()}.json`);
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (error) {
        console.error('Error exporting analysis:', error);
    }
};

// Meta Ads Methods
const connectMeta = async () => {
    metaConnecting.value = true;
    metaError.value = null;
    try {
        const response = await axios.get('/business/target-analysis/meta/auth-url', {
            params: { business_id: props.business.id }
        });
        if (response.data.url) {
            window.location.href = response.data.url;
        } else {
            metaError.value = 'OAuth URL olinmadi. Meta App sozlamalarini tekshiring.';
        }
    } catch (error) {
        console.error('Error getting auth URL:', error);
        metaError.value = error.response?.data?.message || 'Meta bilan ulanishda xatolik yuz berdi';
    } finally {
        metaConnecting.value = false;
    }
};

const disconnectMeta = async () => {
    if (!confirm('Meta Ads integratsiyasini uzmoqchimisiz?')) return;
    metaLoading.value = true;
    try {
        await axios.post('/business/target-analysis/meta/disconnect', {
            business_id: props.business.id
        });
        window.location.reload();
    } catch (error) {
        console.error('Error disconnecting:', error);
    } finally {
        metaLoading.value = false;
    }
};

const syncMeta = async () => {
    metaSyncing.value = true;
    metaError.value = null;
    try {
        const response = await axios.post('/business/target-analysis/meta/sync', {
            business_id: props.business.id
        });
        if (response.data.success) {
            // Show success message and reload to get updated accounts
            alert(response.data.message || 'Ma\'lumotlar muvaffaqiyatli yuklandi!');
            window.location.reload();
        } else {
            metaError.value = response.data.error || 'Yuklashda xatolik yuz berdi';
        }
    } catch (error) {
        console.error('Error syncing:', error);
        metaError.value = error.response?.data?.error || 'Meta ma\'lumotlarini yuklashda xatolik yuz berdi';
    } finally {
        metaSyncing.value = false;
    }
};

const selectMetaAccount = async (accountId) => {
    metaLoading.value = true;
    try {
        await axios.post('/business/target-analysis/meta/select-account', {
            business_id: props.business.id,
            account_id: accountId,
        });
        window.location.reload();
    } catch (error) {
        console.error('Error selecting account:', error);
    } finally {
        metaLoading.value = false;
    }
};

const loadMetaOverview = async () => {
    try {
        const response = await axios.get('/business/api/target-analysis/meta/overview', {
            params: { business_id: props.business.id, period: metaDateRange.value }
        });
        metaOverview.value = response.data;
    } catch (error) {
        console.error('Error loading overview:', error);
    }
};

const loadMetaCampaigns = async () => {
    try {
        const response = await axios.get('/business/api/target-analysis/meta/campaigns', {
            params: { business_id: props.business.id, period: metaDateRange.value }
        });
        metaCampaigns.value = response.data.campaigns || [];
    } catch (error) {
        console.error('Error loading campaigns:', error);
    }
};

const loadMetaDemographics = async () => {
    try {
        const response = await axios.get('/business/api/target-analysis/meta/demographics', {
            params: { business_id: props.business.id, period: metaDateRange.value }
        });
        metaDemographics.value = response.data;
    } catch (error) {
        console.error('Error loading demographics:', error);
    }
};

const loadMetaPlacements = async () => {
    try {
        const response = await axios.get('/business/api/target-analysis/meta/placements', {
            params: { business_id: props.business.id, period: metaDateRange.value }
        });
        metaPlacements.value = response.data;
    } catch (error) {
        console.error('Error loading placements:', error);
    }
};

const loadMetaTrend = async () => {
    try {
        const response = await axios.get('/business/api/target-analysis/meta/trend', {
            params: { business_id: props.business.id, days: 30 }
        });
        metaTrend.value = response.data.trend || [];
    } catch (error) {
        console.error('Error loading trend:', error);
    }
};

const loadMetaData = async () => {
    if (!isMetaConnected.value || !props.selectedMetaAccount) return;
    metaLoading.value = true;
    metaError.value = null;
    metaDataLoaded.value = false;
    try {
        await Promise.all([
            loadMetaOverview(),
            loadMetaCampaigns(),
            loadMetaDemographics(),
            loadMetaPlacements(),
            loadMetaTrend(),
        ]);
        metaDataLoaded.value = true;
    } catch (error) {
        console.error('Error loading Meta data:', error);
        metaError.value = 'Meta ma\'lumotlarini yuklashda xatolik yuz berdi';
    } finally {
        metaLoading.value = false;
    }
};

const generateMetaAIInsights = async () => {
    generatingMetaInsights.value = true;
    showMetaAIModal.value = true;
    try {
        const response = await axios.post('/business/api/target-analysis/meta/ai-insights', {
            business_id: props.business.id,
            period: metaDateRange.value,
        });
        metaAIInsights.value = response.data;
    } catch (error) {
        console.error('Error generating insights:', error);
        metaAIInsights.value = { success: false, error: 'Xatolik yuz berdi' };
    } finally {
        generatingMetaInsights.value = false;
    }
};

const getStatusColor = (status) => {
    const colors = {
        ACTIVE: 'bg-green-100 text-green-800',
        PAUSED: 'bg-yellow-100 text-yellow-800',
        DELETED: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

watch(metaDateRange, () => {
    if (isMetaConnected.value && props.selectedMetaAccount) {
        loadMetaData();
    }
});

onMounted(() => {
    if (props.analysis?.ai_insights) {
        aiInsights.value = props.analysis.ai_insights;
    }
    if (isMetaConnected.value && props.selectedMetaAccount) {
        loadMetaData();
    }
});
</script>

<template>
    <Head title="Target Tahlili" />

    <BusinessLayout>
        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Target Tahlili</h1>
                            <p class="mt-2 text-gray-600">
                                <span v-if="business">{{ business.name }}</span>
                                <span v-if="lastUpdated" class="ml-2 text-sm">• Oxirgi yangilanish: {{ lastUpdated }}</span>
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <button @click="generateAIInsights" :disabled="generatingInsights"
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 flex items-center gap-2">
                                <svg v-if="!generatingInsights" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                <svg v-else class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                AI Tahlil
                            </button>
                            <button @click="exportAnalysis" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Export
                            </button>
                            <button @click="refreshAnalysis" :disabled="refreshing"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2">
                                <svg :class="{ 'animate-spin': refreshing }" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Yangilash
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Error State -->
                <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                    <p class="text-red-800">{{ error }}</p>
                </div>

                <!-- Main Content -->
                <div v-else>
                    <!-- AI Insights Modal -->
                    <div v-if="showAIInsights" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                        <div class="bg-white rounded-xl max-w-4xl w-full max-h-[80vh] overflow-y-auto">
                            <div class="p-6 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white">
                                <h3 class="text-xl font-bold text-gray-900">AI Tahlil Natijalari</h3>
                                <button @click="showAIInsights = false" class="text-gray-500 hover:text-gray-700">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div class="p-6">
                                <div v-if="generatingInsights" class="text-center py-12">
                                    <svg class="animate-spin h-12 w-12 mx-auto mb-4 text-purple-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <p class="text-gray-600">AI tahlil yaratilmoqda...</p>
                                </div>
                                <div v-else-if="aiInsights" class="prose max-w-none">
                                    <div class="whitespace-pre-wrap text-gray-800">{{ aiInsights.insights || aiInsights }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Meta AI Insights Modal -->
                    <div v-if="showMetaAIModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                        <div class="bg-white rounded-xl max-w-4xl w-full max-h-[80vh] overflow-y-auto">
                            <div class="p-6 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white">
                                <h3 class="text-xl font-bold text-gray-900">Meta Ads AI Tahlil</h3>
                                <button @click="showMetaAIModal = false" class="text-gray-500 hover:text-gray-700">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div class="p-6">
                                <div v-if="generatingMetaInsights" class="text-center py-12">
                                    <svg class="animate-spin h-12 w-12 mx-auto mb-4 text-purple-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <p class="text-gray-600">AI tahlil yaratilmoqda...</p>
                                </div>
                                <div v-else-if="metaAIInsights?.success" class="space-y-6">
                                    <div class="bg-blue-50 p-4 rounded-lg">
                                        <h4 class="font-semibold text-blue-900 mb-2">Samaradorlik Xulosasi</h4>
                                        <p class="text-blue-800">{{ metaAIInsights.performance_summary }}</p>
                                    </div>
                                    <div class="bg-green-50 p-4 rounded-lg">
                                        <h4 class="font-semibold text-green-900 mb-2">Tavsiyalar</h4>
                                        <ul class="list-disc list-inside text-green-800 space-y-1">
                                            <li v-for="(rec, i) in metaAIInsights.recommendations" :key="i">{{ rec }}</li>
                                        </ul>
                                    </div>
                                    <div class="bg-purple-50 p-4 rounded-lg">
                                        <h4 class="font-semibold text-purple-900 mb-2">Auditoriya Tahlili</h4>
                                        <p class="text-purple-800">{{ metaAIInsights.audience_insights }}</p>
                                    </div>
                                </div>
                                <div v-else-if="metaAIInsights" class="text-red-600">
                                    {{ metaAIInsights.error || 'Xatolik yuz berdi' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="-mb-px flex space-x-8 overflow-x-auto">
                            <button @click="activeTab = 'overview'" :class="[activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                                Umumiy Ko'rinish
                            </button>
                            <button @click="activeTab = 'dreambuyer'" :class="[activeTab === 'dreambuyer' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                                Dream Buyer
                            </button>
                            <button @click="activeTab = 'segments'" :class="[activeTab === 'segments' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                                Segmentatsiya
                            </button>
                            <button @click="activeTab = 'funnel'" :class="[activeTab === 'funnel' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                                Voronka
                            </button>
                            <button @click="activeTab = 'churn'" :class="[activeTab === 'churn' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                                Churn
                            </button>
                            <button @click="activeTab = 'meta'" :class="[activeTab === 'meta' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2']">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                Meta Ads
                            </button>
                        </nav>
                    </div>

                    <!-- Connect Meta Required Message (shown for all tabs except meta when not connected) -->
                    <div v-if="!hasMetaAccount && activeTab !== 'meta'" class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                            <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 mb-3">Meta hisobingizni ulang</h2>
                        <p class="text-gray-600 mb-6 max-w-md mx-auto">
                            {{ activeTab === 'overview' ? 'Umumiy ko\'rinish' :
                               activeTab === 'dreambuyer' ? 'Dream Buyer tahlili' :
                               activeTab === 'segments' ? 'Segmentatsiya' :
                               activeTab === 'funnel' ? 'Voronka tahlili' :
                               'Churn tahlili' }}
                            ma'lumotlarini ko'rish uchun avval Facebook/Instagram reklama hisobingizni ulang.
                        </p>
                        <button @click="activeTab = 'meta'" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl hover:from-blue-700 hover:to-purple-700 font-semibold inline-flex items-center gap-2 shadow-lg transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            Meta hisobini ulash
                        </button>
                    </div>

                    <!-- Overview Tab -->
                    <div v-show="activeTab === 'overview' && hasMetaAccount && metaDataLoaded" class="space-y-6">
                        <!-- Filters Bar -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                            <div class="flex flex-wrap items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <label class="text-sm font-medium text-gray-700">Davr:</label>
                                    <select v-model="metaDateRange" @change="loadMetaData"
                                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="last_7d">Oxirgi 7 kun</option>
                                        <option value="last_14d">Oxirgi 14 kun</option>
                                        <option value="last_30d">Oxirgi 30 kun</option>
                                        <option value="last_90d">Oxirgi 90 kun</option>
                                        <option value="maximum">Barcha vaqt</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-sm font-medium text-gray-700">Status:</label>
                                    <select v-model="campaignStatusFilter"
                                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="all">Barchasi</option>
                                        <option value="ACTIVE">Faol</option>
                                        <option value="PAUSED">To'xtatilgan</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-sm font-medium text-gray-700">Tartiblash:</label>
                                    <select v-model="campaignSortBy"
                                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="spend">Sarflangan</option>
                                        <option value="clicks">Kliklar</option>
                                        <option value="impressions">Ko'rishlar</option>
                                        <option value="ctr">CTR</option>
                                        <option value="cpc">CPC</option>
                                        <option value="created">Yaratilgan sana</option>
                                    </select>
                                </div>
                                <button @click="loadMetaData" :disabled="metaLoading"
                                    class="ml-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2 text-sm">
                                    <svg :class="{ 'animate-spin': metaLoading }" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Yangilash
                                </button>
                            </div>
                        </div>

                        <!-- Campaign Summary Cards -->
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-sm p-5 text-white">
                                <p class="text-xs font-medium text-blue-100 uppercase">Kampaniyalar</p>
                                <p class="text-2xl font-bold mt-1">{{ campaignStats.total }}</p>
                                <p class="text-xs text-blue-100 mt-1">{{ campaignStats.active }} faol</p>
                            </div>
                            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                                <p class="text-xs font-medium text-gray-500 uppercase">Sarflangan</p>
                                <p class="text-xl font-bold text-gray-900 mt-1">{{ formatCurrency(metaOverview?.current?.spend || 0, selectedMetaAccount?.currency) }}</p>
                                <p class="text-xs mt-1" :class="(metaOverview?.change?.spend || 0) >= 0 ? 'text-red-500' : 'text-green-500'">
                                    {{ (metaOverview?.change?.spend || 0) >= 0 ? '+' : '' }}{{ metaOverview?.change?.spend || 0 }}%
                                </p>
                            </div>
                            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                                <p class="text-xs font-medium text-gray-500 uppercase">Reach</p>
                                <p class="text-xl font-bold text-gray-900 mt-1">{{ formatNumber(metaOverview?.current?.reach || 0) }}</p>
                                <p class="text-xs text-gray-500 mt-1">Noyob foydalanuvchi</p>
                            </div>
                            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                                <p class="text-xs font-medium text-gray-500 uppercase">Impressions</p>
                                <p class="text-xl font-bold text-gray-900 mt-1">{{ formatNumber(metaOverview?.current?.impressions || 0) }}</p>
                                <p class="text-xs text-gray-500 mt-1">Jami ko'rishlar</p>
                            </div>
                            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                                <p class="text-xs font-medium text-gray-500 uppercase">Kliklar</p>
                                <p class="text-xl font-bold text-gray-900 mt-1">{{ formatNumber(metaOverview?.current?.clicks || 0) }}</p>
                                <p class="text-xs mt-1" :class="(metaOverview?.change?.clicks || 0) >= 0 ? 'text-green-500' : 'text-red-500'">
                                    {{ (metaOverview?.change?.clicks || 0) >= 0 ? '+' : '' }}{{ metaOverview?.change?.clicks || 0 }}%
                                </p>
                            </div>
                            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                                <p class="text-xs font-medium text-gray-500 uppercase">CTR</p>
                                <p class="text-xl font-bold mt-1" :class="(metaOverview?.current?.ctr || 0) >= 1 ? 'text-green-600' : 'text-orange-600'">
                                    {{ formatPercent(metaOverview?.current?.ctr || 0) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">CPC: {{ formatCurrency(metaOverview?.current?.cpc || 0, selectedMetaAccount?.currency) }}</p>
                            </div>
                        </div>

                        <!-- Campaigns Table -->
                        <div v-if="filteredCampaigns.length" class="bg-white rounded-xl shadow-sm border border-gray-200">
                            <div class="p-5 border-b border-gray-200 flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Kampaniyalar</h3>
                                    <p class="text-sm text-gray-500">{{ filteredCampaigns.length }} ta kampaniya</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input v-model="campaignSearch" type="text" placeholder="Qidirish..."
                                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 w-48" />
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kampaniya</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Maqsad</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sarflangan</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ko'rishlar</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Kliklar</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">CTR</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">CPC</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Amal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="campaign in paginatedCampaigns" :key="campaign.id"
                                            class="hover:bg-gray-50 cursor-pointer" @click="selectCampaign(campaign)">
                                            <td class="px-4 py-4">
                                                <div class="text-sm font-medium text-gray-900 max-w-xs truncate">{{ campaign.name }}</div>
                                                <div class="text-xs text-gray-500">ID: {{ campaign.id }}</div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span :class="getStatusColor(campaign.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                                                    {{ campaign.status === 'ACTIVE' ? 'Faol' : campaign.status === 'PAUSED' ? 'To\'xtatilgan' : campaign.status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded">{{ formatObjective(campaign.objective) }}</span>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-right font-medium text-gray-900">
                                                {{ formatCurrency(campaign.spend, selectedMetaAccount?.currency) }}
                                            </td>
                                            <td class="px-4 py-4 text-sm text-right text-gray-600">
                                                {{ formatNumber(campaign.impressions) }}
                                            </td>
                                            <td class="px-4 py-4 text-sm text-right text-gray-600">
                                                {{ formatNumber(campaign.clicks) }}
                                            </td>
                                            <td class="px-4 py-4 text-sm text-right">
                                                <span :class="campaign.ctr >= 1 ? 'text-green-600 font-medium' : campaign.ctr >= 0.5 ? 'text-orange-600' : 'text-red-600'">
                                                    {{ formatPercent(campaign.ctr) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-right text-gray-600">
                                                {{ formatCurrency(campaign.cpc, selectedMetaAccount?.currency) }}
                                            </td>
                                            <td class="px-4 py-4 text-center">
                                                <button @click.stop="selectCampaign(campaign)" class="text-blue-600 hover:text-blue-800">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination -->
                            <div class="px-5 py-4 border-t border-gray-200 flex items-center justify-between">
                                <p class="text-sm text-gray-500">
                                    {{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, filteredCampaigns.length) }} / {{ filteredCampaigns.length }} ta kampaniya
                                </p>
                                <div class="flex items-center gap-2">
                                    <button @click="currentPage--" :disabled="currentPage === 1"
                                        class="px-3 py-1 border border-gray-300 rounded text-sm disabled:opacity-50 hover:bg-gray-50">
                                        Oldingi
                                    </button>
                                    <span class="text-sm text-gray-600">{{ currentPage }} / {{ totalPages }}</span>
                                    <button @click="currentPage++" :disabled="currentPage === totalPages"
                                        class="px-3 py-1 border border-gray-300 rounded text-sm disabled:opacity-50 hover:bg-gray-50">
                                        Keyingi
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Campaign Detail Modal -->
                        <div v-if="selectedCampaign" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                            <div class="bg-white rounded-xl max-w-3xl w-full max-h-[85vh] overflow-y-auto">
                                <div class="p-6 border-b border-gray-200 flex justify-between items-start sticky top-0 bg-white">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900">{{ selectedCampaign.name }}</h3>
                                        <p class="text-sm text-gray-500 mt-1">ID: {{ selectedCampaign.id }}</p>
                                    </div>
                                    <button @click="selectedCampaign = null" class="text-gray-500 hover:text-gray-700">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="p-6 space-y-6">
                                    <!-- Campaign Info -->
                                    <div class="flex items-center gap-4">
                                        <span :class="getStatusColor(selectedCampaign.status)" class="px-3 py-1.5 text-sm font-semibold rounded-full">
                                            {{ selectedCampaign.status === 'ACTIVE' ? 'Faol' : 'To\'xtatilgan' }}
                                        </span>
                                        <span class="text-sm text-gray-600 bg-gray-100 px-3 py-1.5 rounded-full">{{ formatObjective(selectedCampaign.objective) }}</span>
                                    </div>

                                    <!-- Performance Metrics -->
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                                            <p class="text-xs text-gray-500 uppercase">Sarflangan</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatCurrency(selectedCampaign.spend, selectedMetaAccount?.currency) }}</p>
                                        </div>
                                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                                            <p class="text-xs text-gray-500 uppercase">Ko'rishlar</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatNumber(selectedCampaign.impressions) }}</p>
                                        </div>
                                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                                            <p class="text-xs text-gray-500 uppercase">Kliklar</p>
                                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatNumber(selectedCampaign.clicks) }}</p>
                                        </div>
                                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                                            <p class="text-xs text-gray-500 uppercase">CTR</p>
                                            <p class="text-2xl font-bold mt-1" :class="selectedCampaign.ctr >= 1 ? 'text-green-600' : 'text-orange-600'">
                                                {{ formatPercent(selectedCampaign.ctr) }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Additional Metrics -->
                                    <div class="grid grid-cols-3 gap-4">
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <p class="text-sm text-gray-500">CPC (Klik uchun narx)</p>
                                            <p class="text-xl font-bold text-gray-900 mt-1">{{ formatCurrency(selectedCampaign.cpc, selectedMetaAccount?.currency) }}</p>
                                        </div>
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <p class="text-sm text-gray-500">CPM (1000 ko'rish narxi)</p>
                                            <p class="text-xl font-bold text-gray-900 mt-1">{{ formatCurrency(selectedCampaign.impressions > 0 ? (selectedCampaign.spend / selectedCampaign.impressions * 1000) : 0, selectedMetaAccount?.currency) }}</p>
                                        </div>
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <p class="text-sm text-gray-500">Konversiyalar</p>
                                            <p class="text-xl font-bold text-gray-900 mt-1">{{ formatNumber(selectedCampaign.conversions || 0) }}</p>
                                        </div>
                                    </div>

                                    <!-- Performance Assessment -->
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <h4 class="font-semibold text-gray-900 mb-3">Samaradorlik Bahosi</h4>
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-gray-600">CTR Darajasi</span>
                                                <div class="flex items-center gap-2">
                                                    <div class="w-32 bg-gray-200 rounded-full h-2">
                                                        <div class="h-2 rounded-full" :class="selectedCampaign.ctr >= 1 ? 'bg-green-500' : selectedCampaign.ctr >= 0.5 ? 'bg-yellow-500' : 'bg-red-500'"
                                                            :style="{ width: Math.min(selectedCampaign.ctr * 33, 100) + '%' }"></div>
                                                    </div>
                                                    <span class="text-sm font-medium" :class="selectedCampaign.ctr >= 1 ? 'text-green-600' : selectedCampaign.ctr >= 0.5 ? 'text-yellow-600' : 'text-red-600'">
                                                        {{ selectedCampaign.ctr >= 1 ? 'Yaxshi' : selectedCampaign.ctr >= 0.5 ? 'O\'rtacha' : 'Past' }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-gray-600">CPC Darajasi</span>
                                                <div class="flex items-center gap-2">
                                                    <div class="w-32 bg-gray-200 rounded-full h-2">
                                                        <div class="h-2 rounded-full" :class="selectedCampaign.cpc <= 0.5 ? 'bg-green-500' : selectedCampaign.cpc <= 1 ? 'bg-yellow-500' : 'bg-red-500'"
                                                            :style="{ width: Math.min(100 - selectedCampaign.cpc * 25, 100) + '%' }"></div>
                                                    </div>
                                                    <span class="text-sm font-medium" :class="selectedCampaign.cpc <= 0.5 ? 'text-green-600' : selectedCampaign.cpc <= 1 ? 'text-yellow-600' : 'text-red-600'">
                                                        {{ selectedCampaign.cpc <= 0.5 ? 'Arzon' : selectedCampaign.cpc <= 1 ? 'O\'rtacha' : 'Qimmat' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Recommendations -->
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <h4 class="font-semibold text-blue-900 mb-2">Tavsiyalar</h4>
                                        <ul class="text-sm text-blue-800 space-y-1">
                                            <li v-if="selectedCampaign.ctr < 0.5">• CTR juda past. Kreativlarni yangilang va maqsadli auditoriyani qayta ko'rib chiqing.</li>
                                            <li v-if="selectedCampaign.ctr >= 0.5 && selectedCampaign.ctr < 1">• CTR o'rtacha. A/B test orqali kreativlarni optimallashtiring.</li>
                                            <li v-if="selectedCampaign.ctr >= 1">• CTR yaxshi! Budjetni oshirishni ko'rib chiqing.</li>
                                            <li v-if="selectedCampaign.cpc > 1">• CPC yuqori. Auditoriyani kengaytiring yoki bid strategiyasini o'zgartiring.</li>
                                            <li v-if="selectedCampaign.status === 'PAUSED' && selectedCampaign.ctr >= 1">• Yaxshi natijali kampaniya to'xtatilgan. Qayta ishga tushirishni ko'rib chiqing.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dream Buyer Tab - Based on Meta Demographics -->
                    <div v-show="activeTab === 'dreambuyer' && hasMetaAccount && metaDataLoaded" class="space-y-6">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Ideal Auditoriya Profili (Meta asosida)</h3>
                            <p class="text-gray-600 mb-6">Facebook/Instagram reklama ma'lumotlari asosida eng samarali auditoriya segmentlari</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Age Based Dream Buyer -->
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-900 mb-3">Yosh bo'yicha eng samarali</h4>
                                    <div v-if="metaDemographics.age?.length" class="space-y-2">
                                        <div v-for="(item, index) in metaDemographics.age.slice(0, 3)" :key="item.label"
                                            :class="index === 0 ? 'bg-green-50 border-green-200' : 'bg-gray-50'"
                                            class="p-3 rounded-lg border">
                                            <div class="flex justify-between items-center">
                                                <span class="font-medium" :class="index === 0 ? 'text-green-800' : 'text-gray-700'">{{ item.label }} yosh</span>
                                                <span class="font-bold" :class="index === 0 ? 'text-green-600' : 'text-gray-900'">{{ item.percentage }}%</span>
                                            </div>
                                            <p v-if="index === 0" class="text-sm text-green-600 mt-1">Eng yuqori konversiya</p>
                                        </div>
                                    </div>
                                    <p v-else class="text-gray-500 text-center py-4">Ma'lumot mavjud emas</p>
                                </div>

                                <!-- Platform Based Dream Buyer -->
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-900 mb-3">Platforma bo'yicha</h4>
                                    <div v-if="metaPlacements.platforms?.length" class="space-y-2">
                                        <div v-for="(item, index) in metaPlacements.platforms" :key="item.label"
                                            :class="index === 0 ? 'bg-blue-50 border-blue-200' : 'bg-gray-50'"
                                            class="p-3 rounded-lg border">
                                            <div class="flex justify-between items-center">
                                                <span class="font-medium" :class="index === 0 ? 'text-blue-800' : 'text-gray-700'">{{ item.label }}</span>
                                                <span class="font-bold" :class="index === 0 ? 'text-blue-600' : 'text-gray-900'">{{ item.percentage }}%</span>
                                            </div>
                                            <p v-if="index === 0" class="text-sm text-blue-600 mt-1">Asosiy platforma</p>
                                        </div>
                                    </div>
                                    <p v-else class="text-gray-500 text-center py-4">Ma'lumot mavjud emas</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Segments Tab - Based on Meta Data -->
                    <div v-show="activeTab === 'segments' && hasMetaAccount && metaDataLoaded" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Age Segmentation -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Yosh Bo'yicha Segmentatsiya</h3>
                                <div v-if="metaDemographics.age?.length" class="space-y-4">
                                    <div v-for="item in metaDemographics.age" :key="item.label">
                                        <div class="flex justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-700">{{ item.label }}</span>
                                            <span class="text-sm font-semibold text-gray-900">{{ item.percentage }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full" :style="{ width: item.percentage + '%' }"></div>
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="text-gray-500 text-center py-8">Demografik ma'lumot mavjud emas</p>
                            </div>

                            <!-- Platform Segmentation -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Platforma Bo'yicha Segmentatsiya</h3>
                                <div v-if="metaPlacements.platforms?.length" class="space-y-3">
                                    <div v-for="item in metaPlacements.platforms" :key="item.label"
                                        class="flex justify-between items-center p-3 rounded-lg"
                                        :class="item.label.toLowerCase() === 'facebook' ? 'bg-blue-50' : 'bg-purple-50'">
                                        <div class="flex items-center gap-3">
                                            <div :class="item.label.toLowerCase() === 'facebook' ? 'bg-blue-100' : 'bg-purple-100'" class="w-10 h-10 rounded-lg flex items-center justify-center">
                                                <svg v-if="item.label.toLowerCase() === 'facebook'" class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                                </svg>
                                                <svg v-else class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069z"/>
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">{{ item.label }}</span>
                                        </div>
                                        <span class="text-lg font-bold" :class="item.label.toLowerCase() === 'facebook' ? 'text-blue-600' : 'text-purple-600'">{{ item.percentage }}%</span>
                                    </div>
                                </div>
                                <p v-else class="text-gray-500 text-center py-8">Platforma ma'lumoti mavjud emas</p>
                            </div>
                        </div>
                    </div>

                    <!-- Funnel Tab - Based on Meta Insights -->
                    <div v-show="activeTab === 'funnel' && hasMetaAccount && metaDataLoaded" class="space-y-6">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6">Reklama Voronkasi (Meta)</h3>
                            <div class="space-y-4">
                                <div class="relative">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700">Impressions (Ko'rishlar)</span>
                                        <div class="text-right">
                                            <span class="text-lg font-bold text-gray-900">{{ formatNumber(metaOverview?.current?.impressions) }}</span>
                                            <span class="text-sm text-gray-500 ml-2">(100%)</span>
                                        </div>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-8">
                                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-8 rounded-full" style="width: 100%"></div>
                                    </div>
                                </div>
                                <div class="relative">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700">Reach (Qamrov)</span>
                                        <div class="text-right">
                                            <span class="text-lg font-bold text-gray-900">{{ formatNumber(metaOverview?.current?.reach) }}</span>
                                            <span class="text-sm text-gray-500 ml-2">({{ ((metaOverview?.current?.reach || 0) / (metaOverview?.current?.impressions || 1) * 100).toFixed(1) }}%)</span>
                                        </div>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-8">
                                        <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-8 rounded-full" :style="{ width: ((metaOverview?.current?.reach || 0) / (metaOverview?.current?.impressions || 1) * 100) + '%' }"></div>
                                    </div>
                                </div>
                                <div class="relative">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700">Clicks (Kliklar)</span>
                                        <div class="text-right">
                                            <span class="text-lg font-bold text-gray-900">{{ formatNumber(metaOverview?.current?.clicks) }}</span>
                                            <span class="text-sm text-gray-500 ml-2">(CTR: {{ formatPercent(metaOverview?.current?.ctr) }})</span>
                                        </div>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-8">
                                        <div class="bg-gradient-to-r from-green-500 to-green-600 h-8 rounded-full" :style="{ width: Math.min((metaOverview?.current?.ctr || 0) * 10, 100) + '%' }"></div>
                                    </div>
                                </div>
                                <div class="relative">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700">Conversions</span>
                                        <div class="text-right">
                                            <span class="text-lg font-bold text-gray-900">{{ formatNumber(metaOverview?.current?.conversions || 0) }}</span>
                                        </div>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-8">
                                        <div class="bg-gradient-to-r from-orange-500 to-orange-600 h-8 rounded-full" :style="{ width: Math.min(((metaOverview?.current?.conversions || 0) / (metaOverview?.current?.clicks || 1) * 100), 100) + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Churn Tab - Based on Campaign Performance -->
                    <div v-show="activeTab === 'churn' && hasMetaAccount && metaDataLoaded" class="space-y-6">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Kampaniya Samaradorlik Tahlili</h3>
                            <p class="text-gray-600 mb-6">Reklama kampaniyalari holati va samaradorligi</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                                <h4 class="text-sm font-medium text-green-800 mb-2">Faol Kampaniyalar</h4>
                                <p class="text-3xl font-bold text-green-600">{{ metaCampaigns.filter(c => c.status === 'ACTIVE').length }}</p>
                                <p class="text-sm text-green-600 mt-1">Ishlayotgan</p>
                            </div>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                                <h4 class="text-sm font-medium text-yellow-800 mb-2">To'xtatilgan</h4>
                                <p class="text-3xl font-bold text-yellow-600">{{ metaCampaigns.filter(c => c.status === 'PAUSED').length }}</p>
                                <p class="text-sm text-yellow-600 mt-1">Pauzada</p>
                            </div>
                            <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                                <h4 class="text-sm font-medium text-red-800 mb-2">Past Samaradorlik</h4>
                                <p class="text-3xl font-bold text-red-600">{{ metaCampaigns.filter(c => (c.ctr || 0) < 1).length }}</p>
                                <p class="text-sm text-red-600 mt-1">CTR < 1%</p>
                            </div>
                        </div>

                        <!-- Low Performing Campaigns -->
                        <div v-if="metaCampaigns.filter(c => (c.ctr || 0) < 1).length" class="bg-white rounded-xl shadow-sm border border-gray-200">
                            <div class="p-6 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Past Samaradorlikdagi Kampaniyalar</h3>
                                <p class="text-sm text-gray-500">CTR 1% dan past - optimallashtirish tavsiya etiladi</p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kampaniya</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">CTR</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sarflangan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="campaign in metaCampaigns.filter(c => (c.ctr || 0) < 1)" :key="campaign.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ campaign.name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 text-right font-medium">{{ formatPercent(campaign.ctr) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(campaign.spend, selectedMetaAccount?.currency) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Meta Ads Tab -->
                    <div v-show="activeTab === 'meta'" class="space-y-6">
                        <!-- Error Message -->
                        <div v-if="metaError" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-red-700">{{ metaError }}</p>
                                <button @click="metaError = null" class="ml-auto text-red-500 hover:text-red-700">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- State 1: Not Connected - Show Connect UI -->
                        <div v-if="!isMetaConnected" class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                            <div class="w-24 h-24 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                                <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-4">Meta Ads Integratsiyasi</h2>
                            <p class="text-gray-600 mb-6 max-w-lg mx-auto">
                                Facebook va Instagram reklama hisoblaringizni ulang. Ulanganingizdan so'ng quyidagi imkoniyatlarga ega bo'lasiz:
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-2xl mx-auto mb-8">
                                <div class="bg-blue-50 rounded-lg p-4">
                                    <svg class="w-8 h-8 text-blue-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    <p class="text-sm font-medium text-blue-900">Kampaniya Statistikasi</p>
                                </div>
                                <div class="bg-purple-50 rounded-lg p-4">
                                    <svg class="w-8 h-8 text-purple-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <p class="text-sm font-medium text-purple-900">Auditoriya Tahlili</p>
                                </div>
                                <div class="bg-green-50 rounded-lg p-4">
                                    <svg class="w-8 h-8 text-green-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    <p class="text-sm font-medium text-green-900">AI Tavsiyalar</p>
                                </div>
                            </div>
                            <button @click="connectMeta" :disabled="metaConnecting"
                                class="px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl hover:from-blue-700 hover:to-purple-700 disabled:opacity-50 text-lg font-semibold inline-flex items-center gap-3 shadow-lg transition-all">
                                <svg v-if="!metaConnecting" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                <svg v-else class="animate-spin w-6 h-6" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ metaConnecting ? 'Ulanmoqda...' : 'Facebook/Instagram bilan Ulash' }}
                            </button>
                            <p class="text-xs text-gray-500 mt-4">
                                Ulanish orqali siz Meta Ads hisoblaringizga faqat o'qish huquqini berasiz
                            </p>
                        </div>

                        <!-- State 2: Connected but no account selected -->
                        <div v-else-if="isMetaConnected && !selectedMetaAccount && metaAdAccounts?.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                            <div class="text-center mb-8">
                                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <h2 class="text-xl font-bold text-gray-900 mb-2">Meta hisobi ulandi!</h2>
                                <p class="text-gray-600">Endi tahlil qilmoqchi bo'lgan reklama hisobingizni tanlang</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <button v-for="account in metaAdAccounts" :key="account.id" @click="selectMetaAccount(account.meta_account_id)"
                                    :disabled="metaLoading"
                                    class="p-6 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-all text-left group">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h3 class="font-semibold text-gray-900 group-hover:text-blue-700">{{ account.name }}</h3>
                                            <p class="text-sm text-gray-500 mt-1 font-mono">{{ account.meta_account_id }}</p>
                                        </div>
                                        <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                    <div class="flex items-center gap-2 mt-4">
                                        <span class="text-xs px-2 py-1 bg-gray-100 rounded font-medium">{{ account.currency }}</span>
                                        <span class="text-xs px-2 py-1 bg-gray-100 rounded">{{ account.timezone }}</span>
                                    </div>
                                </button>
                            </div>
                            <div class="mt-6 pt-6 border-t border-gray-200 text-center">
                                <button @click="disconnectMeta" class="text-red-600 hover:text-red-700 text-sm font-medium">
                                    Boshqa hisob bilan ulash
                                </button>
                            </div>
                        </div>

                        <!-- State 3: Connected with no accounts found - Need to load data -->
                        <div v-else-if="isMetaConnected && !selectedMetaAccount && (!metaAdAccounts || metaAdAccounts.length === 0)" class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                            <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-blue-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-3">Meta hisobi ulandi!</h2>
                            <p class="text-gray-600 mb-8 max-w-md mx-auto">
                                Meta hisobingiz muvaffaqiyatli ulandi. Endi reklama hisoblaringiz va kampaniya ma'lumotlarini yuklab olish uchun quyidagi tugmani bosing.
                            </p>
                            <button @click="syncMeta" :disabled="metaSyncing"
                                class="px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl hover:from-blue-700 hover:to-purple-700 disabled:opacity-50 text-lg font-semibold inline-flex items-center gap-3 shadow-lg transition-all">
                                <svg v-if="!metaSyncing" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <svg v-else class="animate-spin w-6 h-6" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ metaSyncing ? 'Yuklanmoqda...' : 'Ma\'lumotlarni Yuklash' }}
                            </button>
                            <p class="text-sm text-gray-500 mt-6">
                                Bu jarayon bir necha soniya davom etishi mumkin
                            </p>
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <button @click="disconnectMeta" class="text-red-600 hover:text-red-700 text-sm font-medium">
                                    Boshqa hisob bilan ulash
                                </button>
                            </div>
                        </div>

                        <!-- State 4: Connected with Account Selected - Show Dashboard -->
                        <div v-else-if="hasMetaAccount">
                            <!-- Account Header -->
                            <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-purple-700 rounded-xl p-6 mb-6 text-white shadow-lg">
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-blue-200 text-sm">Ulangan Hisob</p>
                                            <!-- Account Selector Dropdown -->
                                            <select v-if="metaAdAccounts?.length > 1"
                                                @change="selectMetaAccount($event.target.value)"
                                                class="text-xl font-bold bg-transparent border-none text-white focus:ring-0 cursor-pointer pr-8 -ml-1">
                                                <option v-for="acc in metaAdAccounts" :key="acc.meta_account_id"
                                                    :value="acc.meta_account_id"
                                                    :selected="acc.meta_account_id === selectedMetaAccount.meta_account_id"
                                                    class="text-gray-900 text-base">
                                                    {{ acc.name }}
                                                </option>
                                            </select>
                                            <h2 v-else class="text-xl font-bold">{{ selectedMetaAccount.name }}</h2>
                                            <p class="text-blue-200 text-sm mt-0.5">
                                                {{ selectedMetaAccount.meta_account_id }} • {{ selectedMetaAccount.currency }}
                                                <span v-if="selectedMetaAccount.last_sync_at" class="ml-2">• Oxirgi sync: {{ selectedMetaAccount.last_sync_at }}</span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <select v-model="metaDateRange" class="px-3 py-2 bg-white/10 border border-white/20 text-white rounded-lg text-sm focus:ring-2 focus:ring-white/30">
                                            <option v-for="option in dateRangeOptions" :key="option.value" :value="option.value" class="text-gray-900">
                                                {{ option.label }}
                                            </option>
                                        </select>
                                        <button @click="generateMetaAIInsights" :disabled="generatingMetaInsights || !metaDataLoaded"
                                            class="px-3 py-2 bg-white/10 border border-white/20 rounded-lg hover:bg-white/20 flex items-center gap-2 text-sm disabled:opacity-50">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                            </svg>
                                            AI Tahlil
                                        </button>
                                        <button @click="loadMetaData" :disabled="metaLoading"
                                            class="px-3 py-2 bg-white/10 border border-white/20 rounded-lg hover:bg-white/20 flex items-center gap-2 text-sm disabled:opacity-50">
                                            <svg :class="{ 'animate-spin': metaLoading }" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Yangilash
                                        </button>
                                        <button @click="disconnectMeta" class="px-3 py-2 bg-red-500/80 border border-red-400/50 rounded-lg hover:bg-red-500 text-sm">
                                            Uzish
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Loading State -->
                            <div v-if="metaLoading && !metaDataLoaded" class="bg-white rounded-xl shadow-sm border border-gray-200 p-12">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="animate-spin h-12 w-12 text-blue-600 mb-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <p class="text-gray-600">Meta ma'lumotlari yuklanmoqda...</p>
                                </div>
                            </div>

                            <!-- Data Loaded - Show Stats -->
                            <div v-else-if="metaDataLoaded">
                                <!-- Overview Stats Cards -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-500 uppercase">Sarflangan</p>
                                                <p class="text-xl font-bold text-gray-900">{{ formatCurrency(metaOverview?.current?.spend, selectedMetaAccount?.currency) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-500 uppercase">Ko'rishlar</p>
                                                <p class="text-xl font-bold text-gray-900">{{ formatNumber(metaOverview?.current?.impressions) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-500 uppercase">Kliklar</p>
                                                <p class="text-xl font-bold text-gray-900">{{ formatNumber(metaOverview?.current?.clicks) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-500 uppercase">CTR</p>
                                                <p class="text-xl font-bold text-gray-900">{{ formatPercent(metaOverview?.current?.ctr) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Campaigns Table -->
                                <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
                                    <div class="p-5 border-b border-gray-200 flex items-center justify-between">
                                        <h3 class="text-lg font-semibold text-gray-900">Kampaniyalar</h3>
                                        <span class="text-sm text-gray-500">{{ metaCampaigns.length }} ta kampaniya</span>
                                    </div>
                                    <div v-if="metaCampaigns.length > 0" class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kampaniya Nomi</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sarflangan</th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ko'rishlar</th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Kliklar</th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">CTR</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr v-for="campaign in metaCampaigns" :key="campaign.id" class="hover:bg-gray-50">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-medium text-gray-900">{{ campaign.name }}</div>
                                                        <div class="text-xs text-gray-500">{{ campaign.objective || 'Noma\'lum' }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span :class="getStatusColor(campaign.status)" class="px-2.5 py-1 text-xs font-semibold rounded-full">
                                                            {{ campaign.status }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                                        {{ formatCurrency(campaign.spend, selectedMetaAccount?.currency) }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-right">
                                                        {{ formatNumber(campaign.impressions) }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-right">
                                                        {{ formatNumber(campaign.clicks) }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                                        {{ formatPercent(campaign.ctr) }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div v-else class="p-8 text-center text-gray-500">
                                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <p>Hozircha kampaniyalar mavjud emas</p>
                                    </div>
                                </div>

                                <!-- Demographics & Placements -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <!-- Age Demographics -->
                                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Yosh Bo'yicha Auditoriya</h3>
                                        <div v-if="metaDemographics.age?.length" class="space-y-4">
                                            <div v-for="item in metaDemographics.age" :key="item.label">
                                                <div class="flex justify-between items-center mb-1.5">
                                                    <span class="text-sm font-medium text-gray-700">{{ item.label }}</span>
                                                    <div class="text-right">
                                                        <span class="text-sm font-semibold text-gray-900">{{ item.percentage }}%</span>
                                                        <span class="text-xs text-gray-500 ml-2">{{ formatCurrency(item.spend, selectedMetaAccount?.currency) }}</span>
                                                    </div>
                                                </div>
                                                <div class="w-full bg-gray-100 rounded-full h-2.5">
                                                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2.5 rounded-full transition-all" :style="{ width: item.percentage + '%' }"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="flex flex-col items-center justify-center py-8 text-gray-400">
                                            <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <p class="text-sm">Demografik ma'lumot mavjud emas</p>
                                        </div>
                                    </div>

                                    <!-- Platform Distribution -->
                                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Platforma Bo'yicha</h3>
                                        <div v-if="metaPlacements.platforms?.length" class="space-y-4">
                                            <div v-for="item in metaPlacements.platforms" :key="item.label" class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
                                                <div :class="item.label.toLowerCase() === 'facebook' ? 'bg-blue-100' : 'bg-gradient-to-br from-purple-100 to-pink-100'" class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0">
                                                    <svg v-if="item.label.toLowerCase() === 'facebook'" class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                                    </svg>
                                                    <svg v-else class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069z"/>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-semibold text-gray-900">{{ item.label }}</p>
                                                    <p class="text-sm text-gray-500">{{ formatCurrency(item.spend, selectedMetaAccount?.currency) }}</p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-lg font-bold text-gray-900">{{ item.percentage }}%</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="flex flex-col items-center justify-center py-8 text-gray-400">
                                            <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            <p class="text-sm">Platforma ma'lumoti mavjud emas</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- No Data Yet - Prompt to Load -->
                            <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Ma'lumotlarni yuklash</h3>
                                <p class="text-gray-600 mb-6 max-w-md mx-auto">
                                    Meta Ads hisobingiz ulandi. Kampaniya statistikasi va auditoriya ma'lumotlarini ko'rish uchun ma'lumotlarni yuklang.
                                </p>
                                <button @click="loadMetaData" :disabled="metaLoading"
                                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 inline-flex items-center gap-2">
                                    <svg :class="{ 'animate-spin': metaLoading }" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    {{ metaLoading ? 'Yuklanmoqda...' : 'Ma\'lumotlarni Yuklash' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
