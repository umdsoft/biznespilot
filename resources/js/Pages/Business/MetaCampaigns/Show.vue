<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import axios from 'axios';

const props = defineProps({
    campaign: Object,
    currency: {
        type: String,
        default: 'USD'
    },
    businessId: String
});

// State
const loading = ref(false);
const adSets = ref([]);
const ads = ref([]);
const activeTab = ref('adsets');
const insights = ref([]);

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

// Load Ad Sets
const loadAdSets = async () => {
    try {
        const response = await axios.get(`/business/api/meta-campaigns/${props.campaign.id}/adsets`, {
            params: { business_id: props.businessId }
        });
        if (response.data.success) {
            adSets.value = response.data.data;
        }
    } catch (error) {
        console.error('Error loading ad sets:', error);
    }
};

// Load Ads
const loadAds = async () => {
    try {
        const response = await axios.get(`/business/api/meta-campaigns/${props.campaign.id}/ads`, {
            params: { business_id: props.businessId }
        });
        if (response.data.success) {
            ads.value = response.data.data;
        }
    } catch (error) {
        console.error('Error loading ads:', error);
    }
};

// Summary stats
const summary = computed(() => ({
    adsets_count: adSets.value.length,
    ads_count: ads.value.length,
    active_adsets: adSets.value.filter(a => a.effective_status === 'ACTIVE').length,
    active_ads: ads.value.filter(a => a.effective_status === 'ACTIVE').length
}));

// Initialize
onMounted(async () => {
    loading.value = true;
    await Promise.all([loadAdSets(), loadAds()]);
    loading.value = false;
});
</script>

