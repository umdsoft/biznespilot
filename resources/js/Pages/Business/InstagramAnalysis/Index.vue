<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import axios from 'axios';

const props = defineProps({
    business: Object,
    metaIntegration: Object,
    instagramAccounts: Array,
    selectedAccount: Object,
});

const page = usePage();
const loading = ref(false);
const syncing = ref(false);
const activeTab = ref('insights');
const dateRange = ref('last_30d');
const dataLoaded = ref(false);
const error = ref(null);

// Data states
const overview = ref(null);
const mediaPerformance = ref(null);
const reelsAnalytics = ref(null);
const engagement = ref(null);
const audience = ref(null);
const hashtags = ref(null);
const growthTrend = ref([]);
const contentComparison = ref(null);
const aiInsights = ref(null);
const generatingInsights = ref(false);
const showAIModal = ref(false);
const showHealthModal = ref(false);

// Business Insights data
const businessInsights = ref(null);
const contentWinners = ref(null);
const growthDrivers = ref(null);
const viralAnalysis = ref(null);
const insightsLoading = ref(false);

// View mode: 'cards' or 'table'
const viewMode = ref('cards');
const winnersViewMode = ref('cards');
const viralViewMode = ref('cards');
const timesViewMode = ref('cards');

// Filtering & sorting
const mediaSearch = ref('');
const mediaSortBy = ref('engagement');
const mediaTypeFilter = ref('all');
const currentPage = ref(1);
const itemsPerPage = 12;

const dateRangeOptions = [
    { value: 'last_7d', label: 'Oxirgi 7 kun' },
    { value: 'last_14d', label: 'Oxirgi 14 kun' },
    { value: 'last_30d', label: 'Oxirgi 30 kun' },
    { value: 'last_90d', label: 'Oxirgi 3 oy' },
];

const tabs = [
    { id: 'insights', label: 'Tavsiyalar', icon: 'lightbulb' },
    { id: 'overview', label: 'Umumiy', icon: 'chart' },
    { id: 'chatbot', label: 'Chatbot', icon: 'chat', isLink: true, href: '/business/instagram-chatbot' },
    { id: 'audience', label: 'Auditoriya', icon: 'users' },
    { id: 'engagement', label: 'Faollik', icon: 'heart' },
];

// Computed
const isConnected = computed(() => props.metaIntegration?.status === 'connected');
const hasAccount = computed(() => isConnected.value && props.selectedAccount);

// Account health score (0-100)
const accountHealth = computed(() => {
    if (!overview.value?.account) return 0;

    let score = 0;
    const acc = overview.value.account;
    const eng = acc.engagement_rate || 0;
    const followers = acc.followers_count || 0;

    // Engagement rate score (max 40 points)
    if (eng >= 5) score += 40;
    else if (eng >= 3) score += 32;
    else if (eng >= 2) score += 24;
    else if (eng >= 1) score += 16;
    else score += 8;

    // Follower quality (max 20 points)
    if (followers >= 10000) score += 20;
    else if (followers >= 5000) score += 16;
    else if (followers >= 1000) score += 12;
    else score += 8;

    // Reach efficiency (max 20 points)
    const reach = overview.value.current?.reach || 0;
    const reachRate = followers > 0 ? (reach / followers) * 100 : 0;
    if (reachRate >= 50) score += 20;
    else if (reachRate >= 30) score += 16;
    else if (reachRate >= 15) score += 12;
    else score += 6;

    // Content consistency (max 20 points)
    const mediaCount = acc.media_count || 0;
    if (mediaCount >= 100) score += 20;
    else if (mediaCount >= 50) score += 16;
    else if (mediaCount >= 20) score += 12;
    else score += 6;

    return Math.min(100, score);
});

const healthStatus = computed(() => {
    const score = accountHealth.value;
    if (score >= 80) return { label: 'Ajoyib', color: 'green', emoji: '🌟', desc: 'Akkountingiz zo\'r ishlayapti!' };
    if (score >= 60) return { label: 'Yaxshi', color: 'blue', emoji: '👍', desc: 'Yaxshi yo\'ldasiz, biroz yaxshilash mumkin' };
    if (score >= 40) return { label: 'O\'rtacha', color: 'yellow', emoji: '📈', desc: 'Rivojlanish imkoniyati bor' };
    return { label: 'Boshlang\'ich', color: 'orange', emoji: '🚀', desc: 'Keling, birga o\'stiramiz!' };
});

// Engagement explanation for business owners
const engagementExplanation = computed(() => {
    const rate = overview.value?.account?.engagement_rate || 0;
    if (rate >= 5) return { status: 'excellent', text: 'Juda yaxshi! Auditoriyangiz kontentingizni juda yaxshi qabul qilyapti.', color: 'green' };
    if (rate >= 3) return { status: 'good', text: 'Yaxshi! O\'rtachadan yuqori. Shunday davom eting.', color: 'blue' };
    if (rate >= 1) return { status: 'average', text: 'O\'rtacha. Ko\'proq savol so\'rang va auditoriya bilan gaplashing.', color: 'yellow' };
    return { status: 'low', text: 'Past. Kontentni qiziqarliroq qilish kerak. Videolar va reelslarni ko\'paytiring.', color: 'red' };
});

// Filtered media
const filteredMedia = computed(() => {
    if (!mediaPerformance.value?.all_media) return [];

    let media = [...mediaPerformance.value.all_media];

    if (mediaTypeFilter.value !== 'all') {
        media = media.filter(m => m.media_type === mediaTypeFilter.value);
    }

    if (mediaSearch.value) {
        const search = mediaSearch.value.toLowerCase();
        media = media.filter(m => (m.caption || '').toLowerCase().includes(search));
    }

    media.sort((a, b) => {
        switch (mediaSortBy.value) {
            case 'engagement': return (b.engagement_rate || 0) - (a.engagement_rate || 0);
            case 'likes': return (b.like_count || 0) - (a.like_count || 0);
            case 'comments': return (b.comments_count || 0) - (a.comments_count || 0);
            case 'reach': return (b.reach || 0) - (a.reach || 0);
            case 'date': return new Date(b.timestamp) - new Date(a.timestamp);
            default: return 0;
        }
    });

    return media;
});

const totalMediaPages = computed(() => Math.ceil(filteredMedia.value.length / itemsPerPage));
const paginatedMedia = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return filteredMedia.value.slice(start, start + itemsPerPage);
});

// Format helpers
const formatNumber = (num) => {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return new Intl.NumberFormat('uz-UZ').format(num || 0);
};
const formatPercent = (value) => (value || 0).toFixed(2) + '%';
const formatChange = (value) => {
    const v = value || 0;
    if (v > 0) return `+${v.toFixed(1)}%`;
    return `${v.toFixed(1)}%`;
};
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' });
};

// Load all data
const loadAllData = async () => {
    if (!hasAccount.value) return;

    loading.value = true;
    error.value = null;

    try {
        await Promise.all([
            loadOverview(),
            loadMediaPerformance(),
            loadReelsAnalytics(),
            loadEngagement(),
            loadAudience(),
            loadHashtags(),
            loadGrowthTrend(),
            loadContentComparison(),
            loadBusinessInsights(),
        ]);
        dataLoaded.value = true;
    } catch (e) {
        console.error('Error loading data:', e);
        error.value = 'Ma\'lumotlarni yuklashda xatolik yuz berdi';
    } finally {
        loading.value = false;
    }
};

const loadOverview = async () => {
    const response = await axios.get('/business/api/instagram-analysis/overview', {
        params: { business_id: props.business.id, period: dateRange.value }
    });
    overview.value = response.data;
};

const loadMediaPerformance = async () => {
    const response = await axios.get('/business/api/instagram-analysis/media-performance', {
        params: { business_id: props.business.id, period: dateRange.value }
    });
    mediaPerformance.value = response.data;
};

const loadReelsAnalytics = async () => {
    const response = await axios.get('/business/api/instagram-analysis/reels-analytics', {
        params: { business_id: props.business.id, period: dateRange.value }
    });
    reelsAnalytics.value = response.data;
};

const loadEngagement = async () => {
    const response = await axios.get('/business/api/instagram-analysis/engagement', {
        params: { business_id: props.business.id, period: dateRange.value }
    });
    engagement.value = response.data;
};

const loadAudience = async () => {
    const response = await axios.get('/business/api/instagram-analysis/audience', {
        params: { business_id: props.business.id }
    });
    audience.value = response.data;
};

const loadHashtags = async () => {
    const response = await axios.get('/business/api/instagram-analysis/hashtags', {
        params: { business_id: props.business.id, limit: 20 }
    });
    hashtags.value = response.data;
};

const loadGrowthTrend = async () => {
    const response = await axios.get('/business/api/instagram-analysis/growth-trend', {
        params: { business_id: props.business.id, days: 30 }
    });
    growthTrend.value = response.data.trend || [];
};

