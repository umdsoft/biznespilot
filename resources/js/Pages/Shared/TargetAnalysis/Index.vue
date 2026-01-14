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
const metaDataLoaded = ref(false);
const metaError = ref(null);

// AI Insights
const showAIModal = ref(false);
const generatingAI = ref(false);
const aiInsights = ref(null);

// Toast notification
const notification = ref({ show: false, type: 'success', message: '' });

const isMetaConnected = computed(() => props.metaIntegration?.status === 'connected');
const hasMetaAccount = computed(() => isMetaConnected.value && props.selectedMetaAccount);

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
    try {
        await axios.post('/integrations/meta/select-account', {
            business_id: props.business.id,
            account_id: accountId
        });
        window.location.reload();
    } catch (error) {
        console.error('Select account error:', error);
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

            <!-- Not Connected State -->
            <div v-if="!hasMetaAccount" class="text-center py-20">
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
                                <select v-if="metaAdAccounts?.length > 1"
                                    @change="selectMetaAccount($event.target.value)"
                                    class="text-xl font-bold bg-transparent border-none text-gray-900 dark:text-gray-100 focus:ring-0 cursor-pointer -ml-1">
                                    <option v-for="acc in metaAdAccounts" :key="acc.meta_account_id"
                                        :value="acc.meta_account_id"
                                        :selected="acc.meta_account_id === selectedMetaAccount?.meta_account_id"
                                        class="text-gray-900 dark:text-gray-100">
                                        {{ acc.name }}
                                    </option>
                                </select>
                                <h2 v-else class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ selectedMetaAccount?.name }}</h2>
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
                            <!-- Top Age Groups -->
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
                                <div v-else class="text-center py-8 text-gray-400 dark:text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <p class="text-sm">Ma'lumot mavjud emas</p>
                                </div>
                            </div>

                            <!-- Gender Distribution -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Jins bo'yicha auditoriya</h3>
                                <div v-if="metaDemographics.gender?.length" class="space-y-4">
                                    <div v-for="item in metaDemographics.gender" :key="item.label" class="flex items-center gap-4 p-4 rounded-xl"
                                        :class="item.label === 'male' ? 'bg-blue-50 dark:bg-blue-900/20' : item.label === 'female' ? 'bg-pink-50 dark:bg-pink-900/20' : 'bg-gray-50 dark:bg-gray-700'">
                                        <div class="w-14 h-14 rounded-xl flex items-center justify-center"
                                            :class="item.label === 'male' ? 'bg-blue-100 dark:bg-blue-900/50' : item.label === 'female' ? 'bg-pink-100 dark:bg-pink-900/50' : 'bg-gray-200 dark:bg-gray-600'">
                                            <svg class="w-7 h-7" :class="item.label === 'male' ? 'text-blue-600 dark:text-blue-400' : item.label === 'female' ? 'text-pink-600 dark:text-pink-400' : 'text-gray-500 dark:text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ item.label === 'male' ? 'Erkaklar' : item.label === 'female' ? 'Ayollar' : 'Noma\'lum' }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatCurrency(item.spend, selectedMetaAccount?.currency) }} sarflangan</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ item.percentage }}%</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8 text-gray-400 dark:text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <p class="text-sm">Ma'lumot mavjud emas</p>
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
                                    <div v-else-if="item.platform === 'instagram'" class="w-14 h-14 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center flex-shrink-0 shadow-lg">
                                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069z"/>
                                        </svg>
                                    </div>
                                    <!-- Messenger -->
                                    <div v-else-if="item.platform === 'messenger'" class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.654V24l4.088-2.242c1.092.3 2.246.464 3.443.464 6.627 0 12-4.975 12-11.111S18.627 0 12 0z"/>
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
    </component>
</template>
