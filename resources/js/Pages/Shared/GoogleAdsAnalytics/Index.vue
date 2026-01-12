<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import CampaignsTab from '@/components/GoogleAds/CampaignsTab.vue';
import CreateCampaignModal from '@/components/GoogleAds/CreateCampaignModal.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    currentBusiness: Object,
    integration: Object,
    analyticsData: Object,
    previousPeriodData: Object,
    campaignsData: Array,
    insights: Array,
    recommendations: Array,
    apiErrors: Array,
    panelType: {
        type: String,
        default: 'business',
        validator: (v) => ['business', 'marketing', 'finance', 'operator', 'saleshead'].includes(v),
    },
});

// Dynamic layout selection
const layoutComponent = computed(() => {
    const layouts = {
        business: BusinessLayout,
        marketing: MarketingLayout,
        finance: FinanceLayout,
        operator: OperatorLayout,
        saleshead: SalesHeadLayout,
    };
    return layouts[props.panelType] || BusinessLayout;
});

const activeTab = ref('overview');
const showCampaignModal = ref(false);
const campaignsTabRef = ref(null);
const isConnecting = ref(false);

const openCampaignModal = () => {
    showCampaignModal.value = true;
};

const closeCampaignModal = () => {
    showCampaignModal.value = false;
};

const onCampaignCreated = () => {
    closeCampaignModal();
    if (campaignsTabRef.value) {
        campaignsTabRef.value.loadCampaigns();
    }
};

// Use shared integrations route for campaigns
const goToCampaign = (campaign) => {
    router.visit(`/integrations/google-ads/campaigns/${campaign.id}`);
};

// Connect to Google Ads via shared integrations route
const connectGoogleAds = async () => {
    isConnecting.value = true;
    try {
        const response = await fetch('/integrations/google-ads/auth-url', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        });

        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.error('Response is not JSON:', await response.text());
            alert('Server xatosi. Sahifani yangilang va qayta urinib ko\'ring.');
            return;
        }

        const data = await response.json();

        if (!response.ok) {
            console.error('Server error:', data.error);
            alert(data.error || 'Integratsiya xatosi. Qayta urinib ko\'ring.');
            return;
        }

        if (data.url) {
            window.location.href = data.url;
        } else {
            console.error('No auth URL received:', data);
            alert(data.error || 'Integratsiya xatosi. Qayta urinib ko\'ring.');
        }
    } catch (error) {
        console.error('Connect error:', error);
        alert('Tarmoq xatosi. Internet aloqangizni tekshiring va qayta urinib ko\'ring.');
    } finally {
        isConnecting.value = false;
    }
};

// Disconnect Google Ads
const disconnectGoogleAds = () => {
    if (!confirm('Google Ads integratsiyasini uzmoqchimisiz?')) return;
    router.post('/integrations/google-ads/disconnect', {}, {
        onFinish: () => router.reload(),
    });
};

const formatNumber = (num) => {
    if (!num) return '0';
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toLocaleString();
};

const formatCurrency = (num) => {
    if (!num) return "0 so'm";
    return new Intl.NumberFormat('uz-UZ').format(num) + " so'm";
};

const formatPercent = (num) => {
    if (!num) return '0%';
    return num.toFixed(2) + '%';
};

const getTrendIcon = (trend) => {
    if (trend === 'up') return '↑';
    if (trend === 'down') return '↓';
    return '→';
};

const getTrendColor = (trend, inverse = false) => {
    if (inverse) {
        if (trend === 'up') return 'text-red-500';
        if (trend === 'down') return 'text-green-500';
    }
    if (trend === 'up') return 'text-green-500';
    if (trend === 'down') return 'text-red-500';
    return 'text-gray-500';
};

const getPriorityText = (priority) => {
    switch (priority) {
        case 'high': return 'Muhim';
        case 'medium': return "O'rtacha";
        case 'info': return "Ma'lumot";
        default: return priority;
    }
};
</script>