const loadContentComparison = async () => {
    const response = await axios.get('/business/api/instagram-analysis/content-comparison', {
        params: { business_id: props.business.id, period: dateRange.value }
    });
    contentComparison.value = response.data;
};

// Business Insights loaders
const loadBusinessInsights = async () => {
    insightsLoading.value = true;
    try {
        const [insightsRes, winnersRes, growthRes, viralRes] = await Promise.all([
            axios.get('/business/api/instagram-analysis/business-insights', {
                params: { business_id: props.business.id }
            }),
            axios.get('/business/api/instagram-analysis/content-winners', {
                params: { business_id: props.business.id }
            }),
            axios.get('/business/api/instagram-analysis/growth-drivers', {
                params: { business_id: props.business.id }
            }),
            axios.get('/business/api/instagram-analysis/viral-analysis', {
                params: { business_id: props.business.id }
            }),
        ]);
        businessInsights.value = insightsRes.data;
        contentWinners.value = winnersRes.data;
        growthDrivers.value = growthRes.data;
        viralAnalysis.value = viralRes.data;
    } catch (e) {
        console.error('Error loading business insights:', e);
    } finally {
        insightsLoading.value = false;
    }
};

const generateAIInsights = async () => {
    generatingInsights.value = true;
    showAIModal.value = true;
    try {
        const response = await axios.post('/business/api/instagram-analysis/ai-insights', {
            business_id: props.business.id,
            period: dateRange.value,
        });
        aiInsights.value = response.data;
    } catch (e) {
        console.error('Error generating insights:', e);
        aiInsights.value = { success: false, error: 'AI tahlil yaratishda xatolik' };
    } finally {
        generatingInsights.value = false;
    }
};

// Sync error details
const syncError = ref(null);
const needsReconnect = ref(false);
const reconnecting = ref(false);

// Reconnect Meta with Instagram permissions
const reconnectMeta = async () => {
    reconnecting.value = true;
    try {
        const response = await axios.get('/business/target-analysis/meta/auth-url', {
            params: { business_id: props.business.id }
        });
        if (response.data.url) {
            window.location.href = response.data.url;
        }
    } catch (e) {
        console.error('Error getting auth URL:', e);
        error.value = 'Meta ulash URL olishda xatolik';
        reconnecting.value = false;
    }
};

const syncData = async () => {
    syncing.value = true;
    error.value = null;
    syncError.value = null;
    needsReconnect.value = false;

    try {
        const response = await axios.post('/business/instagram-analysis/sync', {
            business_id: props.business.id,
        });

        if (response.data.success) {
            // Reload page to get fresh data including new accounts
            window.location.reload();
        } else {
            error.value = response.data.error || 'Sinxronizatsiya xatosi';
            syncError.value = response.data;
        }
    } catch (e) {
        console.error('Error syncing:', e);
        const errorData = e.response?.data || {};
        error.value = errorData.error || 'Sinxronizatsiya xatosi yuz berdi';
        syncError.value = errorData;
        needsReconnect.value = errorData.needs_reconnect || false;
    } finally {
        syncing.value = false;
    }
};

const selectAccount = async (accountId) => {
    loading.value = true;
    try {
        await axios.post('/business/instagram-analysis/select-account', {
            business_id: props.business.id,
            account_id: accountId,
        });
        window.location.reload();
    } catch (e) {
        console.error('Error selecting account:', e);
    } finally {
        loading.value = false;
    }
};

const getMediaTypeIcon = (type) => {
    switch (type) {
        case 'VIDEO': return '🎬';
        case 'CAROUSEL_ALBUM': return '📷';
        case 'IMAGE': return '🖼️';
        default: return '📱';
    }
};

const getEngagementColor = (rate) => {
    if (rate >= 5) return 'text-green-600';
    if (rate >= 3) return 'text-blue-600';
    if (rate >= 1) return 'text-yellow-600';
    return 'text-red-600';
};

const getEngagementBg = (rate) => {
    if (rate >= 5) return 'bg-green-100 dark:bg-green-900/30 border-green-200 dark:border-green-800';
    if (rate >= 3) return 'bg-blue-100 dark:bg-blue-900/30 border-blue-200 dark:border-blue-800';
    if (rate >= 1) return 'bg-yellow-100 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-800';
    return 'bg-red-100 dark:bg-red-900/30 border-red-200 dark:border-red-800';
};

// Watch date range changes
watch(dateRange, () => {
    if (hasAccount.value && dataLoaded.value) {
        loadAllData();
    }
});

watch([mediaSearch, mediaTypeFilter, mediaSortBy], () => {
    currentPage.value = 1;
});

onMounted(() => {
    if (hasAccount.value) {
        loadAllData();
    }
});
</script>

