<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import axios from 'axios';
import CampaignsTab from '@/components/Meta/CampaignsTab.vue';

const props = defineProps({
    business: Object,
    analysis: Object,
    lastUpdated: String,
    error: String,
    metaIntegration: Object,
    metaAdAccounts: Array,
    selectedMetaAccount: Object,
    panelType: {
        type: String,
        default: 'business',
        validator: (v) => ['business', 'marketing', 'finance', 'operator', 'saleshead'].includes(v),
    },
});

// Layout selection
const layoutComponent = computed(() => {
    const layouts = {
        business: BusinessLayout,
        marketing: MarketingLayout,
        finance: FinanceLayout,
        operator: OperatorLayout,
        saleshead: SalesHeadLayout,
    };
    return layouts[props.panelType] || BaseLayout;
});

// Route helpers based on panel type
const getApiRoute = (path) => {
    return `/${props.panelType}${path}`;
};

const page = usePage();
const activeTab = ref('overview');

// Flash messages
const flashSuccess = ref(page.props.flash?.success || null);
const flashError = ref(page.props.flash?.error || null);
const showFlash = ref(!!flashSuccess.value || !!flashError.value);

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
const metaObjectives = ref({});
const metaAudience = ref({});
const metaDataLoaded = ref(false);
const metaError = ref(null);

// AI Insights
const showAIModal = ref(false);
const generatingAI = ref(false);
const aiInsights = ref(null);

// Toast notification
const notification = ref({ show: false, type: 'success', message: '' });

// Account selection modal
const selectingAccount = ref(false);
const showAccountSelectorModal = computed(() => {
    // Show modal when connected but has multiple accounts (user needs to pick one)
    return isMetaConnected.value && props.metaAdAccounts?.length > 1;
});

const isMetaConnected = computed(() => props.metaIntegration?.status === 'connected');
const isMetaExpired = computed(() => props.metaIntegration?.status === 'expired');
const hasMetaAccount = computed(() => isMetaConnected.value && props.selectedMetaAccount);
const tokenDaysRemaining = computed(() => props.metaIntegration?.days_until_expiry);
const showTokenWarning = computed(() => tokenDaysRemaining.value !== null && tokenDaysRemaining.value <= 7 && tokenDaysRemaining.value > 0);

// Format helpers
const formatNumber = (num) => new Intl.NumberFormat('uz-UZ').format(num || 0);
const formatCurrency = (amount, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(amount || 0);
};
const formatPercent = (value) => (value || 0).toFixed(2) + '%';

const dismissFlash = () => {
    showFlash.value = false;
    flashSuccess.value = null;
    flashError.value = null;
};

const showNotification = (type, message) => {
    notification.value = { show: true, type, message };
    setTimeout(() => notification.value.show = false, 5000);
};

// Meta Methods - using shared /integrations/meta routes
const connectMeta = async () => {
    metaConnecting.value = true;
    try {
        const response = await axios.get('/integrations/meta/auth-url', {
            params: { business_id: props.business.id }
        });
        if (response.data.url) {
            window.location.href = response.data.url;
        } else {
            metaError.value = 'OAuth URL olinmadi';
        }
    } catch (error) {
        metaError.value = error.response?.data?.message || 'Ulanishda xatolik';
    } finally {
        metaConnecting.value = false;
    }
};

const disconnectMeta = async () => {
    if (!confirm('Meta Ads integratsiyasini uzmoqchimisiz?')) return;
    metaLoading.value = true;
    try {
        await axios.post('/integrations/meta/disconnect', {
            business_id: props.business.id
        });
        window.location.reload();
    } catch (error) {
        console.error('Disconnect error:', error);
    } finally {
        metaLoading.value = false;
    }
};

const syncMeta = async () => {
    metaSyncing.value = true;
    metaError.value = null;
    try {
        const response = await axios.post('/integrations/meta/sync', {
            business_id: props.business.id
        });
        if (response.data.success) {
            showNotification('success', 'Ma\'lumotlar muvaffaqiyatli sinxronlandi!');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification('error', response.data.error || 'Xatolik yuz berdi');
        }
    } catch (error) {
        showNotification('error', error.response?.data?.error || 'Sinxronlashda xatolik');
    } finally {
        metaSyncing.value = false;
    }
};

const selectMetaAccount = async (accountId) => {
    selectingAccount.value = true;
    try {
        await axios.post('/integrations/meta/select-account', {
            business_id: props.business.id,
            account_id: accountId
        });
        showNotification('success', 'Account tanlandi. Ma\'lumotlar yuklanmoqda...');
        setTimeout(() => window.location.reload(), 1000);
    } catch (error) {
        console.error('Select account error:', error);
        showNotification('error', 'Account tanlashda xatolik');
        selectingAccount.value = false;
    }
};

// Load Meta Data - using shared /integrations/meta/api routes
const loadMetaOverview = async () => {
    try {
        const response = await axios.get('/integrations/meta/api/overview', {
            params: { business_id: props.business.id, period: metaDateRange.value }
        });
        metaOverview.value = response.data;
    } catch (error) {
        console.error('Overview error:', error);
    }
};

const loadMetaCampaigns = async () => {
    try {
        const response = await axios.get('/integrations/meta/api/campaigns', {
            params: { business_id: props.business.id, period: metaDateRange.value }
        });
        metaCampaigns.value = response.data.campaigns || [];
    } catch (error) {
        console.error('Campaigns error:', error);
    }
};

const loadMetaDemographics = async () => {
    try {
        const response = await axios.get('/integrations/meta/api/demographics', {
            params: { business_id: props.business.id, period: metaDateRange.value }
        });
        metaDemographics.value = response.data;
    } catch (error) {
        console.error('Demographics error:', error);
    }
};

const loadMetaPlacements = async () => {
    try {
        const response = await axios.get('/integrations/meta/api/placements', {
            params: { business_id: props.business.id, period: metaDateRange.value }
        });
        metaPlacements.value = response.data;
    } catch (error) {
        console.error('Placements error:', error);
    }
};

const loadMetaTrend = async () => {
    try {
        const response = await axios.get('/integrations/meta/api/trend', {
            params: { business_id: props.business.id, days: 30 }
        });
        metaTrend.value = response.data.trend || [];
    } catch (error) {
        console.error('Trend error:', error);
    }
};