<template>
    <Head :title="`${campaign.name} - Kampaniya`" />

    <BusinessLayout title="Kampaniya tafsilotlari">
        <div class="space-y-6">
            <!-- Back Button & Header -->
            <div class="flex items-center gap-4">
                <Link href="/business/target-analysis" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span class="text-sm font-medium">Orqaga</span>
                </Link>
            </div>

            <!-- Campaign Header Card -->
            <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-2xl p-6 text-white shadow-xl">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <!-- Campaign Info -->
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-3">
                            <span :class="[getStatusColor(campaign.effective_status), 'px-3 py-1 text-sm font-semibold rounded-full']">
                                {{ formatStatus(campaign.effective_status) }}
                            </span>
                            <span class="px-3 py-1 bg-white/20 text-white text-sm font-medium rounded-full">
                                {{ formatObjective(campaign.objective) }}
                            </span>
                        </div>
                        <h1 class="text-2xl lg:text-3xl font-bold mb-2">{{ campaign.name }}</h1>
                        <p class="text-white/70 font-mono text-sm">ID: {{ campaign.meta_campaign_id }}</p>
                    </div>

                    <!-- Quick Stats -->
                    <div class="flex flex-wrap gap-4">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl px-5 py-3 min-w-[120px]">
                            <p class="text-white/60 text-xs uppercase tracking-wide mb-1">Ad Setlar</p>
                            <p class="text-2xl font-bold">{{ summary.adsets_count }}</p>
                            <p class="text-xs text-green-300">{{ summary.active_adsets }} faol</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl px-5 py-3 min-w-[120px]">
                            <p class="text-white/60 text-xs uppercase tracking-wide mb-1">Reklamalar</p>
                            <p class="text-2xl font-bold">{{ summary.ads_count }}</p>
                            <p class="text-xs text-green-300">{{ summary.active_ads }} faol</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                    <p class="text-xs font-medium text-gray-500 uppercase mb-1">Sarflangan</p>
                    <p class="text-xl font-bold text-gray-900">{{ formatCurrency(campaign.total_spend, currency) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                    <p class="text-xs font-medium text-gray-500 uppercase mb-1">Ko'rishlar</p>
                    <p class="text-xl font-bold text-gray-900">{{ formatNumber(campaign.total_impressions) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                    <p class="text-xs font-medium text-gray-500 uppercase mb-1">Qamrov</p>
                    <p class="text-xl font-bold text-gray-900">{{ formatNumber(campaign.total_reach) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                    <p class="text-xs font-medium text-gray-500 uppercase mb-1">Kliklar</p>
                    <p class="text-xl font-bold text-gray-900">{{ formatNumber(campaign.total_clicks) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                    <p class="text-xs font-medium text-gray-500 uppercase mb-1">CTR</p>
                    <p class="text-xl font-bold text-blue-600">{{ formatPercent(campaign.avg_ctr) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                    <p class="text-xs font-medium text-gray-500 uppercase mb-1">CPC</p>
                    <p class="text-xl font-bold text-gray-900">{{ formatCurrency(campaign.avg_cpc, currency) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                    <p class="text-xs font-medium text-gray-500 uppercase mb-1">{{ getResultLabel(campaign.objective) }}</p>
                    <p class="text-xl font-bold text-green-600">{{ formatNumber(campaign.total_conversions || 0) }}</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <!-- Tab Navigation -->
                <div class="border-b border-gray-200">
                    <nav class="flex px-6 gap-1 -mb-px">
                        <button @click="activeTab = 'adsets'"
                            :class="[
                                activeTab === 'adsets'
                                    ? 'border-blue-600 text-blue-600 bg-blue-50'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50',
                                'py-4 px-6 text-sm font-medium border-b-2 transition-all rounded-t-lg flex items-center gap-2'
                            ]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            Ad Setlar (Guruhlar)
                            <span class="bg-gray-100 text-gray-600 px-2.5 py-0.5 rounded-full text-xs font-semibold">
                                {{ adSets.length }}
                            </span>
                        </button>
                        <button @click="activeTab = 'ads'"
                            :class="[
                                activeTab === 'ads'
                                    ? 'border-blue-600 text-blue-600 bg-blue-50'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50',
                                'py-4 px-6 text-sm font-medium border-b-2 transition-all rounded-t-lg flex items-center gap-2'
                            ]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Reklamalar (Kreativlar)
                            <span class="bg-gray-100 text-gray-600 px-2.5 py-0.5 rounded-full text-xs font-semibold">
                                {{ ads.length }}
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Loading -->
                    <div v-if="loading" class="flex items-center justify-center py-16">
                        <div class="flex flex-col items-center gap-3">
                            <svg class="animate-spin h-10 w-10 text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <p class="text-gray-500 text-sm">Ma'lumotlar yuklanmoqda...</p>
                        </div>
                    </div>

                    <!-- Ad Sets Tab -->
                    <div v-else-if="activeTab === 'adsets'">
                        <div v-if="adSets.length > 0" class="space-y-4">
                            <div v-for="adset in adSets" :key="adset.id"
                                class="bg-gray-50 hover:bg-gray-100 rounded-xl p-5 transition-all border border-gray-100 hover:border-gray-200 hover:shadow-sm">
                                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                    <!-- AdSet Info -->
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h3 class="font-semibold text-gray-900 text-lg">{{ adset.name }}</h3>
                                            <span :class="[getStatusColor(adset.effective_status), 'px-2.5 py-0.5 text-xs font-semibold rounded-full']">
                                                {{ formatStatus(adset.effective_status) }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 font-mono mb-3">ID: {{ adset.meta_adset_id }}</p>

                                        <!-- Targeting Info -->
                                        <div class="flex flex-wrap items-center gap-3 text-sm">
                                            <div v-if="adset.targeting_summary" class="flex items-center gap-1.5 text-gray-600 bg-white px-3 py-1.5 rounded-lg border border-gray-200">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                <span>{{ adset.targeting_summary }}</span>
                                            </div>
                                            <div v-if="adset.daily_budget" class="flex items-center gap-1.5 text-gray-600 bg-white px-3 py-1.5 rounded-lg border border-gray-200">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>Kunlik: {{ formatCurrency(adset.daily_budget, currency) }}</span>
                                            </div>
                                            <div v-if="adset.lifetime_budget" class="flex items-center gap-1.5 text-gray-600 bg-white px-3 py-1.5 rounded-lg border border-gray-200">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                <span>Umumiy: {{ formatCurrency(adset.lifetime_budget, currency) }}</span>
                                            </div>
                                            <div v-if="adset.optimization_goal" class="flex items-center gap-1.5 text-gray-600 bg-white px-3 py-1.5 rounded-lg border border-gray-200">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                                </svg>
                                                <span>{{ adset.optimization_goal }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- AdSet Stats -->
                                    <div class="grid grid-cols-3 lg:grid-cols-6 gap-3 lg:gap-4">
                                        <div class="text-center lg:text-right">
                                            <p class="text-xs text-gray-500 mb-1">Sarflangan</p>
                                            <p class="text-base font-bold text-gray-900">{{ formatCurrency(adset.total_spend, currency) }}</p>
                                        </div>
                                        <div class="text-center lg:text-right">
                                            <p class="text-xs text-gray-500 mb-1">Ko'rishlar</p>
                                            <p class="text-base font-semibold text-gray-700">{{ formatNumber(adset.total_impressions) }}</p>
                                        </div>
                                        <div class="text-center lg:text-right">
                                            <p class="text-xs text-gray-500 mb-1">Kliklar</p>
                                            <p class="text-base font-semibold text-gray-700">{{ formatNumber(adset.total_clicks) }}</p>
                                        </div>
                                        <div class="text-center lg:text-right">
                                            <p class="text-xs text-gray-500 mb-1">CTR</p>
                                            <p class="text-base font-semibold text-blue-600">{{ formatPercent(adset.avg_ctr) }}</p>
                                        </div>
                                        <div class="text-center lg:text-right">
                                            <p class="text-xs text-gray-500 mb-1">CPC</p>
                                            <p class="text-base font-semibold text-gray-700">{{ formatCurrency(adset.avg_cpc, currency) }}</p>
                                        </div>
                                        <div class="text-center lg:text-right">
                                            <p class="text-xs text-gray-500 mb-1">Natija</p>
                                            <p class="text-base font-bold text-green-600">{{ formatNumber(adset.total_conversions || 0) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-16">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">Ad Set topilmadi</h3>
                            <p class="text-gray-500 text-sm">Bu kampaniyada hali Ad Set mavjud emas</p>
                        </div>
                    </div>

                    <!-- Ads Tab -->
                    <div v-else-if="activeTab === 'ads'">
                        <div v-if="ads.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            <div v-for="ad in ads" :key="ad.id"
                                class="bg-gray-50 hover:bg-gray-100 rounded-xl overflow-hidden transition-all border border-gray-100 hover:border-gray-200 hover:shadow-md">
                                <!-- Ad Preview -->
                                <div class="relative aspect-video bg-gray-200">
                                    <img v-if="ad.thumbnail_url" :src="ad.thumbnail_url" :alt="ad.name"
                                        class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <!-- Status Badge -->
                                    <span :class="[getStatusColor(ad.effective_status), 'absolute top-3 right-3 px-2.5 py-0.5 text-xs font-semibold rounded-full shadow-sm']">
                                        {{ formatStatus(ad.effective_status) }}
                                    </span>
                                </div>

                                <!-- Ad Content -->
                                <div class="p-4">
                                    <h3 class="font-semibold text-gray-900 truncate mb-1">{{ ad.name }}</h3>
                                    <p class="text-xs text-gray-500 font-mono mb-3">ID: {{ ad.meta_ad_id }}</p>

                                    <!-- Creative Info -->
                                    <div v-if="ad.title || ad.body" class="mb-3 p-3 bg-white rounded-lg border border-gray-200">
                                        <p v-if="ad.title" class="font-medium text-gray-900 text-sm mb-1">{{ ad.title }}</p>
                                        <p v-if="ad.body" class="text-gray-600 text-xs line-clamp-2">{{ ad.body }}</p>
                                    </div>

                                    <!-- Call to Action -->
                                    <div v-if="ad.call_to_action" class="mb-3">
                                        <span class="inline-flex items-center px-2.5 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-lg">
                                            {{ ad.call_to_action }}
                                        </span>
                                    </div>

                                    <!-- Ad Stats -->
                                    <div class="grid grid-cols-3 gap-2 pt-3 border-t border-gray-200">
                                        <div>
                                            <p class="text-xs text-gray-500">Sarflangan</p>
                                            <p class="font-semibold text-gray-900 text-sm">{{ formatCurrency(ad.total_spend, currency) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Ko'rishlar</p>
                                            <p class="font-medium text-gray-700 text-sm">{{ formatNumber(ad.total_impressions) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Kliklar</p>
                                            <p class="font-medium text-gray-700 text-sm">{{ formatNumber(ad.total_clicks) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">CTR</p>
                                            <p class="font-semibold text-blue-600 text-sm">{{ formatPercent(ad.avg_ctr) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">CPC</p>
                                            <p class="font-medium text-gray-700 text-sm">{{ formatCurrency(ad.avg_cpc, currency) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Natija</p>
                                            <p class="font-bold text-green-600 text-sm">{{ formatNumber(ad.total_conversions || 0) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-16">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">Reklama topilmadi</h3>
                            <p class="text-gray-500 text-sm">Bu kampaniyada hali reklama mavjud emas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