<template>
    <Head title="Instagram Tahlili" />
    <BusinessLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-pink-100 dark:bg-pink-900/50 rounded-xl flex items-center justify-center">
                                <svg class="w-7 h-7 text-pink-600 dark:text-pink-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Instagram Tahlili</h1>
                                <p class="text-gray-500 dark:text-gray-400 text-sm mt-0.5">Akkountingiz qanday ishlayotganini ko'ring</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <select v-if="hasAccount" v-model="dateRange"
                                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-pink-500 focus:border-transparent cursor-pointer">
                                <option v-for="opt in dateRangeOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                            <button v-if="hasAccount" @click="syncData" :disabled="syncing"
                                class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 flex items-center gap-2 text-sm transition-colors">
                                <svg :class="{ 'animate-spin': syncing }" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Yangilash
                            </button>
                            <button v-if="hasAccount && dataLoaded" @click="generateAIInsights"
                                class="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 flex items-center gap-2 text-sm font-medium transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                AI Maslahat
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-4 sm:px-6 lg:px-8 py-6">
                <!-- Not Connected State -->
                <div v-if="!isConnected" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 lg:p-12">
                    <div class="max-w-md mx-auto text-center">
                        <div class="mx-auto w-16 h-16 bg-pink-100 dark:bg-pink-900/50 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-pink-600 dark:text-pink-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">Instagram ulanmagan</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">
                            Instagram akkountingizni ulang va postlaringiz qanchalik yaxshi ishlayotganini ko'ring
                        </p>
                        <a href="/business/target-analysis"
                            class="inline-flex items-center px-5 py-2.5 bg-pink-600 text-white rounded-lg hover:bg-pink-700 font-medium transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            Instagram ulash
                        </a>
                    </div>
                </div>

                <!-- Connected but no Instagram accounts synced -->
                <div v-else-if="isConnected && !hasAccount" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 lg:p-12">
                    <div class="max-w-xl mx-auto text-center">
                        <div class="mx-auto w-16 h-16 bg-amber-100 dark:bg-amber-900/50 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">Instagram ma'lumotlari topilmadi</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">
                            Meta ulangan, lekin Instagram akkountlar hali sinxronizatsiya qilinmagan.
                            Tugmani bosing va Instagram ma'lumotlaringizni oling.
                        </p>

                        <!-- Detailed Error message -->
                        <div v-if="error" class="mb-6 text-left">
                            <div class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-red-100 dark:bg-red-900/50 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-semibold text-red-800 dark:text-red-300">Xatolik yuz berdi</p>
                                        <p class="text-red-700 dark:text-red-400 text-sm mt-1">{{ error }}</p>

                                        <!-- Permission issue - needs reconnect -->
                                        <div v-if="syncError?.needs_reconnect" class="mt-4 p-3 bg-white dark:bg-gray-800 rounded-lg border border-red-100 dark:border-red-900">
                                            <p class="font-medium text-gray-900 dark:text-gray-100 text-sm mb-2">Yechim:</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                                Meta integratsiyasini qayta ulashingiz kerak.
                                            </p>
                                            <button @click="reconnectMeta" :disabled="reconnecting"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors">
                                                <svg v-if="reconnecting" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                                </svg>
                                                {{ reconnecting ? 'Ulanmoqda...' : 'Meta qayta ulash' }}
                                            </button>
                                        </div>

                                        <!-- No pages -->
                                        <div v-else-if="syncError?.no_pages" class="mt-4 p-3 bg-white dark:bg-gray-800 rounded-lg border border-red-100 dark:border-red-900">
                                            <p class="font-medium text-gray-900 dark:text-gray-100 text-sm mb-2">Yechim:</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Facebook Business sahifasi yarating va Instagram akkountingizni unga ulang.</p>
                                            <a href="https://business.facebook.com" target="_blank" class="mt-3 inline-flex items-center gap-2 text-blue-600 dark:text-blue-400 text-sm font-medium hover:underline">
                                                Facebook Business'ga o'tish
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        </div>

                                        <!-- No Instagram connected to pages -->
                                        <div v-else-if="syncError?.no_instagram" class="mt-4 p-3 bg-white dark:bg-gray-800 rounded-lg border border-red-100 dark:border-red-900">
                                            <p class="font-medium text-gray-900 dark:text-gray-100 text-sm mb-2">Yechim:</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Facebook sahifangizga Instagram Business akkountini ulang:</p>
                                            <ol class="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-decimal list-inside">
                                                <li>Facebook sahifangizga kiring</li>
                                                <li>Settings → Linked Accounts → Instagram</li>
                                                <li>Instagram akkountingizni ulang</li>
                                            </ol>
                                            <p v-if="syncError?.pages" class="mt-3 text-xs text-gray-500 dark:text-gray-500">
                                                Mavjud sahifalar: {{ syncError.pages.join(', ') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button @click="syncData" :disabled="syncing"
                            class="inline-flex items-center px-5 py-2.5 bg-pink-600 text-white rounded-lg hover:bg-pink-700 font-medium transition-colors disabled:opacity-50">
                            <svg :class="{ 'animate-spin': syncing }" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ syncing ? 'Sinxronizatsiya qilinmoqda...' : 'Instagram sinxronizatsiya qilish' }}
                        </button>

                        <p v-if="syncing" class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                            Bu bir necha daqiqa davom etishi mumkin...
                        </p>
                    </div>
                </div>

                <!-- Account Selector -->
                <div v-else-if="instagramAccounts?.length > 1" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Akkount tanlang:</label>
                    <div class="flex flex-wrap gap-3">
                        <button v-for="account in instagramAccounts" :key="account.id"
                            @click="selectAccount(account.id)"
                            :class="[
                                'flex items-center gap-3 px-5 py-3 rounded-xl border-2 transition-all',
                                account.is_primary
                                    ? 'border-pink-500 bg-pink-50 dark:bg-pink-900/30'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-pink-300 dark:hover:border-pink-500 hover:bg-pink-50/50 dark:hover:bg-pink-900/20'
                            ]">
                            <img v-if="account.profile_picture_url" :src="account.profile_picture_url"
                                :alt="account.username" class="w-12 h-12 rounded-full ring-2 ring-white dark:ring-gray-700" />
                            <div class="text-left">
                                <div class="font-bold text-gray-900 dark:text-gray-100">@{{ account.username }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ formatNumber(account.followers_count) }} follower</div>
                            </div>
                            <svg v-if="account.is_primary" class="w-6 h-6 text-pink-500 dark:text-pink-400 ml-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading && !dataLoaded" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <div class="relative w-16 h-16 mx-auto mb-6">
                        <div class="absolute inset-0 border-4 border-pink-200 dark:border-pink-900 rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-pink-500 dark:border-pink-400 border-t-transparent rounded-full animate-spin"></div>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">Ma'lumotlar yuklanmoqda...</p>
                </div>

                <!-- Main Content -->
                <div v-else-if="hasAccount && dataLoaded" class="space-y-6">

                    <!-- Account Health Card - Biznes egasi uchun eng muhim! -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="p-6">
                            <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                                <!-- Profile -->
                                <div class="flex items-center gap-5">
                                    <div class="relative">
                                        <img v-if="selectedAccount?.profile_picture_url"
                                            :src="selectedAccount.profile_picture_url"
                                            :alt="selectedAccount?.username"
                                            class="w-20 h-20 rounded-full ring-4 ring-pink-100 dark:ring-pink-900/50" />
                                        <div class="absolute -bottom-1 -right-1 w-7 h-7 bg-pink-500 dark:bg-pink-600 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">@{{ overview.account?.username }}</h2>
                                        <p class="text-gray-500 dark:text-gray-400">{{ overview.account?.name }}</p>
                                    </div>
                                </div>

                                <!-- Health Score -->
                                <div class="flex-1 lg:text-center">
                                    <button @click="showHealthModal = true" class="inline-flex items-center gap-3 px-6 py-3 rounded-xl transition-all hover:scale-105"
                                        :class="{
                                            'bg-green-100 dark:bg-green-900/50 hover:bg-green-200 dark:hover:bg-green-900/70': healthStatus.color === 'green',
                                            'bg-blue-100 dark:bg-blue-900/50 hover:bg-blue-200 dark:hover:bg-blue-900/70': healthStatus.color === 'blue',
                                            'bg-yellow-100 dark:bg-yellow-900/50 hover:bg-yellow-200 dark:hover:bg-yellow-900/70': healthStatus.color === 'yellow',
                                            'bg-orange-100 dark:bg-orange-900/50 hover:bg-orange-200 dark:hover:bg-orange-900/70': healthStatus.color === 'orange',
                                        }">
                                        <span class="text-3xl">{{ healthStatus.emoji }}</span>
                                        <div class="text-left">
                                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Akkount holati</div>
                                            <div class="text-xl font-bold" :class="{
                                                'text-green-700 dark:text-green-400': healthStatus.color === 'green',
                                                'text-blue-700 dark:text-blue-400': healthStatus.color === 'blue',
                                                'text-yellow-700 dark:text-yellow-400': healthStatus.color === 'yellow',
                                                'text-orange-700 dark:text-orange-400': healthStatus.color === 'orange',
                                            }">{{ healthStatus.label }} ({{ accountHealth }}/100)</div>
                                        </div>
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Key Numbers -->
                                <div class="grid grid-cols-3 gap-6 lg:gap-8">
                                    <div class="text-center">
                                        <div class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(overview.account?.followers_count) }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Follower</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(overview.account?.media_count) }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Post</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl lg:text-3xl font-bold" :class="getEngagementColor(overview.account?.engagement_rate)">
                                            {{ overview.account?.engagement_rate || 0 }}%
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Engagement</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Engagement Explanation Card - Biznes egasiga tushunarli tilda -->
                    <div class="rounded-xl p-5 border-2" :class="getEngagementBg(overview?.account?.engagement_rate)">
                        <div class="flex items-start gap-4">
                            <div class="text-3xl">
                                {{ engagementExplanation.status === 'excellent' ? '🌟' :
                                   engagementExplanation.status === 'good' ? '👍' :
                                   engagementExplanation.status === 'average' ? '📊' : '💡' }}
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-1">Engagement Rate nima?</h3>
                                <p class="text-gray-700 dark:text-gray-300">
                                    Bu sizning postlaringizga necha foiz odam munosabat bildirganini ko'rsatadi (like, izoh, saqlash).
                                    <strong>{{ engagementExplanation.text }}</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats - Muhim raqamlar -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:border-pink-300 dark:hover:border-pink-700 transition-all">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">Yetib borgan odamlar</span>
                                <span class="text-2xl">👥</span>
                            </div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(overview?.current?.reach) }}</div>
                            <div class="flex items-center gap-1 mt-2">
                                <span :class="(overview?.change?.reach || 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" class="text-sm font-semibold">
                                    {{ formatChange(overview?.change?.reach || 0) }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">o'tgan davrga nisbatan</span>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:border-pink-300 dark:hover:border-pink-700 transition-all">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">Ko'rishlar soni</span>
                                <span class="text-2xl">👁️</span>
                            </div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(overview?.current?.impressions) }}</div>
                            <div class="flex items-center gap-1 mt-2">
                                <span :class="(overview?.change?.impressions || 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" class="text-sm font-semibold">
                                    {{ formatChange(overview?.change?.impressions || 0) }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">o'tgan davrga nisbatan</span>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:border-pink-300 dark:hover:border-pink-700 transition-all">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">Likelar</span>
                                <span class="text-2xl">❤️</span>
                            </div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(overview?.current?.total_likes) }}</div>
                            <div class="flex items-center gap-1 mt-2">
                                <span :class="(overview?.change?.total_likes || 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" class="text-sm font-semibold">
                                    {{ formatChange(overview?.change?.total_likes || 0) }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">o'tgan davrga nisbatan</span>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:border-pink-300 dark:hover:border-pink-700 transition-all">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">Izohlar</span>
                                <span class="text-2xl">💬</span>
                            </div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(overview?.current?.total_comments) }}</div>
                            <div class="flex items-center gap-1 mt-2">
                                <span :class="(overview?.change?.total_comments || 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" class="text-sm font-semibold">
                                    {{ formatChange(overview?.change?.total_comments || 0) }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">o'tgan davrga nisbatan</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                            <nav class="flex overflow-x-auto px-2">
                                <template v-for="tab in tabs" :key="tab.id">
                                    <a v-if="tab.isLink" :href="tab.href"
                                        class="px-6 py-4 text-sm font-semibold border-b-2 whitespace-nowrap transition-all border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/50 dark:hover:bg-gray-800/50 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        {{ tab.label }}
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                    <button v-else @click="activeTab = tab.id"
                                        :class="[
                                            'px-6 py-4 text-sm font-semibold border-b-2 whitespace-nowrap transition-all',
                                            activeTab === tab.id
                                                ? 'border-pink-500 text-pink-600 dark:text-pink-400 bg-white dark:bg-gray-800'
                                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/50 dark:hover:bg-gray-800/50'
                                        ]">
                                        {{ tab.label }}
                                    </button>
                                </template>
                            </nav>
                        </div>

                        <div class="p-6">
                            <!-- Insights Tab - Biznes uchun eng muhim! -->
                            <div v-if="activeTab === 'insights'" class="space-y-8">
                                <!-- Loading -->
                                <div v-if="insightsLoading" class="flex items-center justify-center py-12">
                                    <div class="animate-spin w-8 h-8 border-4 border-pink-500 border-t-transparent rounded-full"></div>
                                    <span class="ml-3 text-gray-500">Tahlil qilinmoqda...</span>
                                </div>

                                <template v-else>
                                    <!-- Quick Answers Section -->
                                    <div class="bg-pink-600 dark:bg-pink-700 rounded-xl p-8 text-white">
                                        <h2 class="text-2xl font-bold mb-2">Sizning biznes savollaringizga javoblar</h2>
                                        <p class="text-pink-100 dark:text-pink-200 mb-6">Instagram ma'lumotlaringizga asoslangan amaliy tavsiyalar</p>

                                        <div class="grid gap-4">
                                            <!-- Strategy Summary -->
                                            <div v-if="businessInsights?.best_posting_strategy?.strategy_summary" class="bg-white/10 rounded-xl p-5">
                                                <div class="flex items-start gap-4">
                                                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                                                        <span class="text-2xl">📅</span>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-bold text-lg">Qachon post qilish kerak?</h3>
                                                        <p class="text-pink-100 dark:text-pink-200 mt-1">{{ businessInsights.best_posting_strategy.strategy_summary }}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Growth Insight -->
                                            <div v-if="growthDrivers?.insight" class="bg-white/10 rounded-xl p-5">
                                                <div class="flex items-start gap-4">
                                                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                                                        <span class="text-2xl">📈</span>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-bold text-lg">Qaysi kontent follower olib keladi?</h3>
                                                        <p class="text-pink-100 dark:text-pink-200 mt-1">{{ growthDrivers.insight }}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Viral Formula -->
                                            <div v-if="viralAnalysis?.viral_formula" class="bg-white/10 rounded-xl p-5">
                                                <div class="flex items-start gap-4">
                                                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                                                        <span class="text-2xl">🚀</span>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-bold text-lg">Viral bo'lish uchun nima qilish kerak?</h3>
                                                        <p class="text-pink-100 dark:text-pink-200 mt-1">{{ viralAnalysis.viral_formula }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Content Winners -->
                                    <div v-if="contentWinners">
                                        <div class="flex items-center justify-between mb-6">
                                            <div>
                                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Eng yaxshi kontentlaringiz</h3>
                                                <p class="text-gray-500 dark:text-gray-400">Har bir kategoriyada g'olib postlar - bu formatlarni takrorlang!</p>
                                            </div>
                                            <!-- View Toggle -->
                                            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-xl p-1">
                                                <button @click="winnersViewMode = 'cards'"
                                                    :class="winnersViewMode === 'cards' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                                    class="px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                                    </svg>
                                                    Kartochka
                                                </button>
                                                <button @click="winnersViewMode = 'table'"
                                                    :class="winnersViewMode === 'table' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                                    class="px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                    Jadval
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Cards View -->
                                        <div v-if="winnersViewMode === 'cards'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                            <template v-for="(winner, key) in contentWinners" :key="key">
                                                <div v-if="winner" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:border-pink-300 dark:hover:border-pink-600 transition-all group">
                                                    <div class="relative aspect-square bg-gray-100">
                                                        <img v-if="winner.thumbnail_url" :src="winner.thumbnail_url" class="w-full h-full object-cover" />
                                                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent">
                                                            <div class="absolute top-3 left-3">
                                                                <span class="px-3 py-1 bg-yellow-400 text-yellow-900 text-xs font-bold rounded-full">
                                                                    {{ winner.title }}
                                                                </span>
                                                            </div>
                                                            <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                                                                <div class="text-2xl font-bold">{{ formatNumber(winner.reach) }}</div>
                                                                <div class="text-sm text-gray-300">reach</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="p-4">
                                                        <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400 mb-3">
                                                            <span>❤️ {{ formatNumber(winner.likes) }}</span>
                                                            <span>💬 {{ formatNumber(winner.comments) }}</span>
                                                            <span>📌 {{ formatNumber(winner.saves) }}</span>
                                                        </div>
                                                        <p v-if="winner.why_it_worked" class="text-sm text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30 rounded-lg p-2">
                                                            {{ winner.why_it_worked }}
                                                        </p>
                                                        <a v-if="winner.permalink" :href="winner.permalink" target="_blank"
                                                            class="mt-3 inline-flex items-center text-sm text-pink-600 dark:text-pink-400 hover:text-pink-700 dark:hover:text-pink-300 font-medium">
                                                            Instagram'da ko'rish
                                                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                            </svg>
                                                        </a>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Table View -->
                                        <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                                            <div class="overflow-x-auto">
                                                <table class="w-full">
                                                    <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                                                        <tr>
                                                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kontent</th>
                                                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kategoriya</th>
                                                            <th class="text-right px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reach</th>
                                                            <th class="text-right px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Engagement</th>
                                                            <th class="text-right px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Likes</th>
                                                            <th class="text-right px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Comments</th>
                                                            <th class="text-right px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Saves</th>
                                                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sana</th>
                                                            <th class="text-center px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Link</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                        <template v-for="(winner, key) in contentWinners" :key="key">
                                                            <tr v-if="winner" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                                <td class="px-6 py-4">
                                                                    <div class="flex items-center gap-3">
                                                                        <img v-if="winner.thumbnail_url" :src="winner.thumbnail_url"
                                                                            class="w-12 h-12 rounded-lg object-cover" />
                                                                        <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center" v-else>
                                                                            <span class="text-gray-400">📷</span>
                                                                        </div>
                                                                        <div class="max-w-xs">
                                                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                                                                {{ winner.type === 'REELS' ? '🎬 Reels' : winner.type === 'CAROUSEL_ALBUM' ? '📷 Carousel' : '🖼️ Post' }}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td class="px-6 py-4">
                                                                    <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300 text-xs font-semibold rounded-full">
                                                                        {{ winner.title }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-6 py-4 text-right">
                                                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(winner.reach) }}</span>
                                                                </td>
                                                                <td class="px-6 py-4 text-right">
                                                                    <span class="text-sm font-medium" :class="winner.engagement_rate > 3 ? 'text-green-600 dark:text-green-400' : winner.engagement_rate > 1 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400'">
                                                                        {{ winner.engagement_rate }}%
                                                                    </span>
                                                                </td>
                                                                <td class="px-6 py-4 text-right">
                                                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ formatNumber(winner.likes) }}</span>
                                                                </td>
                                                                <td class="px-6 py-4 text-right">
                                                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ formatNumber(winner.comments) }}</span>
                                                                </td>
                                                                <td class="px-6 py-4 text-right">
                                                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ formatNumber(winner.saves) }}</span>
                                                                </td>
                                                                <td class="px-6 py-4">
                                                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ winner.posted_at }}</span>
                                                                </td>
                                                                <td class="px-6 py-4 text-center">
                                                                    <a v-if="winner.permalink" :href="winner.permalink" target="_blank"
                                                                        class="inline-flex items-center justify-center w-8 h-8 bg-pink-100 dark:bg-pink-900/50 text-pink-600 dark:text-pink-400 rounded-lg hover:bg-pink-200 dark:hover:bg-pink-900 transition-colors">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                                        </svg>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Best Posting Times -->
                                    <div v-if="businessInsights?.best_posting_strategy">
                                        <div class="flex items-center justify-between mb-6">
                                            <div>
                                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Eng yaxshi post qilish vaqtlari</h3>
                                                <p class="text-gray-500 dark:text-gray-400">Sizning auditoriyangiz qachon eng faol?</p>
                                            </div>
                                            <!-- View Toggle -->
                                            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-xl p-1">
                                                <button @click="timesViewMode = 'cards'"
                                                    :class="timesViewMode === 'cards' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                                    class="px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                                    </svg>
                                                    Kartochka
                                                </button>
                                                <button @click="timesViewMode = 'table'"
                                                    :class="timesViewMode === 'table' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                                    class="px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                    Jadval
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Cards View -->
                                        <div v-if="timesViewMode === 'cards'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                            <!-- Best Days -->
                                            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                                    <span class="text-2xl">📆</span> Eng yaxshi kunlar
                                                </h3>
                                                <div class="space-y-3">
                                                    <div v-for="(day, index) in businessInsights.best_posting_strategy.best_days?.slice(0, 3)" :key="index"
                                                        class="flex items-center justify-between p-3 rounded-xl"
                                                        :class="index === 0 ? 'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800' : 'bg-gray-50 dark:bg-gray-700'">
                                                        <div class="flex items-center gap-3">
                                                            <span v-if="index === 0" class="text-xl">🥇</span>
                                                            <span v-else-if="index === 1" class="text-xl">🥈</span>
                                                            <span v-else class="text-xl">🥉</span>
                                                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ day.name }}</span>
                                                        </div>
                                                        <div class="text-right">
                                                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(day.avg_reach) }} reach</div>
                                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ day.posts_count }} post</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Best Hours -->
                                            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                                    <span class="text-2xl">🕐</span> Eng yaxshi soatlar
                                                </h3>
                                                <div class="space-y-3">
                                                    <div v-for="(hour, index) in businessInsights.best_posting_strategy.best_hours?.slice(0, 3)" :key="index"
                                                        class="flex items-center justify-between p-3 rounded-xl"
                                                        :class="index === 0 ? 'bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800' : 'bg-gray-50 dark:bg-gray-700'">
                                                        <div class="flex items-center gap-3">
                                                            <span v-if="index === 0" class="text-xl">🥇</span>
                                                            <span v-else-if="index === 1" class="text-xl">🥈</span>
                                                            <span v-else class="text-xl">🥉</span>
                                                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ hour.hour }}</span>
                                                        </div>
                                                        <div class="text-right">
                                                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(hour.avg_reach) }} reach</div>
                                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ hour.posts_count }} post</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Table View -->
                                        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                            <!-- Days Table -->
                                            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                                                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                                                    <h3 class="font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                                        <span class="text-xl">📆</span> Hafta kunlari bo'yicha statistika
                                                    </h3>
                                                </div>
                                                <table class="w-full">
                                                    <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700">
                                                        <tr>
                                                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Kun</th>
                                                            <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Postlar</th>
                                                            <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">O'rt. Reach</th>
                                                            <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">O'rt. Engagement</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                        <tr v-for="(day, index) in businessInsights.best_posting_strategy.best_days" :key="index"
                                                            class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                                            :class="index === 0 ? 'bg-green-50 dark:bg-green-900/30' : ''">
                                                            <td class="px-6 py-3">
                                                                <div class="flex items-center gap-2">
                                                                    <span v-if="index === 0" class="text-sm">🥇</span>
                                                                    <span v-else-if="index === 1" class="text-sm">🥈</span>
                                                                    <span v-else-if="index === 2" class="text-sm">🥉</span>
                                                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ day.name }}</span>
                                                                </div>
                                                            </td>
                                                            <td class="px-6 py-3 text-right text-sm text-gray-600 dark:text-gray-400">{{ day.posts_count }}</td>
                                                            <td class="px-6 py-3 text-right text-sm font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(day.avg_reach) }}</td>
                                                            <td class="px-6 py-3 text-right text-sm text-gray-600 dark:text-gray-400">{{ day.avg_engagement }}%</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Hours Table -->
                                            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                                                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                                                    <h3 class="font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                                        <span class="text-xl">🕐</span> Soatlar bo'yicha statistika
                                                    </h3>
                                                </div>
                                                <div class="max-h-80 overflow-y-auto">
                                                    <table class="w-full">
                                                        <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700 sticky top-0">
                                                            <tr>
                                                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Soat</th>
                                                                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Postlar</th>
                                                                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">O'rt. Reach</th>
                                                                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">O'rt. Engagement</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                            <tr v-for="(hour, index) in businessInsights.best_posting_strategy.best_hours" :key="index"
                                                                class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                                                :class="index === 0 ? 'bg-blue-50 dark:bg-blue-900/30' : ''">
                                                                <td class="px-6 py-3">
                                                                    <div class="flex items-center gap-2">
                                                                        <span v-if="index === 0" class="text-sm">🥇</span>
                                                                        <span v-else-if="index === 1" class="text-sm">🥈</span>
                                                                        <span v-else-if="index === 2" class="text-sm">🥉</span>
                                                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ hour.hour }}</span>
                                                                    </div>
                                                                </td>
                                                                <td class="px-6 py-3 text-right text-sm text-gray-600 dark:text-gray-400">{{ hour.posts_count }}</td>
                                                                <td class="px-6 py-3 text-right text-sm font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(hour.avg_reach) }}</td>
                                                                <td class="px-6 py-3 text-right text-sm text-gray-600 dark:text-gray-400">{{ hour.avg_engagement }}%</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Viral Posts -->
                                    <div v-if="viralAnalysis?.viral_posts?.length > 0">
                                        <div class="flex items-center justify-between mb-6">
                                            <div>
                                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Viral bo'lgan postlaringiz</h3>
                                                <p class="text-gray-500 dark:text-gray-400">Bu postlar followerlaringiz sonidan ko'proq odamga yetib borgan</p>
                                            </div>
                                            <!-- View Toggle -->
                                            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-xl p-1">
                                                <button @click="viralViewMode = 'cards'"
                                                    :class="viralViewMode === 'cards' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                                    class="px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                                    </svg>
                                                    Kartochka
                                                </button>
                                                <button @click="viralViewMode = 'table'"
                                                    :class="viralViewMode === 'table' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                                    class="px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                    Jadval
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Cards View -->
                                        <div v-if="viralViewMode === 'cards'" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                            <a v-for="post in viralAnalysis.viral_posts.slice(0, 10)" :key="post.id"
                                                :href="post.permalink" target="_blank"
                                                class="group relative aspect-square bg-gray-100 dark:bg-gray-700 rounded-xl overflow-hidden hover:ring-2 hover:ring-pink-500 transition-all">
                                                <img v-if="post.thumbnail_url" :src="post.thumbnail_url" class="w-full h-full object-cover" />
                                                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-all">
                                                    <div class="absolute bottom-0 left-0 right-0 p-3 text-white">
                                                        <div class="text-lg font-bold">{{ formatNumber(post.reach) }}</div>
                                                        <div class="text-xs text-gray-300">{{ post.viral_ratio }}x viral</div>
                                                    </div>
                                                </div>
                                                <div class="absolute top-2 right-2">
                                                    <span class="px-2 py-1 bg-red-500 text-white text-xs font-bold rounded-full">
                                                        🔥 {{ post.viral_ratio }}x
                                                    </span>
                                                </div>
                                            </a>
                                        </div>

                                        <!-- Table View -->
                                        <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                                            <div class="overflow-x-auto">
                                                <table class="w-full">
                                                    <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                                                        <tr>
                                                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kontent</th>
                                                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Turi</th>
                                                            <th class="text-right px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reach</th>
                                                            <th class="text-right px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Viral Ratio</th>
                                                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Caption</th>
                                                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sana</th>
                                                            <th class="text-center px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Link</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                        <tr v-for="post in viralAnalysis.viral_posts" :key="post.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                            <td class="px-6 py-4">
                                                                <div class="flex items-center gap-3">
                                                                    <img v-if="post.thumbnail_url" :src="post.thumbnail_url"
                                                                        class="w-12 h-12 rounded-lg object-cover" />
                                                                    <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center" v-else>
                                                                        <span class="text-gray-400">📷</span>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="px-6 py-4">
                                                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                                    {{ post.type === 'REELS' ? '🎬 Reels' : post.type === 'CAROUSEL_ALBUM' ? '📷 Carousel' : '🖼️ Post' }}
                                                                </span>
                                                            </td>
                                                            <td class="px-6 py-4 text-right">
                                                                <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(post.reach) }}</span>
                                                            </td>
                                                            <td class="px-6 py-4 text-right">
                                                                <span class="px-2 py-1 bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 text-xs font-bold rounded-full">
                                                                    🔥 {{ post.viral_ratio }}x
                                                                </span>
                                                            </td>
                                                            <td class="px-6 py-4">
                                                                <span class="text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate block">{{ post.caption }}</span>
                                                            </td>
                                                            <td class="px-6 py-4">
                                                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ post.posted_at }}</span>
                                                            </td>
                                                            <td class="px-6 py-4 text-center">
                                                                <a v-if="post.permalink" :href="post.permalink" target="_blank"
                                                                    class="inline-flex items-center justify-center w-8 h-8 bg-pink-100 dark:bg-pink-900/50 text-pink-600 dark:text-pink-400 rounded-lg hover:bg-pink-200 dark:hover:bg-pink-900 transition-colors">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                                    </svg>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Content Recommendations -->
                                    <div v-if="businessInsights?.content_recommendations" class="bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800 p-6">
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                            <span class="text-2xl">💡</span> Kontent tavsiyalari
                                        </h3>

                                        <div class="space-y-4">
                                            <div v-for="(item, index) in businessInsights.content_recommendations.action_items" :key="index"
                                                class="flex items-start gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                                                    :class="item.priority === 'high' ? 'bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400' : 'bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400'">
                                                    {{ index + 1 }}
                                                </div>
                                                <div>
                                                    <span v-if="item.priority === 'high'" class="text-xs font-bold text-red-600 dark:text-red-400 uppercase">Muhim</span>
                                                    <p class="text-gray-800 dark:text-gray-200 font-medium">{{ item.action }}</p>
                                                </div>
                                            </div>

                                            <!-- Top Hashtags -->
                                            <div v-if="businessInsights.content_recommendations.top_hashtags?.length" class="mt-4 p-4 bg-white dark:bg-gray-800 rounded-xl">
                                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Eng samarali hashtaglar:</h4>
                                                <div class="flex flex-wrap gap-2">
                                                    <span v-for="tag in businessInsights.content_recommendations.top_hashtags.slice(0, 8)" :key="tag.hashtag"
                                                        class="px-3 py-1 bg-pink-100 dark:bg-pink-900/50 text-pink-700 dark:text-pink-300 rounded-full text-sm font-medium">
                                                        {{ tag.hashtag }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Performance Trend -->
                                    <div v-if="businessInsights?.performance_trends" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Sizning trend</h3>

                                        <div class="flex items-center gap-4 p-4 rounded-xl"
                                            :class="{
                                                'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800': businessInsights.performance_trends.overall_trend === 'growing',
                                                'bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800': businessInsights.performance_trends.overall_trend === 'declining',
                                                'bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600': businessInsights.performance_trends.overall_trend === 'stable'
                                            }">
                                            <span class="text-4xl">
                                                {{ businessInsights.performance_trends.overall_trend === 'growing' ? '📈' :
                                                   businessInsights.performance_trends.overall_trend === 'declining' ? '📉' : '➡️' }}
                                            </span>
                                            <div>
                                                <div class="font-bold text-lg"
                                                    :class="{
                                                        'text-green-700 dark:text-green-400': businessInsights.performance_trends.overall_trend === 'growing',
                                                        'text-red-700 dark:text-red-400': businessInsights.performance_trends.overall_trend === 'declining',
                                                        'text-gray-700 dark:text-gray-300': businessInsights.performance_trends.overall_trend === 'stable'
                                                    }">
                                                    {{ businessInsights.performance_trends.overall_trend === 'growing' ? 'O\'sish' :
                                                       businessInsights.performance_trends.overall_trend === 'declining' ? 'Pasayish' : 'Barqaror' }}
                                                </div>
                                                <p class="text-gray-600 dark:text-gray-400">{{ businessInsights.performance_trends.trend_insight }}</p>
                                            </div>
                                        </div>

                                        <!-- Change metrics -->
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                                            <div v-for="(value, key) in businessInsights.performance_trends.changes" :key="key"
                                                class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                                <div class="text-lg font-bold"
                                                    :class="value > 0 ? 'text-green-600 dark:text-green-400' : value < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400'">
                                                    {{ value > 0 ? '+' : '' }}{{ value }}%
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ key === 'avg_reach' ? 'Reach' :
                                                       key === 'avg_engagement' ? 'Engagement' :
                                                       key === 'total_posts' ? 'Postlar' :
                                                       key === 'total_likes' ? 'Likelar' : key }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Audience Profile -->
                                    <div v-if="businessInsights?.audience_insights" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                            <span class="text-2xl">👥</span> Auditoriyangiz kim?
                                        </h3>

                                        <div class="p-4 bg-purple-50 dark:bg-purple-900/30 rounded-xl border border-purple-200 dark:border-purple-800 mb-4">
                                            <p class="text-purple-800 dark:text-purple-300 font-medium">{{ businessInsights.audience_insights.target_audience_profile }}</p>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div v-for="(insight, key) in businessInsights.audience_insights.insights" :key="key"
                                                class="p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                                <p class="text-gray-700 dark:text-gray-300">{{ insight }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Overview Tab -->
                            <div v-if="activeTab === 'overview'" class="space-y-8">
                                <!-- Content Type Comparison -->
                                <div v-if="contentComparison">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">Qaysi turdagi kontent yaxshi ishlaydi?</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mb-6">Har xil turdagi postlaringiz qanday natija berayotganini ko'ring</p>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                        <div v-for="(data, type) in contentComparison" :key="type"
                                            class="bg-gray-50 dark:bg-gray-700 rounded-xl p-6 border border-gray-200 dark:border-gray-600 hover:border-pink-300 dark:hover:border-pink-600 transition-all">
                                            <div class="flex items-center justify-between mb-5">
                                                <div class="flex items-center gap-3">
                                                    <span class="text-2xl">{{ type === 'Reels' ? '🎬' : type === 'Carousel' ? '📷' : '🖼️' }}</span>
                                                    <span class="font-bold text-gray-900 dark:text-gray-100 text-lg">{{ type }}</span>
                                                </div>
                                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-600 px-3 py-1.5 rounded-full">{{ data.count }} ta</span>
                                            </div>
                                            <div class="space-y-4">
                                                <div>
                                                    <div class="flex justify-between items-center mb-1">
                                                        <span class="text-sm text-gray-500 dark:text-gray-400">O'rtacha Reach</span>
                                                        <span class="font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(data.avg_reach) }}</span>
                                                    </div>
                                                    <div class="text-xs text-gray-400 dark:text-gray-500">Necha odamga yetib borgan</div>
                                                </div>
                                                <div>
                                                    <div class="flex justify-between items-center mb-1">
                                                        <span class="text-sm text-gray-500 dark:text-gray-400">Engagement</span>
                                                        <span class="font-bold" :class="getEngagementColor(data.avg_engagement_rate)">{{ data.avg_engagement_rate }}%</span>
                                                    </div>
                                                    <div class="text-xs text-gray-400 dark:text-gray-500">Faollik darajasi</div>
                                                </div>
                                                <div>
                                                    <div class="flex justify-between items-center mb-1">
                                                        <span class="text-sm text-gray-500 dark:text-gray-400">Jami Like</span>
                                                        <span class="font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(data.total_likes) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Best Performing Content -->
                                <div v-if="mediaPerformance?.top_posts?.length">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">Eng yaxshi ishlab postlar</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mb-6">Bu postlar eng ko'p like va izoh olgan - shu tarzda davom eting!</p>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div v-for="(post, index) in mediaPerformance.top_posts.slice(0, 4)" :key="post.id"
                                            class="group relative rounded-xl overflow-hidden aspect-square bg-gray-100 dark:bg-gray-700 hover:ring-2 hover:ring-pink-500 transition-all">
                                            <img v-if="post.thumbnail_url || post.media_url" :src="post.thumbnail_url || post.media_url"
                                                class="w-full h-full object-cover" />
                                            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-all">
                                                <div class="absolute top-3 left-3">
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-white/90 rounded-lg text-xs font-bold text-gray-900">
                                                        #{{ index + 1 }} {{ getMediaTypeIcon(post.media_type) }}
                                                    </span>
                                                </div>
                                                <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                                                    <div class="flex items-center gap-4 text-sm font-semibold">
                                                        <span class="flex items-center gap-1">❤️ {{ formatNumber(post.like_count) }}</span>
                                                        <span class="flex items-center gap-1">💬 {{ formatNumber(post.comments_count) }}</span>
                                                    </div>
                                                    <div class="mt-2 flex items-center gap-2">
                                                        <div class="px-2 py-0.5 rounded-full text-xs font-medium"
                                                            :class="post.engagement_rate >= 5 ? 'bg-green-500' : post.engagement_rate >= 3 ? 'bg-blue-500' : 'bg-yellow-500'">
                                                            {{ post.engagement_rate }}% engagement
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Best times to post -->
                                <div v-if="engagement?.best_posting_days?.length || engagement?.best_posting_hours?.length"
                                    class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-6 border border-purple-200 dark:border-purple-800">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">Qachon post qilish kerak?</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mb-6">Auditoriyangiz eng faol bo'lgan vaqtlar</p>
                                    <div class="grid md:grid-cols-2 gap-6">
                                        <div v-if="engagement?.best_posting_days?.length">
                                            <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Eng yaxshi kunlar:</h4>
                                            <div class="flex flex-wrap gap-2">
                                                <span v-for="(day, i) in engagement.best_posting_days" :key="day"
                                                    class="px-4 py-2 rounded-xl font-medium"
                                                    :class="i === 0 ? 'bg-purple-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'">
                                                    {{ i === 0 ? '⭐ ' : '' }}{{ day }}
                                                </span>
                                            </div>
                                        </div>
                                        <div v-if="engagement?.best_posting_hours?.length">
                                            <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Eng faol soatlar:</h4>
                                            <div class="flex flex-wrap gap-2">
                                                <span v-for="(hour, i) in engagement.best_posting_hours" :key="hour"
                                                    class="px-4 py-2 rounded-xl font-medium"
                                                    :class="i === 0 ? 'bg-pink-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'">
                                                    {{ hour }}:00
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Reels Tab -->
                            <div v-if="activeTab === 'reels'" class="space-y-6">
                                <div v-if="reelsAnalytics?.summary" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="bg-pink-600 dark:bg-pink-700 rounded-xl p-6 text-white">
                                        <div class="text-4xl font-bold">{{ formatNumber(reelsAnalytics.summary.total_plays) }}</div>
                                        <div class="text-pink-100 dark:text-pink-200 text-sm mt-2">Jami ko'rishlar</div>
                                        <div class="text-xs text-pink-200 dark:text-pink-300 mt-1">Reelslaringizni necha marta ko'rishgan</div>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                                        <div class="text-4xl font-bold text-gray-900 dark:text-gray-100">{{ reelsAnalytics.summary.total_reels }}</div>
                                        <div class="text-gray-500 dark:text-gray-400 text-sm mt-2">Reelslar soni</div>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                                        <div class="text-4xl font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(reelsAnalytics.summary.avg_plays) }}</div>
                                        <div class="text-gray-500 dark:text-gray-400 text-sm mt-2">O'rtacha ko'rish</div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">Har bir reels uchun</div>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                                        <div class="text-4xl font-bold" :class="getEngagementColor(reelsAnalytics.summary.avg_engagement_rate)">
                                            {{ reelsAnalytics.summary.avg_engagement_rate }}%
                                        </div>
                                        <div class="text-gray-500 dark:text-gray-400 text-sm mt-2">Engagement</div>
                                    </div>
                                </div>

                                <!-- Top Reels by Reach -->
                                <div v-if="reelsAnalytics?.most_viewed?.length">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">Eng ko'p yetib borgan Reelslar</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mb-6">Bu reelslar eng ko'p odamga yetib borgan - shu tarzda davom eting!</p>
                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                        <div v-for="(reel, index) in reelsAnalytics.most_viewed" :key="reel.id"
                                            class="group relative rounded-xl overflow-hidden aspect-[9/16] bg-gray-100 dark:bg-gray-700 hover:ring-2 hover:ring-pink-500 transition-all">
                                            <img v-if="reel.thumbnail_url" :src="reel.thumbnail_url" class="w-full h-full object-cover" />
                                            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-all">
                                                <div class="absolute top-3 right-3">
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-pink-500 rounded-lg text-xs font-bold text-white">
                                                        #{{ index + 1 }}
                                                    </span>
                                                </div>
                                                <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                                                    <div class="text-2xl font-bold">{{ formatNumber(reel.reach) }}</div>
                                                    <div class="text-sm text-gray-300">reach</div>
                                                    <div class="flex items-center gap-3 text-sm mt-3 font-medium">
                                                        <span>❤️ {{ formatNumber(reel.like_count) }}</span>
                                                        <span>💬 {{ formatNumber(reel.comments_count) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- All Reels Grid -->
                                <div v-if="reelsAnalytics?.all_reels?.length" class="mt-8">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">Barcha Reelslar</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mb-6">Jami {{ reelsAnalytics.summary?.total_reels || 0 }} ta reels</p>
                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                                        <a v-for="reel in reelsAnalytics.all_reels.slice(0, 20)" :key="reel.id"
                                            :href="reel.permalink" target="_blank"
                                            class="group relative rounded-xl overflow-hidden aspect-[9/16] bg-gray-100 dark:bg-gray-700 hover:ring-2 hover:ring-pink-500 transition-all">
                                            <img v-if="reel.thumbnail_url" :src="reel.thumbnail_url" class="w-full h-full object-cover" />
                                            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-all">
                                                <div class="absolute bottom-0 left-0 right-0 p-3 text-white">
                                                    <div class="flex items-center gap-2 text-sm font-medium">
                                                        <span>👁️ {{ formatNumber(reel.reach) }}</span>
                                                        <span>❤️ {{ formatNumber(reel.like_count) }}</span>
                                                    </div>
                                                    <div class="text-xs text-gray-300 mt-1">{{ formatDate(reel.timestamp) }}</div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <div v-else-if="!reelsAnalytics?.summary?.total_reels" class="text-center py-12 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <div class="text-5xl mb-4">🎬</div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Hali Reels yo'q</h3>
                                    <p class="text-gray-500 dark:text-gray-400">Reels joylaganingizda bu yerda statistika ko'rinadi</p>
                                </div>
                            </div>

                            <!-- Posts Tab -->
                            <div v-if="activeTab === 'posts'" class="space-y-6">
                                <!-- Filters -->
                                <div class="flex flex-wrap items-center gap-4 pb-5 border-b border-gray-100 dark:border-gray-700">
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Turi:</label>
                                        <select v-model="mediaTypeFilter" class="text-sm border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 dark:text-gray-200 focus:bg-white dark:focus:bg-gray-600">
                                            <option value="all">Barchasi</option>
                                            <option value="IMAGE">Rasmlar</option>
                                            <option value="VIDEO">Videolar</option>
                                            <option value="CAROUSEL_ALBUM">Carousel</option>
                                        </select>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Tartiblash:</label>
                                        <select v-model="mediaSortBy" class="text-sm border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 dark:text-gray-200 focus:bg-white dark:focus:bg-gray-600">
                                            <option value="engagement">Engagement</option>
                                            <option value="likes">Likelar</option>
                                            <option value="comments">Izohlar</option>
                                            <option value="reach">Reach</option>
                                            <option value="date">Sana</option>
                                        </select>
                                    </div>
                                    <div class="flex-1 min-w-[200px]">
                                        <input v-model="mediaSearch" type="text" placeholder="Post qidirish..."
                                            class="w-full text-sm border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 dark:text-gray-200 focus:bg-white dark:focus:bg-gray-600 px-4 py-2" />
                                    </div>
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-3 py-1.5 rounded-full">{{ filteredMedia.length }} ta post</span>
                                </div>

                                <!-- Media Grid -->
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                    <div v-for="post in paginatedMedia" :key="post.id"
                                        class="group relative rounded-xl overflow-hidden aspect-square bg-gray-100 dark:bg-gray-700 hover:ring-2 hover:ring-pink-500 transition-all">
                                        <img v-if="post.thumbnail_url || post.media_url" :src="post.thumbnail_url || post.media_url"
                                            class="w-full h-full object-cover" />
                                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-all">
                                            <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                                                <p class="text-xs line-clamp-2 mb-2 text-gray-200">{{ post.caption }}</p>
                                                <div class="flex items-center gap-3 text-sm font-medium">
                                                    <span>❤️ {{ formatNumber(post.like_count) }}</span>
                                                    <span>💬 {{ formatNumber(post.comments_count) }}</span>
                                                </div>
                                                <div class="mt-2 flex items-center gap-2">
                                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                                        :class="post.engagement_rate >= 5 ? 'bg-green-500' : post.engagement_rate >= 3 ? 'bg-blue-500' : 'bg-yellow-500'">
                                                        {{ post.engagement_rate }}%
                                                    </span>
                                                    <span class="text-xs text-gray-300">{{ formatDate(post.timestamp) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="absolute top-2 left-2 text-lg bg-white/90 dark:bg-gray-800/90 rounded-lg px-1.5 py-0.5">
                                            {{ getMediaTypeIcon(post.media_type) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Pagination -->
                                <div v-if="totalMediaPages > 1" class="flex items-center justify-center gap-3 pt-6">
                                    <button @click="currentPage--" :disabled="currentPage === 1"
                                        class="px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium disabled:opacity-50 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">
                                        ← Oldingi
                                    </button>
                                    <span class="px-4 py-2 bg-pink-100 dark:bg-pink-900/50 text-pink-700 dark:text-pink-300 rounded-xl text-sm font-bold">
                                        {{ currentPage }} / {{ totalMediaPages }}
                                    </span>
                                    <button @click="currentPage++" :disabled="currentPage === totalMediaPages"
                                        class="px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium disabled:opacity-50 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">
                                        Keyingi →
                                    </button>
                                </div>
                            </div>

                            <!-- Audience Tab -->
                            <div v-if="activeTab === 'audience'" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Gender -->
                                    <div v-if="audience?.gender_distribution" class="bg-gray-50 dark:bg-gray-700 rounded-xl p-6 border border-gray-200 dark:border-gray-600">
                                        <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2">Followerlar jinsi</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Sizni kim kuzatadi?</p>
                                        <div class="space-y-4">
                                            <div>
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="flex items-center gap-2 text-blue-600 dark:text-blue-400 font-semibold">
                                                        <span class="text-xl">👨</span> Erkaklar
                                                    </span>
                                                    <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ audience.gender_distribution.male }}%</span>
                                                </div>
                                                <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                                    <div class="h-full bg-blue-500 rounded-full transition-all"
                                                        :style="{ width: audience.gender_distribution.male + '%' }"></div>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="flex items-center gap-2 text-pink-600 dark:text-pink-400 font-semibold">
                                                        <span class="text-xl">👩</span> Ayollar
                                                    </span>
                                                    <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ audience.gender_distribution.female }}%</span>
                                                </div>
                                                <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                                    <div class="h-full bg-pink-500 rounded-full transition-all"
                                                        :style="{ width: audience.gender_distribution.female + '%' }"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Age -->
                                    <div v-if="audience?.age_distribution?.length" class="bg-gray-50 dark:bg-gray-700 rounded-xl p-6 border border-gray-200 dark:border-gray-600">
                                        <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2">Followerlar yoshi</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Eng ko'p qaysi yoshdagilar kuzatadi?</p>
                                        <div class="space-y-3">
                                            <div v-for="(age, index) in audience.age_distribution" :key="age.range"
                                                class="flex items-center gap-3">
                                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400 w-14">{{ age.range }}</span>
                                                <div class="flex-1 h-5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                                    <div class="h-full bg-pink-500 rounded-full transition-all"
                                                        :style="{ width: age.percentage + '%' }"></div>
                                                </div>
                                                <span class="text-sm font-bold text-gray-900 dark:text-gray-100 w-12 text-right">{{ age.percentage }}%</span>
                                                <span v-if="index === 0" class="text-yellow-500">⭐</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Top Cities -->
                                    <div v-if="audience?.top_cities?.length" class="bg-gray-50 dark:bg-gray-700 rounded-xl p-6 border border-gray-200 dark:border-gray-600">
                                        <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2">Top shaharlar</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Followerlaringiz qayerda yashaydi?</p>
                                        <div class="space-y-3">
                                            <div v-for="(city, i) in audience.top_cities.slice(0, 5)" :key="city.name"
                                                class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-xl">
                                                <div class="flex items-center gap-3">
                                                    <span class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm"
                                                        :class="i === 0 ? 'bg-yellow-400 text-yellow-900' : i === 1 ? 'bg-gray-300 dark:bg-gray-500 text-gray-700 dark:text-gray-200' : i === 2 ? 'bg-orange-300 text-orange-800' : 'bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300'">
                                                        {{ i + 1 }}
                                                    </span>
                                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ city.name }}</span>
                                                </div>
                                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(city.count) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Top Countries -->
                                    <div v-if="audience?.top_countries?.length" class="bg-gray-50 dark:bg-gray-700 rounded-xl p-6 border border-gray-200 dark:border-gray-600">
                                        <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2">Top davlatlar</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Qaysi davlatlardan kuzatishadi?</p>
                                        <div class="space-y-3">
                                            <div v-for="(country, i) in audience.top_countries.slice(0, 5)" :key="country.name"
                                                class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-xl">
                                                <div class="flex items-center gap-3">
                                                    <span class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm"
                                                        :class="i === 0 ? 'bg-yellow-400 text-yellow-900' : i === 1 ? 'bg-gray-300 dark:bg-gray-500 text-gray-700 dark:text-gray-200' : i === 2 ? 'bg-orange-300 text-orange-800' : 'bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300'">
                                                        {{ i + 1 }}
                                                    </span>
                                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ country.name }}</span>
                                                </div>
                                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ formatNumber(country.count) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Engagement Tab -->
                            <div v-if="activeTab === 'engagement'" class="space-y-6">
                                <!-- Top Hashtags -->
                                <div v-if="hashtags?.top_by_engagement?.length">
                                    <h3 class="text-xl font-bold text-gray-900 mb-2">Eng samarali hashtaglar</h3>
                                    <p class="text-gray-500 mb-6">Bu hashtaglar sizga eng ko'p like va izoh keltirgan</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div v-for="(tag, index) in hashtags.top_by_engagement.slice(0, 6)" :key="tag.hashtag"
                                            class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm hover:shadow-lg transition-all flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <span class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm"
                                                    :class="index === 0 ? 'bg-yellow-400 text-yellow-900' : index === 1 ? 'bg-gray-300 text-gray-700' : index === 2 ? 'bg-orange-300 text-orange-800' : 'bg-pink-100 text-pink-600'">
                                                    {{ index + 1 }}
                                                </span>
                                                <div>
                                                    <span class="text-pink-600 font-bold text-lg">{{ tag.hashtag }}</span>
                                                    <div class="text-xs text-gray-500">{{ tag.usage_count }} marta ishlatilgan</div>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-2xl font-bold" :class="getEngagementColor(tag.avg_engagement_rate)">
                                                    {{ tag.avg_engagement_rate }}%
                                                </div>
                                                <div class="text-xs text-gray-500">engagement</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Most Used Hashtags -->
                                <div v-if="hashtags?.most_used?.length" class="bg-gray-50 rounded-2xl p-6">
                                    <h3 class="text-lg font-bold text-gray-900 mb-4">Eng ko'p ishlatiladigan hashtaglar</h3>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="tag in hashtags.most_used" :key="tag.hashtag"
                                            class="px-4 py-2 bg-gradient-to-r from-pink-100 to-purple-100 text-pink-700 rounded-full text-sm font-semibold hover:from-pink-200 hover:to-purple-200 transition-all cursor-default">
                                            {{ tag.hashtag }} <span class="text-pink-500">({{ tag.usage_count }})</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Health Modal -->
                <div v-if="showHealthModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showHealthModal = false">
                    <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl">
                        <div class="p-6 bg-gradient-to-r from-pink-500 to-purple-500 text-white">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="text-4xl">{{ healthStatus.emoji }}</span>
                                    <div>
                                        <h3 class="text-xl font-bold">Akkount holati: {{ healthStatus.label }}</h3>
                                        <p class="text-pink-100">{{ accountHealth }}/100 ball</p>
                                    </div>
                                </div>
                                <button @click="showHealthModal = false" class="text-white/80 hover:text-white p-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="p-6 space-y-4">
                            <p class="text-gray-700 text-lg">{{ healthStatus.desc }}</p>

                            <div class="space-y-3">
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                    <span class="text-2xl">📊</span>
                                    <div>
                                        <div class="font-semibold text-gray-900">Engagement Rate</div>
                                        <div class="text-sm text-gray-500">{{ overview?.account?.engagement_rate || 0 }}% -
                                            {{ (overview?.account?.engagement_rate || 0) >= 3 ? 'Yaxshi!' : 'Yaxshilash mumkin' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                    <span class="text-2xl">👥</span>
                                    <div>
                                        <div class="font-semibold text-gray-900">Followerlar</div>
                                        <div class="text-sm text-gray-500">{{ formatNumber(overview?.account?.followers_count) }} kishi sizni kuzatadi</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                    <span class="text-2xl">📱</span>
                                    <div>
                                        <div class="font-semibold text-gray-900">Postlar</div>
                                        <div class="text-sm text-gray-500">{{ overview?.account?.media_count || 0 }} ta post joylagansiz</div>
                                    </div>
                                </div>
                            </div>

                            <button @click="showHealthModal = false; generateAIInsights()"
                                class="w-full py-3 bg-gradient-to-r from-pink-500 to-purple-500 text-white rounded-xl font-semibold hover:from-pink-600 hover:to-purple-600 transition-all">
                                AI dan batafsil maslahat olish
                            </button>
                        </div>
                    </div>
                </div>

                <!-- AI Insights Modal -->
                <div v-if="showAIModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showAIModal = false">
                    <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[85vh] overflow-y-auto shadow-2xl">
                        <div class="sticky top-0 bg-gradient-to-r from-purple-500 to-pink-500 p-6 flex items-center justify-between">
                            <div class="flex items-center gap-3 text-white">
                                <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold">AI Maslahatlar</h3>
                                    <p class="text-purple-100 text-sm">Akkountingiz uchun shaxsiy tavsiyalar</p>
                                </div>
                            </div>
                            <button @click="showAIModal = false" class="text-white/80 hover:text-white p-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6">
                            <div v-if="generatingInsights" class="text-center py-12">
                                <div class="relative w-16 h-16 mx-auto mb-6">
                                    <div class="absolute inset-0 border-4 border-purple-200 rounded-full"></div>
                                    <div class="absolute inset-0 border-4 border-purple-500 border-t-transparent rounded-full animate-spin"></div>
                                </div>
                                <p class="text-gray-500 text-lg">AI akkountingizni tahlil qilmoqda...</p>
                            </div>

                            <div v-else-if="aiInsights?.success" class="space-y-6">
                                <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl p-6 border border-purple-100">
                                    <div class="flex items-start gap-4">
                                        <span class="text-3xl">📊</span>
                                        <div>
                                            <h4 class="font-bold text-gray-900 mb-2">Umumiy holat</h4>
                                            <p class="text-gray-700 leading-relaxed">{{ aiInsights.performance_summary }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="aiInsights.recommendations?.length" class="bg-blue-50 rounded-2xl p-6 border border-blue-100">
                                    <div class="flex items-start gap-4">
                                        <span class="text-3xl">💡</span>
                                        <div class="flex-1">
                                            <h4 class="font-bold text-gray-900 mb-4">Nima qilish kerak?</h4>
                                            <ul class="space-y-3">
                                                <li v-for="(rec, i) in aiInsights.recommendations" :key="i"
                                                    class="flex items-start gap-3 p-3 bg-white rounded-xl">
                                                    <span class="w-7 h-7 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                                                        {{ i + 1 }}
                                                    </span>
                                                    <span class="text-gray-700">{{ rec }}</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="aiInsights.content_insights" class="bg-pink-50 rounded-2xl p-6 border border-pink-100">
                                    <div class="flex items-start gap-4">
                                        <span class="text-3xl">🎯</span>
                                        <div>
                                            <h4 class="font-bold text-gray-900 mb-2">Kontent haqida</h4>
                                            <p class="text-gray-700 leading-relaxed">{{ aiInsights.content_insights }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="aiInsights?.error" class="text-center py-12">
                                <span class="text-5xl mb-4 block">😕</span>
                                <p class="text-gray-500">{{ aiInsights.error }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