const loadMetaObjectives = async () => {
    try {
        const response = await axios.get('/integrations/meta/api/objectives', {
            params: { business_id: props.business.id, date_preset: metaDateRange.value }
        });
        metaObjectives.value = response.data.objectives || {};
    } catch (error) {
        console.error('Objectives error:', error);
    }
};

const loadMetaAudience = async () => {
    try {
        const response = await axios.get('/integrations/meta/api/audience', {
            params: { business_id: props.business.id, date_preset: metaDateRange.value }
        });
        metaAudience.value = response.data.audience || {};
    } catch (error) {
        console.error('Audience error:', error);
    }
};

const loadMetaData = async () => {
    if (!isMetaConnected.value || !props.selectedMetaAccount) return;
    metaLoading.value = true;
    metaError.value = null;
    try {
        await Promise.all([
            loadMetaOverview(),
            loadMetaCampaigns(),
            loadMetaDemographics(),
            loadMetaPlacements(),
            loadMetaTrend(),
            loadMetaObjectives(),
            loadMetaAudience(),
        ]);
        metaDataLoaded.value = true;
    } catch (error) {
        metaError.value = 'Ma\'lumotlarni yuklashda xatolik';
        metaDataLoaded.value = true;
    } finally {
        metaLoading.value = false;
    }
};

const generateAIInsights = async () => {
    generatingAI.value = true;
    showAIModal.value = true;
    try {
        const response = await axios.post('/integrations/meta/api/ai-insights', {
            business_id: props.business.id,
            period: metaDateRange.value,
        });
        aiInsights.value = response.data;
    } catch (error) {
        aiInsights.value = { success: false, error: 'Xatolik yuz berdi' };
    } finally {
        generatingAI.value = false;
    }
};

watch(metaDateRange, () => {
    if (isMetaConnected.value && props.selectedMetaAccount) {
        loadMetaData();
    }
});

onMounted(() => {
    if (isMetaConnected.value && props.selectedMetaAccount) {
        loadMetaData();
    }
    if (showFlash.value) {
        setTimeout(() => dismissFlash(), 8000);
    }
});
</script>