<template>
    <Head title="Google Ads Analitika" />

    <component :is="layoutComponent" title="Google Ads Analitika">
        <div class="space-y-6">
            <!-- Not Connected State -->
            <div v-if="!integration" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-8 text-center">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-green-500 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12.87 15.07l-2.54-2.51.03-.03c1.74-1.94 2.98-4.17 3.71-6.53H17V4h-7V2H8v2H1v1.99h11.17C11.5 7.92 10.44 9.75 9 11.35 8.07 10.32 7.3 9.19 6.69 8h-2c.73 1.63 1.73 3.17 2.98 4.56l-5.09 5.02L4 19l5-5 3.11 3.11.76-2.04zM18.5 10h-2L12 22h2l1.12-3h4.75L21 22h2l-4.5-12zm-2.62 7l1.62-4.33L19.12 17h-3.24z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Google Ads Ulang</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                    Google Ads hisobingizni ulang va reklama kampaniyalaringiz samaradorligini kuzating.
                </p>
                <button
                    @click="connectGoogleAds"
                    :disabled="isConnecting"
                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-green-500 text-white font-semibold rounded-xl hover:from-blue-600 hover:to-green-600 transition-all disabled:opacity-70 disabled:cursor-wait"
                >
                    <svg v-if="isConnecting" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                    {{ isConnecting ? 'Ulanmoqda...' : 'Google Ads Ulang' }}
                </button>
            </div>

            <!-- Connected State -->
            <template v-else>
                <!-- API Errors -->
                <div v-if="apiErrors && apiErrors.length > 0" class="p-4 bg-yellow-500/20 border border-yellow-500/30 rounded-xl">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-400 mr-3 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <h4 class="text-yellow-400 font-semibold mb-2">API xatoliklari:</h4>
                            <ul class="text-yellow-300 text-sm space-y-1">
                                <li v-for="(error, index) in apiErrors" :key="index">{{ error }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Account Header -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-green-500 rounded-xl flex items-center justify-center mr-4">
                                <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.87 15.07l-2.54-2.51.03-.03c1.74-1.94 2.98-4.17 3.71-6.53H17V4h-7V2H8v2H1v1.99h11.17C11.5 7.92 10.44 9.75 9 11.35 8.07 10.32 7.3 9.19 6.69 8h-2c.73 1.63 1.73 3.17 2.98 4.56l-5.09 5.02L4 19l5-5 3.11 3.11.76-2.04zM18.5 10h-2L12 22h2l1.12-3h4.75L21 22h2l-4.5-12zm-2.62 7l1.62-4.33L19.12 17h-3.24z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                                    {{ integration.account_name || 'Google Ads' }}
                                </h1>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Google Ads Analitika
                                </p>
                            </div>
                        </div>
                        <button
                            @click="disconnectGoogleAds"
                            class="text-sm text-gray-500 dark:text-gray-400 hover:text-red-500 transition-colors"
                        >
                            Integratsiyani uzish
                        </button>
                    </div>
                </div>

                <!-- Insights Section -->
                <div v-if="insights && insights.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <template v-for="(insight, index) in insights" :key="index">
                        <div
                            v-if="insight.type !== 'topCampaign'"
                            class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ insight.title }}</span>
                                <span
                                    v-if="insight.change !== undefined"
                                    class="text-sm font-medium"
                                    :class="insight.type === 'cost' ? getTrendColor(insight.trend, true) : getTrendColor(insight.trend)"
                                >
                                    {{ getTrendIcon(insight.trend) }} {{ Math.abs(insight.change) }}%
                                </span>
                            </div>
                            <p class="text-2xl font-bold" :class="{
                                'text-green-500': insight.color === 'green',
                                'text-red-500': insight.color === 'red',
                                'text-yellow-500': insight.color === 'yellow',
                                'text-blue-500': insight.color === 'blue',
                                'text-gray-900 dark:text-white': !insight.color || insight.color === 'default',
                            }">
                                <template v-if="insight.type === 'cost'">{{ formatCurrency(insight.value) }}</template>
                                <template v-else-if="insight.unit === '%'">{{ formatPercent(insight.value) }}</template>
                                <template v-else>{{ formatNumber(insight.value) }}{{ insight.unit || '' }}</template>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ insight.description }}</p>
                        </div>
                    </template>
                </div>

                <!-- Recommendations Section -->
                <div v-if="recommendations && recommendations.length > 0">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        Tavsiyalar va harakatlar
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="(rec, index) in recommendations"
                            :key="index"
                            class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col h-full"
                        >
                            <!-- Card Header -->
                            <div
                                class="px-4 py-3 border-b"
                                :class="{
                                    'bg-red-500/10 border-red-500/20': rec.priority === 'high',
                                    'bg-yellow-500/10 border-yellow-500/20': rec.priority === 'medium',
                                    'bg-blue-500/10 border-blue-500/20': rec.priority === 'info',
                                }"
                            >
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ rec.title }}</h3>
                                    <span
                                        class="px-2 py-0.5 text-xs font-medium rounded-full"
                                        :class="{
                                            'bg-red-500 text-white': rec.priority === 'high',
                                            'bg-yellow-500 text-white': rec.priority === 'medium',
                                            'bg-blue-500 text-white': rec.priority === 'info',
                                        }"
                                    >
                                        {{ getPriorityText(rec.priority) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="p-4 flex-1 flex flex-col">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ rec.description }}</p>

                                <!-- Actions -->
                                <div v-if="rec.actions && rec.actions.length > 0" class="mt-auto">
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Qilish kerak:</p>
                                    <ul class="space-y-2">
                                        <li v-for="(action, i) in rec.actions" :key="i" class="flex items-start text-sm">
                                            <span
                                                class="w-5 h-5 rounded-full flex items-center justify-center mr-2 flex-shrink-0 text-xs font-medium"
                                                :class="{
                                                    'bg-red-500/20 text-red-500': rec.priority === 'high',
                                                    'bg-yellow-500/20 text-yellow-600': rec.priority === 'medium',
                                                    'bg-blue-500/20 text-blue-500': rec.priority === 'info',
                                                }"
                                            >
                                                {{ i + 1 }}
                                            </span>
                                            <span class="text-gray-700 dark:text-gray-300">{{ action }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="flex -mb-px">
                            <button
                                @click="activeTab = 'overview'"
                                class="px-6 py-4 text-sm font-medium border-b-2 transition-colors"
                                :class="activeTab === 'overview' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                            >
                                Umumiy ko'rinish
                            </button>
                            <button
                                @click="activeTab = 'campaigns'"
                                class="px-6 py-4 text-sm font-medium border-b-2 transition-colors"
                                :class="activeTab === 'campaigns' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                            >
                                Kampaniyalar
                            </button>
                        </nav>
                    </div>

                    <!-- Overview Tab -->
                    <div v-if="activeTab === 'overview'" class="p-6">
                        <!-- 28-Day Stats -->
                        <div v-if="analyticsData">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Oxirgi 28 kun statistikasi</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Ko'rishlar</p>
                                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatNumber(analyticsData.totals?.impressions) }}</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kliklar</p>
                                    <p class="text-xl font-bold text-blue-500">{{ formatNumber(analyticsData.totals?.clicks) }}</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">CTR</p>
                                    <p class="text-xl font-bold text-purple-500">{{ formatPercent(analyticsData.totals?.ctr) }}</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Xarajat</p>
                                    <p class="text-xl font-bold text-orange-500">{{ formatCurrency(analyticsData.totals?.cost) }}</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Konversiya</p>
                                    <p class="text-xl font-bold text-green-500">{{ formatNumber(analyticsData.totals?.conversions) }}</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">CPC</p>
                                    <p class="text-xl font-bold text-cyan-500">{{ formatCurrency(analyticsData.totals?.cpc) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- No Data State -->
                        <div v-else class="text-center py-12">
                            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">Ma'lumotlar yuklanmadi</p>
                        </div>
                    </div>

                    <!-- Campaigns Tab -->
                    <div v-if="activeTab === 'campaigns'" class="p-6">
                        <CampaignsTab
                            ref="campaignsTabRef"
                            :business-id="currentBusiness.id"
                            :has-integration="!!integration"
                            @open-campaign-modal="openCampaignModal"
                            @campaign-selected="goToCampaign"
                        />
                    </div>
                </div>

                <!-- Create Campaign Modal -->
                <CreateCampaignModal
                    v-if="showCampaignModal"
                    :business-id="currentBusiness.id"
                    @close="closeCampaignModal"
                    @created="onCampaignCreated"
                />

                <!-- External Link & Disconnect -->
                <div class="flex justify-center items-center gap-6">
                    <a
                        href="https://ads.google.com"
                        target="_blank"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-green-500 text-white font-semibold rounded-xl hover:from-blue-600 hover:to-green-600 transition-all"
                    >
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12.87 15.07l-2.54-2.51.03-.03c1.74-1.94 2.98-4.17 3.71-6.53H17V4h-7V2H8v2H1v1.99h11.17C11.5 7.92 10.44 9.75 9 11.35 8.07 10.32 7.3 9.19 6.69 8h-2c.73 1.63 1.73 3.17 2.98 4.56l-5.09 5.02L4 19l5-5 3.11 3.11.76-2.04zM18.5 10h-2L12 22h2l1.12-3h4.75L21 22h2l-4.5-12zm-2.62 7l1.62-4.33L19.12 17h-3.24z"/>
                        </svg>
                        Google Ads ga o'tish
                    </a>
                    <button
                        @click="disconnectGoogleAds"
                        class="inline-flex items-center text-gray-500 dark:text-gray-400 hover:text-red-500 transition-colors"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Integratsiyani uzish
                    </button>
                </div>
            </template>
        </div>
    </component>
</template>