<template>
    <Head title="Meta Ads Tahlil" />

    <!-- Notification Toast -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="transform translate-x-full opacity-0"
            enter-to-class="transform translate-x-0 opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="transform translate-x-0 opacity-100"
            leave-to-class="transform translate-x-full opacity-0"
        >
            <div v-if="notification.show" class="fixed top-4 right-4 z-50">
                <div :class="notification.type === 'success' ? 'bg-green-600' : 'bg-red-600'" class="text-white rounded-xl shadow-2xl p-4 flex items-center gap-3 min-w-[300px]">
                    <svg v-if="notification.type === 'success'" class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <svg v-else class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ notification.message }}</span>
                    <button @click="notification.show = false" class="ml-auto opacity-70 hover:opacity-100">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- AI Insights Modal -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showAIModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl max-w-3xl w-full max-h-[85vh] overflow-hidden border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-purple-600 dark:bg-purple-700 text-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold">AI Tahlil</h3>
                        </div>
                        <button @click="showAIModal = false" class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center hover:bg-white/30 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-6 overflow-y-auto max-h-[calc(85vh-100px)]">
                        <div v-if="generatingAI" class="text-center py-16">
                            <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="animate-spin h-8 w-8 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-600 dark:text-gray-300 font-medium">AI tahlil yaratilmoqda...</p>
                            <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Bu bir necha soniya vaqt olishi mumkin</p>
                        </div>
                        <div v-else-if="aiInsights" class="prose dark:prose-invert max-w-none">
                            <div class="whitespace-pre-wrap text-gray-700 dark:text-gray-300 leading-relaxed">{{ aiInsights.insights || aiInsights.error || aiInsights }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <component :is="layoutComponent" title="Meta Ads Tahlil">
        <div class="min-h-screen">
            <!-- Flash Messages -->
            <div v-if="showFlash" class="mb-6">
                <div v-if="flashSuccess" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-green-800 dark:text-green-200 font-medium">{{ flashSuccess }}</span>
                    </div>
                    <button @click="dismissFlash" class="text-green-500 hover:text-green-700 dark:hover:text-green-300">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
                <div v-if="flashError" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 flex items-center justify-between mt-3">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-red-800 dark:text-red-200 font-medium">{{ flashError }}</span>
                    </div>
                    <button @click="dismissFlash" class="text-red-500 hover:text-red-700 dark:hover:text-red-300">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Token Expired State -->
            <div v-if="isMetaExpired" class="text-center py-20">
                <div class="max-w-lg mx-auto">
                    <div class="w-24 h-24 bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl flex items-center justify-center mx-auto mb-8 shadow-xl shadow-orange-500/30">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">Meta Ads sessiyasi tugagan</h1>
                    <p class="text-gray-600 dark:text-gray-400 text-lg mb-4">
                        Facebook bilan ulanish muddati tugagan. Davom etish uchun qayta ulaning.
                    </p>
                    <p v-if="metaIntegration?.token_status?.message" class="text-orange-600 dark:text-orange-400 text-sm mb-6">
                        {{ metaIntegration.token_status.message }}
                    </p>
                    <button @click="connectMeta" :disabled="metaConnecting"
                        class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 font-semibold text-lg transition-all disabled:opacity-50 shadow-lg shadow-blue-500/30">
                        <svg v-if="!metaConnecting" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg v-else class="animate-spin w-6 h-6" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ metaConnecting ? 'Ulanmoqda...' : 'Qayta ulash' }}
                    </button>
                    <p v-if="metaError" class="mt-4 text-red-600 dark:text-red-400">{{ metaError }}</p>
                </div>
            </div>

            <!-- Not Connected State -->
            <div v-else-if="!hasMetaAccount" class="text-center py-20">
                <div class="max-w-lg mx-auto">
                    <div class="w-24 h-24 bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl flex items-center justify-center mx-auto mb-8 shadow-xl shadow-blue-500/30">
                        <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">Meta Ads Tahlil</h1>
                    <p class="text-gray-600 dark:text-gray-400 text-lg mb-8">
                        Facebook va Instagram reklamalaringiz samaradorligini tahlil qiling.
                        Auditoriya demografiyasi, kampaniya natijalari va AI tavsiyalarini oling.
                    </p>
                    <button @click="connectMeta" :disabled="metaConnecting"
                        class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 font-semibold text-lg transition-all disabled:opacity-50 shadow-lg shadow-blue-500/30">
                        <svg v-if="!metaConnecting" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        <svg v-else class="animate-spin w-6 h-6" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ metaConnecting ? 'Ulanmoqda...' : 'Meta hisobini ulash' }}
                    </button>
                    <p v-if="metaError" class="mt-4 text-red-600 dark:text-red-400">{{ metaError }}</p>
                </div>
            </div>

            <!-- Connected State -->
            <div v-else class="space-y-6">
                <!-- Header Card -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Ulangan hisob</p>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ selectedMetaAccount?.name }}</h2>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">{{ selectedMetaAccount?.meta_account_id }} • {{ selectedMetaAccount?.currency }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <select v-model="metaDateRange" class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                                <option value="last_7d">Oxirgi 7 kun</option>
                                <option value="last_14d">Oxirgi 14 kun</option>
                                <option value="last_30d">Oxirgi 30 kun</option>
                                <option value="last_90d">Oxirgi 90 kun</option>
                                <option value="maximum">Barcha vaqt</option>
                            </select>
                            <button @click="generateAIInsights" :disabled="generatingAI || !metaDataLoaded"
                                class="px-4 py-2.5 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-xl hover:from-purple-700 hover:to-purple-800 transition-all flex items-center gap-2 text-sm font-medium disabled:opacity-50 shadow-lg shadow-purple-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                AI Tahlil
                            </button>
                            <button @click="syncMeta" :disabled="metaSyncing"
                                class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center gap-2 text-sm font-medium disabled:opacity-50">
                                <svg :class="{ 'animate-spin': metaSyncing }" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ metaSyncing ? 'Sinxronlanmoqda...' : 'Yangilash' }}
                            </button>
                            <button @click="disconnectMeta" class="px-4 py-2.5 bg-red-600 dark:bg-red-700 text-white rounded-xl hover:bg-red-700 dark:hover:bg-red-600 transition-colors text-sm font-medium">
                                Uzish
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Token Expiry Warning -->
                <div v-if="showTokenWarning" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-yellow-800 dark:text-yellow-200 font-medium">
                            Meta Ads sessiyasi {{ tokenDaysRemaining }} kun ichida tugaydi. Uzilishdan saqlaning va qayta ulaning.
                        </span>
                    </div>
                    <button @click="connectMeta" :disabled="metaConnecting"
                        class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 text-sm font-medium disabled:opacity-50">
                        {{ metaConnecting ? 'Ulanmoqda...' : 'Yangilash' }}
                    </button>
                </div>

                <!-- Loading State -->
                <div v-if="metaLoading && !metaDataLoaded" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-16">
                    <div class="flex flex-col items-center justify-center">
                        <svg class="animate-spin h-12 w-12 text-blue-600 dark:text-blue-400 mb-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400 font-medium">Ma'lumotlar yuklanmoqda...</p>
                    </div>
                </div>

                <!-- Data Loaded -->
                <template v-else-if="metaDataLoaded">
                    <!-- Tab Navigation -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                        <nav class="flex overflow-x-auto p-1.5 gap-1">
                            <button @click="activeTab = 'overview'"
                                :class="[activeTab === 'overview' ? 'bg-blue-600 dark:bg-blue-700 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700', 'flex-1 min-w-[120px] py-3 px-4 rounded-xl font-medium text-sm transition-all']">
                                Umumiy
                            </button>
                            <button @click="activeTab = 'campaigns'"
                                :class="[activeTab === 'campaigns' ? 'bg-blue-600 dark:bg-blue-700 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700', 'flex-1 min-w-[120px] py-3 px-4 rounded-xl font-medium text-sm transition-all flex items-center justify-center gap-2']">
                                Kampaniyalar
                                <span :class="activeTab === 'campaigns' ? 'bg-white/20' : 'bg-gray-200 dark:bg-gray-600'" class="text-xs px-2 py-0.5 rounded-full">{{ metaCampaigns.length }}</span>
                            </button>
                            <button @click="activeTab = 'audience'"
                                :class="[activeTab === 'audience' ? 'bg-blue-600 dark:bg-blue-700 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700', 'flex-1 min-w-[120px] py-3 px-4 rounded-xl font-medium text-sm transition-all']">
                                Auditoriya
                            </button>
                            <button @click="activeTab = 'placements'"
                                :class="[activeTab === 'placements' ? 'bg-blue-600 dark:bg-blue-700 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700', 'flex-1 min-w-[120px] py-3 px-4 rounded-xl font-medium text-sm transition-all']">
                                Platformalar
                            </button>
                            <button @click="activeTab = 'trend'"
                                :class="[activeTab === 'trend' ? 'bg-blue-600 dark:bg-blue-700 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700', 'flex-1 min-w-[120px] py-3 px-4 rounded-xl font-medium text-sm transition-all']">
                                Trend
                            </button>
                            <!-- Instagram Link -->
                            <a :href="'/integrations/instagram'"
                                class="flex-1 min-w-[120px] py-3 px-4 rounded-xl font-medium text-sm transition-all text-gray-600 dark:text-gray-400 hover:bg-gradient-to-r hover:from-purple-500 hover:via-pink-500 hover:to-orange-400 hover:text-white flex items-center justify-center gap-2 border border-transparent hover:border-pink-300">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2c2.717 0 3.056.01 4.122.06 1.065.05 1.79.217 2.428.465.66.254 1.216.598 1.772 1.153a4.908 4.908 0 0 1 1.153 1.772c.247.637.415 1.363.465 2.428.047 1.066.06 1.405.06 4.122 0 2.717-.01 3.056-.06 4.122-.05 1.065-.218 1.79-.465 2.428a4.883 4.883 0 0 1-1.153 1.772 4.915 4.915 0 0 1-1.772 1.153c-.637.247-1.363.415-2.428.465-1.066.047-1.405.06-4.122.06-2.717 0-3.056-.01-4.122-.06-1.065-.05-1.79-.218-2.428-.465a4.89 4.89 0 0 1-1.772-1.153 4.904 4.904 0 0 1-1.153-1.772c-.248-.637-.415-1.363-.465-2.428C2.013 15.056 2 14.717 2 12c0-2.717.01-3.056.06-4.122.05-1.066.217-1.79.465-2.428a4.88 4.88 0 0 1 1.153-1.772A4.897 4.897 0 0 1 5.45 2.525c.638-.248 1.362-.415 2.428-.465C8.944 2.013 9.283 2 12 2zm0 5a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm6.5-.25a1.25 1.25 0 0 0-2.5 0 1.25 1.25 0 0 0 2.5 0zM12 9a3 3 0 1 1 0 6 3 3 0 0 1 0-6z"/>
                                </svg>
                                Instagram
                            </a>
                        </nav>
                    </div>

                    <!-- Tab: Overview -->
                    <div v-show="activeTab === 'overview'" class="space-y-6">
                        <!-- KPI Cards -->
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                            <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl p-5 text-white shadow-lg shadow-blue-500/30">
                                <p class="text-blue-100 text-xs font-medium uppercase tracking-wide">Sarflangan</p>
                                <p class="text-2xl font-bold mt-2">{{ formatCurrency(metaOverview?.current?.spend || 0, selectedMetaAccount?.currency) }}</p>
                                <p class="text-blue-200 text-xs mt-1" v-if="metaOverview?.change?.spend">
                                    <span :class="metaOverview.change.spend >= 0 ? 'text-red-300' : 'text-green-300'">
                                        {{ metaOverview.change.spend >= 0 ? '+' : '' }}{{ metaOverview.change.spend }}%
                                    </span> oldingi davrga
                                </p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                                <p class="text-gray-500 dark:text-gray-400 text-xs font-medium uppercase tracking-wide">Qamrov</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-2">{{ formatNumber(metaOverview?.current?.reach || 0) }}</p>
                                <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Noyob foydalanuvchi</p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                                <p class="text-gray-500 dark:text-gray-400 text-xs font-medium uppercase tracking-wide">Ko'rishlar</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-2">{{ formatNumber(metaOverview?.current?.impressions || 0) }}</p>
                                <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Jami impressions</p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                                <p class="text-gray-500 dark:text-gray-400 text-xs font-medium uppercase tracking-wide">Kliklar</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-2">{{ formatNumber(metaOverview?.current?.clicks || 0) }}</p>
                                <p class="text-xs mt-1" :class="(metaOverview?.change?.clicks || 0) >= 0 ? 'text-green-500' : 'text-red-500'">
                                    {{ (metaOverview?.change?.clicks || 0) >= 0 ? '+' : '' }}{{ metaOverview?.change?.clicks || 0 }}%
                                </p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                                <p class="text-gray-500 dark:text-gray-400 text-xs font-medium uppercase tracking-wide">CTR</p>
                                <p class="text-2xl font-bold mt-2" :class="(metaOverview?.current?.ctr || 0) >= 1 ? 'text-green-600 dark:text-green-400' : 'text-orange-600 dark:text-orange-400'">
                                    {{ formatPercent(metaOverview?.current?.ctr || 0) }}
                                </p>
                                <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Click-through rate</p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                                <p class="text-gray-500 dark:text-gray-400 text-xs font-medium uppercase tracking-wide">CPC</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-2">{{ formatCurrency(metaOverview?.current?.cpc || 0, selectedMetaAccount?.currency) }}</p>
                                <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Klik narxi</p>
                            </div>
                        </div>

                        <!-- Two Column Layout -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Left Column: Age + Gender -->
                            <div class="space-y-4">
                                <!-- Age Groups -->
                                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Yosh bo'yicha auditoriya</h3>
                                    <div v-if="metaDemographics.age?.length" class="space-y-3">
                                        <div v-for="(item, index) in metaDemographics.age.slice(0, 5)" :key="item.label" class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-bold"
                                                :class="index === 0 ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'">
                                                {{ index + 1 }}
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex justify-between mb-1">
                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ item.label }}</span>
                                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ item.percentage }}%</span>
                                                </div>
                                                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                                                    <div class="h-2 rounded-full transition-all"
                                                        :class="index === 0 ? 'bg-blue-500 dark:bg-blue-400' : 'bg-gray-400 dark:bg-gray-500'"
                                                        :style="{ width: item.percentage + '%' }"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-6 text-gray-400 dark:text-gray-500">
                                        <p class="text-sm">Ma'lumot mavjud emas</p>
                                    </div>
                                </div>

                                <!-- Compact Gender Distribution -->
                                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Jins bo'yicha</h4>
                                    <div v-if="metaDemographics.gender?.length" class="flex gap-3">
                                        <div v-for="item in metaDemographics.gender" :key="item.label"
                                            class="flex-1 flex items-center gap-2 p-2 rounded-lg"
                                            :class="item.label === 'male' ? 'bg-blue-50 dark:bg-blue-900/20' : item.label === 'female' ? 'bg-pink-50 dark:bg-pink-900/20' : 'bg-gray-50 dark:bg-gray-700'">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                                                :class="item.label === 'male' ? 'bg-blue-100 dark:bg-blue-900/50' : item.label === 'female' ? 'bg-pink-100 dark:bg-pink-900/50' : 'bg-gray-200 dark:bg-gray-600'">
                                                <svg class="w-4 h-4" :class="item.label === 'male' ? 'text-blue-600 dark:text-blue-400' : item.label === 'female' ? 'text-pink-600 dark:text-pink-400' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400 truncate">{{ item.label === 'male' ? 'Erkaklar' : item.label === 'female' ? 'Ayollar' : 'Noma\'lum' }}</p>
                                                <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ item.percentage }}%</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-3 text-gray-400 dark:text-gray-500 text-xs">
                                        Ma'lumot mavjud emas
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Objectives Analytics -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Maqsadlar bo'yicha tahlil</h3>
                                <div v-if="Object.keys(metaObjectives).length" class="space-y-3">
                                    <!-- Leads -->
                                    <div v-if="metaObjectives.leads" class="flex items-center gap-4 p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20">
                                        <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ metaObjectives.leads.label }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ metaObjectives.leads.campaigns }} kampaniya</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ formatNumber(metaObjectives.leads.total) }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatCurrency(metaObjectives.leads.cost_per, selectedMetaAccount?.currency) }}/lid</p>
                                        </div>
                                    </div>

                                    <!-- Messages -->
                                    <div v-if="metaObjectives.messages" class="flex items-center gap-4 p-4 rounded-xl bg-purple-50 dark:bg-purple-900/20">
                                        <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ metaObjectives.messages.label }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ metaObjectives.messages.campaigns }} kampaniya</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ formatNumber(metaObjectives.messages.total) }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatCurrency(metaObjectives.messages.cost_per, selectedMetaAccount?.currency) }}/xabar</p>
                                        </div>
                                    </div>

                                    <!-- Sales -->
                                    <div v-if="metaObjectives.sales" class="flex items-center gap-4 p-4 rounded-xl bg-green-50 dark:bg-green-900/20">
                                        <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ metaObjectives.sales.label }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ metaObjectives.sales.campaigns }} kampaniya</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatNumber(metaObjectives.sales.total) }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatCurrency(metaObjectives.sales.cost_per, selectedMetaAccount?.currency) }}/sotish</p>
                                        </div>
                                    </div>

                                    <!-- Traffic -->
                                    <div v-if="metaObjectives.traffic" class="flex items-center gap-4 p-4 rounded-xl bg-orange-50 dark:bg-orange-900/20">
                                        <div class="w-12 h-12 rounded-xl bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ metaObjectives.traffic.label }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ metaObjectives.traffic.campaigns }} kampaniya</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ formatNumber(metaObjectives.traffic.total) }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatCurrency(metaObjectives.traffic.cost_per, selectedMetaAccount?.currency) }}/klik</p>
                                        </div>
                                    </div>

                                    <!-- Engagement -->
                                    <div v-if="metaObjectives.engagement" class="flex items-center gap-4 p-4 rounded-xl bg-pink-50 dark:bg-pink-900/20">
                                        <div class="w-12 h-12 rounded-xl bg-pink-100 dark:bg-pink-900/50 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ metaObjectives.engagement.label }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ metaObjectives.engagement.campaigns }} kampaniya</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-2xl font-bold text-pink-600 dark:text-pink-400">{{ formatNumber(metaObjectives.engagement.total) }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatCurrency(metaObjectives.engagement.cost_per, selectedMetaAccount?.currency) }}/eng</p>
                                        </div>
                                    </div>

                                    <!-- Video -->
                                    <div v-if="metaObjectives.video" class="flex items-center gap-4 p-4 rounded-xl bg-red-50 dark:bg-red-900/20">
                                        <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ metaObjectives.video.label }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ metaObjectives.video.campaigns }} kampaniya</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ formatNumber(metaObjectives.video.total) }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatCurrency(metaObjectives.video.cost_per, selectedMetaAccount?.currency) }}/ko'rish</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-12 text-gray-400 dark:text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    <p class="text-sm">Maqsadlar ma'lumoti mavjud emas</p>
                                </div>
                            </div>
                        </div>

                        <!-- Platform Summary -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Platforma samaradorligi</h3>
                            <div v-if="metaPlacements.platforms?.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                <div v-for="item in metaPlacements.platforms" :key="item.platform" class="text-center p-4 rounded-xl bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <!-- Instagram -->
                                    <div v-if="item.platform === 'instagram'" class="w-14 h-14 mx-auto mb-3 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center shadow-lg">
                                        <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2c2.717 0 3.056.01 4.122.06 1.065.05 1.79.217 2.428.465.66.254 1.216.598 1.772 1.153a4.908 4.908 0 0 1 1.153 1.772c.247.637.415 1.363.465 2.428.047 1.066.06 1.405.06 4.122 0 2.717-.01 3.056-.06 4.122-.05 1.065-.218 1.79-.465 2.428a4.883 4.883 0 0 1-1.153 1.772 4.915 4.915 0 0 1-1.772 1.153c-.637.247-1.363.415-2.428.465-1.066.047-1.405.06-4.122.06-2.717 0-3.056-.01-4.122-.06-1.065-.05-1.79-.218-2.428-.465a4.89 4.89 0 0 1-1.772-1.153 4.904 4.904 0 0 1-1.153-1.772c-.248-.637-.415-1.363-.465-2.428C2.013 15.056 2 14.717 2 12c0-2.717.01-3.056.06-4.122.05-1.066.217-1.79.465-2.428a4.88 4.88 0 0 1 1.153-1.772A4.897 4.897 0 0 1 5.45 2.525c.638-.248 1.362-.415 2.428-.465C8.944 2.013 9.283 2 12 2zm0 5a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm6.5-.25a1.25 1.25 0 0 0-2.5 0 1.25 1.25 0 0 0 2.5 0zM12 9a3 3 0 1 1 0 6 3 3 0 0 1 0-6z"/>
                                        </svg>
                                    </div>
                                    <!-- Facebook -->
                                    <div v-else-if="item.platform === 'facebook'" class="w-14 h-14 mx-auto mb-3 rounded-xl bg-blue-600 flex items-center justify-center shadow-lg">
                                        <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                        </svg>
                                    </div>
                                    <!-- Messenger -->
                                    <div v-else-if="item.platform === 'messenger'" class="w-14 h-14 mx-auto mb-3 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg">
                                        <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2C6.477 2 2 6.145 2 11.243c0 2.906 1.447 5.497 3.708 7.2V22l3.4-1.867c.907.252 1.87.387 2.892.387 5.523 0 10-4.145 10-9.243S17.523 2 12 2zm1.065 12.44l-2.545-2.717-4.97 2.717 5.466-5.802 2.608 2.716 4.906-2.716-5.465 5.803z"/>
                                        </svg>
                                    </div>
                                    <!-- Audience Network -->
                                    <div v-else-if="item.platform === 'audience_network'" class="w-14 h-14 mx-auto mb-3 rounded-xl bg-green-600 flex items-center justify-center shadow-lg">
                                        <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                        </svg>
                                    </div>
                                    <!-- Default -->
                                    <div v-else class="w-14 h-14 mx-auto mb-3 rounded-xl bg-gray-500 flex items-center justify-center shadow-lg">
                                        <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <path d="M8 12h8M12 8v8"/>
                                        </svg>
                                    </div>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100 mt-2">{{ item.label }}</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ item.percentage }}%</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ formatCurrency(item.spend, selectedMetaAccount?.currency) }}</p>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-400 dark:text-gray-500">
                                <p class="text-sm">Ma'lumot mavjud emas</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Campaigns -->
                    <div v-show="activeTab === 'campaigns'">
                        <CampaignsTab
                            v-if="business?.id"
                            :business-id="business.id"
                            :currency="selectedMetaAccount?.currency"
                            :panel-type="panelType"
                            @sync-started="metaSyncing = true"
                            @sync-completed="(data) => { metaSyncing = false; loadMetaData(); }"
                            @error="(msg) => showNotification('error', msg)"
                        />
                        <div v-else class="p-8 text-center text-gray-500 dark:text-gray-400">
                            <p>Biznes ID topilmadi</p>
                        </div>
                    </div>

                    <!-- Tab: Audience -->
                    <div v-show="activeTab === 'audience'" class="space-y-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Age Demographics -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Yosh bo'yicha auditoriya</h3>
                                <div v-if="metaDemographics.age?.length" class="space-y-4">
                                    <div v-for="item in metaDemographics.age" :key="item.label">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ item.label }}</span>
                                            <div class="text-right">
                                                <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ item.percentage }}%</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ formatCurrency(item.spend, selectedMetaAccount?.currency) }}</span>
                                            </div>
                                        </div>
                                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3">
                                            <div class="bg-blue-500 dark:bg-blue-400 h-3 rounded-full transition-all" :style="{ width: item.percentage + '%' }"></div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="flex flex-col items-center justify-center py-12 text-gray-400 dark:text-gray-500">
                                    <svg class="w-16 h-16 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <p>Ma'lumot mavjud emas</p>
                                </div>
                            </div>

                            <!-- Gender Demographics -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Jins bo'yicha auditoriya</h3>
                                <div v-if="metaDemographics.gender?.length" class="space-y-4">
                                    <div v-for="item in metaDemographics.gender" :key="item.label" class="flex items-center gap-4 p-5 rounded-xl"
                                        :class="item.label === 'male' ? 'bg-blue-50 dark:bg-blue-900/20' : item.label === 'female' ? 'bg-pink-50 dark:bg-pink-900/20' : 'bg-gray-50 dark:bg-gray-700'">
                                        <div class="w-16 h-16 rounded-xl flex items-center justify-center"
                                            :class="item.label === 'male' ? 'bg-blue-100 dark:bg-blue-900/50' : item.label === 'female' ? 'bg-pink-100 dark:bg-pink-900/50' : 'bg-gray-200 dark:bg-gray-600'">
                                            <svg class="w-8 h-8" :class="item.label === 'male' ? 'text-blue-600 dark:text-blue-400' : item.label === 'female' ? 'text-pink-600 dark:text-pink-400' : 'text-gray-500 dark:text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ item.label === 'male' ? 'Erkaklar' : item.label === 'female' ? 'Ayollar' : 'Noma\'lum' }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatCurrency(item.spend, selectedMetaAccount?.currency) }} sarflangan</p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">CTR: {{ item.ctr }}% • CPC: {{ formatCurrency(item.cpc, selectedMetaAccount?.currency) }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ item.percentage }}%</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="flex flex-col items-center justify-center py-12 text-gray-400 dark:text-gray-500">
                                    <svg class="w-16 h-16 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <p>Ma'lumot mavjud emas</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Placements -->
                    <div v-show="activeTab === 'placements'" class="space-y-6">
                        <!-- Platform Distribution -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Platformalar</h3>
                            <div v-if="metaPlacements.platforms?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="item in metaPlacements.platforms" :key="item.label" class="flex items-center gap-4 p-5 bg-gray-50 dark:bg-gray-700 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <!-- Facebook -->
                                    <div v-if="item.platform === 'facebook'" class="w-14 h-14 rounded-xl bg-blue-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                        </svg>
                                    </div>
                                    <!-- Instagram -->
                                    <div v-else-if="item.platform === 'instagram'" class="w-14 h-14 rounded-xl bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400 flex items-center justify-center flex-shrink-0 shadow-lg">
                                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6m9.65 1.5a1.25 1.25 0 0 1 1.25 1.25A1.25 1.25 0 0 1 17.25 8 1.25 1.25 0 0 1 16 6.75a1.25 1.25 0 0 1 1.25-1.25M12 7a5 5 0 0 1 5 5 5 5 0 0 1-5 5 5 5 0 0 1-5-5 5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3Z"/>
                                        </svg>
                                    </div>
                                    <!-- Messenger -->
                                    <div v-else-if="item.platform === 'messenger'" class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.654V24l4.088-2.242c1.092.3 2.246.464 3.443.464 6.627 0 12-4.975 12-11.111S18.627 0 12 0zm1.205 14.916l-3.063-3.27-5.978 3.27L10.282 8.5l3.135 3.27 5.906-3.27-6.118 6.416z"/>
                                        </svg>
                                    </div>
                                    <!-- Threads -->
                                    <div v-else-if="item.platform === 'threads'" class="w-14 h-14 rounded-xl bg-black flex items-center justify-center flex-shrink-0 shadow-lg">
                                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.8-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.801-1.063-.689-1.685-1.74-1.752-2.96-.065-1.17.408-2.133 1.332-2.727.89-.57 2.088-.863 3.473-.847.984.011 1.882.136 2.688.374.058-.66.034-1.296-.067-1.89-.248-1.423-1.064-2.19-2.427-2.282-1.322-.088-2.347.377-2.58 1.17l-2.018-.501c.47-1.65 2.181-2.6 4.46-2.449 1.387.093 2.523.507 3.377 1.231.942.799 1.524 1.945 1.73 3.406.078.553.106 1.14.083 1.76 1.015.479 1.827 1.074 2.464 1.82 1.057 1.24 1.403 2.76 1.029 4.52-.545 2.568-2.527 4.583-5.432 5.518-1.328.428-2.838.64-4.407.626zm-1.715-7.274c-.956.01-1.721.177-2.216.482-.323.2-.49.456-.473.726.027.436.282.793.759 1.063.578.328 1.34.48 2.145.428 1.053-.056 1.878-.448 2.453-1.166.387-.481.663-1.1.833-1.855-.965-.265-2.037-.4-3.21-.413l-.29.735z"/>
                                        </svg>
                                    </div>
                                    <!-- Audience Network -->
                                    <div v-else-if="item.platform === 'audience_network'" class="w-14 h-14 rounded-xl bg-green-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                        </svg>
                                    </div>
                                    <!-- Default -->
                                    <div v-else class="w-14 h-14 rounded-xl bg-gray-400 flex items-center justify-center flex-shrink-0 shadow-lg">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ item.label }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatCurrency(item.spend, selectedMetaAccount?.currency) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ item.percentage }}%</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-12 text-gray-400 dark:text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <p>Platforma ma'lumoti mavjud emas</p>
                            </div>
                        </div>

                        <!-- Positions -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Joylashuvlar (Positions)</h3>
                            <div v-if="metaPlacements.positions?.length" class="space-y-3">
                                <div v-for="item in metaPlacements.positions.slice(0, 10)" :key="item.position + item.platform" class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <div class="flex justify-between mb-1">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ item.label }}</span>
                                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ item.percentage }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2.5">
                                            <div class="bg-purple-500 dark:bg-purple-400 h-2.5 rounded-full transition-all" :style="{ width: Math.min(item.percentage * 3, 100) + '%' }"></div>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-400 dark:text-gray-500 w-20 text-right">{{ formatCurrency(item.spend, selectedMetaAccount?.currency) }}</span>
                                </div>
                            </div>
                            <div v-else class="text-center py-12 text-gray-400 dark:text-gray-500">
                                <p>Joylashuv ma'lumoti mavjud emas</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Trend -->
                    <div v-show="activeTab === 'trend'" class="space-y-6">
                        <!-- Audience Insights Cards -->
                        <div v-if="metaAudience?.insights?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div v-for="insight in metaAudience.insights" :key="insight.type"
                                class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow">
                                <div class="flex items-start gap-3">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                                        :class="{
                                            'bg-blue-100 dark:bg-blue-900/50': insight.color === 'blue',
                                            'bg-pink-100 dark:bg-pink-900/50': insight.color === 'pink',
                                            'bg-purple-100 dark:bg-purple-900/50': insight.color === 'purple',
                                            'bg-green-100 dark:bg-green-900/50': insight.color === 'green',
                                        }">
                                        <!-- Users Icon -->
                                        <svg v-if="insight.icon === 'users'" class="w-6 h-6" :class="{
                                            'text-blue-600 dark:text-blue-400': insight.color === 'blue',
                                            'text-pink-600 dark:text-pink-400': insight.color === 'pink',
                                            'text-purple-600 dark:text-purple-400': insight.color === 'purple',
                                            'text-green-600 dark:text-green-400': insight.color === 'green',
                                        }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <!-- User Icon -->
                                        <svg v-else-if="insight.icon === 'user'" class="w-6 h-6" :class="{
                                            'text-blue-600 dark:text-blue-400': insight.color === 'blue',
                                            'text-pink-600 dark:text-pink-400': insight.color === 'pink',
                                            'text-purple-600 dark:text-purple-400': insight.color === 'purple',
                                            'text-green-600 dark:text-green-400': insight.color === 'green',
                                        }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <!-- Device Icon -->
                                        <svg v-else-if="insight.icon === 'device-mobile'" class="w-6 h-6" :class="{
                                            'text-blue-600 dark:text-blue-400': insight.color === 'blue',
                                            'text-pink-600 dark:text-pink-400': insight.color === 'pink',
                                            'text-purple-600 dark:text-purple-400': insight.color === 'purple',
                                            'text-green-600 dark:text-green-400': insight.color === 'green',
                                        }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        <!-- Currency Icon -->
                                        <svg v-else class="w-6 h-6" :class="{
                                            'text-blue-600 dark:text-blue-400': insight.color === 'blue',
                                            'text-pink-600 dark:text-pink-400': insight.color === 'pink',
                                            'text-purple-600 dark:text-purple-400': insight.color === 'purple',
                                            'text-green-600 dark:text-green-400': insight.color === 'green',
                                        }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ insight.title }}</p>
                                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100 truncate">{{ insight.value }}</p>
                                        <p class="text-sm font-semibold" :class="{
                                            'text-blue-600 dark:text-blue-400': insight.color === 'blue',
                                            'text-pink-600 dark:text-pink-400': insight.color === 'pink',
                                            'text-purple-600 dark:text-purple-400': insight.color === 'purple',
                                            'text-green-600 dark:text-green-400': insight.color === 'green',
                                        }">{{ insight.metric }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Age Performance Table -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Yosh guruhlari bo'yicha samaradorlik</h3>
                                <div v-if="metaAudience?.age?.length" class="space-y-3">
                                    <div v-for="item in metaAudience.age" :key="item.label"
                                        class="flex items-center gap-4 p-3 rounded-xl transition-colors"
                                        :class="{
                                            'bg-green-50 dark:bg-green-900/20 ring-2 ring-green-500': item.is_best,
                                            'bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600': !item.is_best
                                        }">
                                        <div class="w-16 text-center">
                                            <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ item.label }}</p>
                                            <p v-if="item.is_best" class="text-xs text-green-600 dark:text-green-400 font-medium">TOP</p>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                <span>{{ item.spend_percentage }}% byudjet</span>
                                                <span>{{ item.ctr }}% CTR</span>
                                            </div>
                                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                <div class="h-2 rounded-full transition-all"
                                                    :class="item.is_best ? 'bg-green-500' : 'bg-blue-500'"
                                                    :style="{ width: Math.min(item.ctr * 20, 100) + '%' }"></div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ formatCurrency(item.cpc, selectedMetaAccount?.currency) }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">/klik</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8 text-gray-400 dark:text-gray-500">
                                    <p>Ma'lumot mavjud emas</p>
                                </div>
                            </div>

                            <!-- Ideal Audience, Gender & Platform Performance -->
                            <div class="space-y-4">
                                <!-- Ideal Audience Recommendation (Compact) -->
                                <div v-if="metaAudience?.ideal_audience && Object.keys(metaAudience.ideal_audience).length"
                                    class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Ideal Maqsadli Auditoriya</h3>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Eng samarali auditoriya</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-if="metaAudience.ideal_audience.age" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 text-xs font-medium">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ metaAudience.ideal_audience.age }}
                                        </span>
                                        <span v-if="metaAudience.ideal_audience.gender" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-pink-100 dark:bg-pink-900/50 text-pink-700 dark:text-pink-300 text-xs font-medium">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            {{ metaAudience.ideal_audience.gender }}
                                        </span>
                                        <span v-if="metaAudience.ideal_audience.platform" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 text-xs font-medium">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                            {{ metaAudience.ideal_audience.platform }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Gender Performance -->
                                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3">Jins bo'yicha samaradorlik</h3>
                                    <div v-if="metaAudience?.gender?.filter(g => g.key === 'male' || g.key === 'female').length" class="grid grid-cols-2 gap-3">
                                        <div v-for="item in metaAudience.gender.filter(g => g.key === 'male' || g.key === 'female')" :key="item.key"
                                            class="p-3 rounded-xl text-center transition-all"
                                            :class="{
                                                'bg-blue-50 dark:bg-blue-900/20 ring-2 ring-blue-500': item.key === 'male' && item.is_best,
                                                'bg-pink-50 dark:bg-pink-900/20 ring-2 ring-pink-500': item.key === 'female' && item.is_best,
                                                'bg-blue-50 dark:bg-blue-900/20': item.key === 'male' && !item.is_best,
                                                'bg-pink-50 dark:bg-pink-900/20': item.key === 'female' && !item.is_best
                                            }">
                                            <div class="w-10 h-10 mx-auto rounded-full flex items-center justify-center mb-2"
                                                :class="item.key === 'male' ? 'bg-blue-100 dark:bg-blue-900/50' : 'bg-pink-100 dark:bg-pink-900/50'">
                                                <svg class="w-5 h-5" :class="item.key === 'male' ? 'text-blue-600 dark:text-blue-400' : 'text-pink-600 dark:text-pink-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <p class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ item.label }}</p>
                                            <p class="text-xl font-bold" :class="item.key === 'male' ? 'text-blue-600 dark:text-blue-400' : 'text-pink-600 dark:text-pink-400'">{{ item.ctr }}%</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatCurrency(item.cpc, selectedMetaAccount?.currency) }}/klik</p>
                                            <p v-if="item.is_best" class="text-xs text-green-600 dark:text-green-400 font-medium mt-1">Eng yaxshi</p>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-4 text-gray-400 dark:text-gray-500 text-sm">
                                        <p>Ma'lumot mavjud emas</p>
                                    </div>
                                </div>

                                <!-- Platform Performance -->
                                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3">Platformalar bo'yicha samaradorlik</h3>
                                    <div v-if="metaAudience?.platform?.length" class="grid grid-cols-2 gap-2">
                                        <div v-for="item in metaAudience.platform.filter(p => p.clicks > 0)" :key="item.key"
                                            class="flex items-center gap-2 p-2 rounded-lg"
                                            :class="item.is_best ? 'bg-green-50 dark:bg-green-900/20 ring-1 ring-green-500' : 'bg-gray-50 dark:bg-gray-700'">
                                            <!-- Platform Icon -->
                                            <div v-if="item.key === 'facebook'" class="w-8 h-8 rounded-md bg-blue-600 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                                </svg>
                                            </div>
                                            <div v-else-if="item.key === 'instagram'" class="w-8 h-8 rounded-md bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6m9.65 1.5a1.25 1.25 0 0 1 1.25 1.25A1.25 1.25 0 0 1 17.25 8 1.25 1.25 0 0 1 16 6.75a1.25 1.25 0 0 1 1.25-1.25M12 7a5 5 0 0 1 5 5 5 5 0 0 1-5 5 5 5 0 0 1-5-5 5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3Z"/>
                                                </svg>
                                            </div>
                                            <div v-else-if="item.key === 'messenger'" class="w-8 h-8 rounded-md bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 2C6.36 2 2 6.13 2 11.7c0 2.91 1.19 5.44 3.14 7.17.16.13.26.35.27.57l.05 1.78c.04.57.61.94 1.13.71l1.98-.87c.17-.08.36-.1.55-.06.91.25 1.87.38 2.88.38 5.64 0 10-4.13 10-9.7C22 6.13 17.64 2 12 2zm5.89 7.59l-2.83 4.48c-.45.71-1.41.89-2.09.38l-2.25-1.69a.6.6 0 00-.72 0l-3.05 2.31c-.41.31-.94-.18-.68-.63l2.83-4.48c.45-.71 1.41-.89 2.09-.38l2.25 1.69a.6.6 0 00.72 0l3.05-2.31c.41-.31.94.18.68.63z"/>
                                                </svg>
                                            </div>
                                            <div v-else-if="item.key === 'audience_network'" class="w-8 h-8 rounded-md bg-teal-500 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </div>
                                            <div v-else class="w-8 h-8 rounded-md bg-gray-400 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ item.label }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ item.ctr }}% CTR</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-bold" :class="item.is_best ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-gray-100'">{{ formatCurrency(item.cpc, selectedMetaAccount?.currency) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-4 text-gray-400 dark:text-gray-500 text-sm">
                                        <p>Ma'lumot mavjud emas</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Daily Trend Table -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">30 kunlik trend</h3>
                            <div v-if="metaTrend?.length" class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:border-gray-700">
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Sana</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Sarflangan</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Ko'rishlar</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Kliklar</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">CTR</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">CPC</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        <tr v-for="day in metaTrend" :key="day.date" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ day.date }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 text-right font-semibold">{{ formatCurrency(day.spend, selectedMetaAccount?.currency) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 text-right">{{ formatNumber(day.impressions) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 text-right">{{ formatNumber(day.clicks) }}</td>
                                            <td class="px-4 py-3 text-sm text-right font-medium" :class="day.ctr >= 1 ? 'text-green-600 dark:text-green-400' : 'text-orange-600 dark:text-orange-400'">{{ formatPercent(day.ctr) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 text-right">{{ formatCurrency(day.cpc, selectedMetaAccount?.currency) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-12 text-gray-400 dark:text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                <p>Trend ma'lumoti mavjud emas</p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Account Selector Modal - appears when multiple accounts exist -->
        <Teleport to="body">
            <div v-if="showAccountSelectorModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 z-10">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">Ad Account Tanlang</h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">
                                Biznesingiz uchun ishlatiladigan bitta reklama hisobini tanlang. Boshqa hisoblar o'chiriladi.
                            </p>
                        </div>

                        <div class="space-y-3 mb-6">
                            <button
                                v-for="acc in metaAdAccounts"
                                :key="acc.meta_account_id"
                                @click="selectMetaAccount(acc.meta_account_id)"
                                :disabled="selectingAccount"
                                class="w-full p-4 text-left rounded-xl border-2 border-gray-200 dark:border-gray-600 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ acc.name }}</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ acc.meta_account_id }} • {{ acc.currency }}</p>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </button>
                        </div>

                        <p class="text-xs text-gray-400 dark:text-gray-500 text-center">
                            Tanlangan hisobni keyinchalik o'zgartirish uchun integratsiyani qayta ulashingiz kerak bo'ladi.
                        </p>
                    </div>
                </div>
            </div>
        </Teleport>
    </component>
</template>
