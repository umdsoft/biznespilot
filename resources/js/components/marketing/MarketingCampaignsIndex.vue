<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, h, watch } from 'vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    MegaphoneIcon,
    ChartBarIcon,
    CurrencyDollarIcon,
    UserGroupIcon,
    PlayIcon,
    PauseIcon,
    CheckCircleIcon,
    DocumentTextIcon,
    ArrowRightIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    panelType: {
        type: String,
        required: true,
        validator: (value) => ['business', 'marketing'].includes(value),
    },
    campaigns: {
        type: Array,
        default: () => []
    },
    stats: Object,
    filters: Object,
    currentBusiness: Object
});

// Helper to generate correct href based on panel type
const getHref = (path) => {
    const prefix = props.panelType === 'business' ? '/business/marketing' : '/marketing';
    return prefix + path;
};

// Helper to get route name based on panel type
const getRouteName = (name) => {
    const prefix = props.panelType === 'business' ? 'business.marketing.' : 'marketing.';
    return prefix + name;
};

const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const typeFilter = ref(props.filters?.type || '');
const showFilters = ref(false);
const activeFilter = ref('all');

// Computed stats
const activeCampaigns = computed(() => props.campaigns.filter(c => c.status === 'running' || c.status === 'active').length);
const scheduledCampaigns = computed(() => props.campaigns.filter(c => c.status === 'scheduled').length);
const totalSentMessages = computed(() => props.campaigns.reduce((sum, c) => sum + (c.sent_count || 0), 0));

const filterOptions = computed(() => [
    { value: 'all', label: 'Barchasi', count: props.campaigns.length },
    { value: 'running', label: 'Faol', count: activeCampaigns.value },
    { value: 'scheduled', label: 'Rejalashtirilgan', count: scheduledCampaigns.value },
    { value: 'draft', label: 'Qoralama', count: props.campaigns.filter(c => c.status === 'draft').length },
    { value: 'completed', label: 'Yakunlangan', count: props.campaigns.filter(c => c.status === 'completed').length }
]);

const filteredCampaigns = computed(() => {
    let result = props.campaigns;

    if (activeFilter.value !== 'all') {
        result = result.filter(c => c.status === activeFilter.value || (activeFilter.value === 'running' && c.status === 'active'));
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(c => c.name.toLowerCase().includes(query));
    }

    return result;
});

const hasActiveFilters = computed(() => {
    return searchQuery.value || activeFilter.value !== 'all';
});

// Format helpers
const formatCurrency = (value) => {
    if (!value) return '0 so\'m';
    return new Intl.NumberFormat('uz-UZ').format(value) + ' so\'m';
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short', year: 'numeric' });
};

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        running: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        completed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        paused: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
        scheduled: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        failed: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
    };
    return classes[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400';
};

const getStatusLabel = (status) => {
    const labels = {
        active: 'Faol',
        running: 'Faol',
        completed: 'Tugallangan',
        paused: 'To\'xtatilgan',
        draft: 'Qoralama',
        scheduled: 'Rejalashtirilgan',
        failed: 'Xatolik'
    };
    return labels[status] || status;
};

const getStatusIcon = (status) => {
    const icons = {
        active: PlayIcon,
        running: PlayIcon,
        completed: CheckCircleIcon,
        paused: PauseIcon,
        draft: DocumentTextIcon,
        scheduled: DocumentTextIcon
    };
    return icons[status] || DocumentTextIcon;
};

const getTypeName = (type) => {
    const names = {
        broadcast: 'Ommaviy',
        drip: 'Drip',
        trigger: 'Trigger',
        promotion: 'Aksiya',
        branding: 'Brending'
    };
    return names[type] || type;
};

const getTypeBadgeClass = (type) => {
    const classes = {
        broadcast: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        drip: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
        trigger: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        promotion: 'bg-pink-100 text-pink-700 dark:bg-pink-900/40 dark:text-pink-300',
        branding: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300'
    };
    return classes[type] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
};

const getChannelName = (channel) => {
    const names = {
        instagram: 'Instagram',
        telegram: 'Telegram',
        facebook: 'Facebook',
        email: 'Email',
        sms: 'SMS',
        all: 'Barcha kanallar'
    };
    return names[channel] || channel;
};

const getChannelIcon = (channel) => {
    const icons = {
        instagram: 'IG',
        facebook: 'FB',
        telegram: 'TG',
        email: '@',
        sms: 'SMS',
        all: 'ALL'
    };
    return icons[channel?.toLowerCase()] || '?';
};

const getChannelColor = (channel) => {
    const colors = {
        instagram: 'bg-gradient-to-br from-purple-500 to-pink-500',
        facebook: 'bg-blue-600',
        telegram: 'bg-sky-500',
        email: 'bg-teal-500',
        sms: 'bg-orange-500',
        all: 'bg-gray-700'
    };
    return colors[channel?.toLowerCase()] || 'bg-gray-500';
};

const getSuccessRate = (campaign) => {
    const total = (campaign.sent_count || 0) + (campaign.failed_count || 0);
    if (total === 0) return 0;
    return Math.round((campaign.sent_count || 0) / total * 100);
};

const budgetProgress = (campaign) => {
    if (!campaign.budget || campaign.budget === 0) return 0;
    return Math.min(100, Math.round((campaign.spent / campaign.budget) * 100));
};

// Actions
const launchCampaign = (campaignId) => {
    if (confirm('Kampaniyani ishga tushirishni xohlaysizmi?')) {
        router.post(getHref(`/campaigns/${campaignId}/launch`), {}, {
            preserveScroll: true
        });
    }
};

const pauseCampaign = (campaignId) => {
    if (confirm('Kampaniyani to\'xtatishni xohlaysizmi?')) {
        router.post(getHref(`/campaigns/${campaignId}/pause`), {}, {
            preserveScroll: true
        });
    }
};

const deleteCampaign = (campaignId) => {
    if (confirm('Kampaniyani o\'chirishni xohlaysizmi? Bu amalni ortga qaytarib bo\'lmaydi.')) {
        router.delete(getHref(`/campaigns/${campaignId}`), {
            preserveScroll: true
        });
    }
};

const clearFilters = () => {
    searchQuery.value = '';
    activeFilter.value = 'all';
};
</script>

<template>
    <Head title="Marketing Kampaniyalari" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Marketing Kampaniyalari</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Barcha kanallar bo'ylab marketing kampaniyalarini boshqaring
                </p>
            </div>
            <Link
                :href="getHref('/campaigns/create')"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-sm font-medium rounded-xl shadow-lg shadow-purple-500/25 hover:shadow-purple-500/40 transition-all duration-200"
            >
                <PlusIcon class="w-5 h-5 mr-2" />
                Yangi Kampaniya
            </Link>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Jami Kampaniyalar -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Jami Kampaniyalar</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ campaigns.length }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900/40 dark:to-indigo-900/40 rounded-xl flex items-center justify-center">
                        <MegaphoneIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
            </div>

            <!-- Faol Kampaniyalar -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Faol Kampaniyalar</p>
                        <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                            {{ activeCampaigns }}
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-emerald-100 to-green-100 dark:from-emerald-900/40 dark:to-green-900/40 rounded-xl flex items-center justify-center">
                        <PlayIcon class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>
                <div class="mt-3 flex items-center text-xs text-emerald-600 dark:text-emerald-400">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full mr-2 animate-pulse"></span>
                    Hozir ishlayapti
                </div>
            </div>

            <!-- Yuborilgan Xabarlar -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Yuborilgan Xabarlar</p>
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                            {{ totalSentMessages }}
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-cyan-100 dark:from-blue-900/40 dark:to-cyan-900/40 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Rejalashtirilgan -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rejalashtirilgan</p>
                        <p class="text-3xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                            {{ scheduledCampaigns }}
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/40 dark:to-orange-900/40 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Filter Tabs -->
        <div class="space-y-4">
            <!-- Search -->
            <div class="relative max-w-md">
                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Kampaniya qidirish..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                />
            </div>

            <!-- Filter Tabs -->
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="filter in filterOptions"
                    :key="filter.value"
                    @click="activeFilter = filter.value"
                    class="px-4 py-2 text-sm font-medium rounded-xl transition-all duration-200"
                    :class="activeFilter === filter.value
                        ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/25'
                        : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600'"
                >
                    {{ filter.label }}
                    <span
                        v-if="filter.count > 0"
                        class="ml-1.5 px-1.5 py-0.5 text-xs rounded-full"
                        :class="activeFilter === filter.value
                            ? 'bg-white/20'
                            : 'bg-gray-100 dark:bg-gray-700'"
                    >
                        {{ filter.count }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Campaigns List -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <!-- Empty State -->
            <div v-if="filteredCampaigns.length === 0" class="p-12 text-center">
                <div class="w-20 h-20 mx-auto bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900/40 dark:to-indigo-900/40 rounded-2xl flex items-center justify-center mb-4">
                    <MegaphoneIcon class="w-10 h-10 text-purple-600 dark:text-purple-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ hasActiveFilters ? 'Natija topilmadi' : 'Kampaniya yo\'q' }}
                </h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                    {{ hasActiveFilters
                        ? 'Filtrlarni o\'zgartirib ko\'ring'
                        : 'Marketing kampaniyalari orqali mijozlaringizga xabarlar yuboring va ularni jalb qiling.'
                    }}
                </p>
                <div class="mt-6">
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors mr-3"
                    >
                        <XMarkIcon class="w-5 h-5 mr-2" />
                        Filtrlarni tozalash
                    </button>
                    <Link
                        :href="getHref('/campaigns/create')"
                        class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-sm font-medium rounded-xl shadow-lg shadow-purple-500/25 hover:shadow-purple-500/40 transition-all duration-200"
                    >
                        <PlusIcon class="w-5 h-5 mr-2" />
                        Birinchi Kampaniyani Yarating
                    </Link>
                </div>
            </div>

            <!-- Campaign Cards -->
            <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                <div
                    v-for="campaign in filteredCampaigns"
                    :key="campaign.id"
                    class="p-4 sm:p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
                >
                    <div class="flex items-start gap-4">
                        <!-- Channel Icon -->
                        <div :class="['w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0', getChannelColor(campaign.channel)]">
                            <span class="text-white text-sm font-bold">{{ getChannelIcon(campaign.channel) }}</span>
                        </div>

                        <!-- Campaign Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-1 flex-wrap">
                                <Link
                                    :href="getHref(`/campaigns/${campaign.id}`)"
                                    class="text-lg font-semibold text-gray-900 dark:text-white truncate group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors"
                                >
                                    {{ campaign.name }}
                                </Link>
                                <span :class="['px-2.5 py-1 text-xs font-medium rounded-full flex items-center gap-1', getStatusClass(campaign.status)]">
                                    <component :is="getStatusIcon(campaign.status)" class="w-3 h-3" />
                                    {{ getStatusLabel(campaign.status) }}
                                </span>
                                <span
                                    class="px-2.5 py-1 text-xs font-medium rounded-lg"
                                    :class="getTypeBadgeClass(campaign.type)"
                                >
                                    {{ getTypeName(campaign.type) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 flex-wrap">
                                <span>{{ getChannelName(campaign.channel) }}</span>
                                <span>{{ formatDate(campaign.created_at) }}</span>
                                <span v-if="campaign.leads" class="flex items-center gap-1">
                                    <UserGroupIcon class="w-4 h-4" />
                                    {{ campaign.leads }} lead
                                </span>
                            </div>

                            <!-- Progress Bar -->
                            <div v-if="campaign.sent_count || campaign.budget" class="mt-3 flex items-center gap-6">
                                <div v-if="campaign.sent_count" class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Yuborildi:</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ campaign.sent_count || 0 }}</span>
                                    <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                        {{ getSuccessRate(campaign) }}%
                                    </span>
                                </div>
                                <div v-if="campaign.budget > 0" class="flex-1 max-w-[200px]">
                                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                        <span>Byudjet</span>
                                        <span>{{ budgetProgress(campaign) }}%</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div
                                            class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full transition-all duration-500"
                                            :style="{ width: budgetProgress(campaign) + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button
                                v-if="campaign.status === 'draft' || campaign.status === 'scheduled'"
                                @click.prevent="launchCampaign(campaign.id)"
                                class="p-2 text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/30 rounded-lg transition-colors"
                                title="Ishga tushirish"
                            >
                                <PlayIcon class="w-5 h-5" />
                            </button>
                            <button
                                v-if="campaign.status === 'running' || campaign.status === 'active'"
                                @click.prevent="pauseCampaign(campaign.id)"
                                class="p-2 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition-colors"
                                title="To'xtatish"
                            >
                                <PauseIcon class="w-5 h-5" />
                            </button>
                            <Link
                                :href="getHref(`/campaigns/${campaign.id}`)"
                                class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                                title="Ko'rish"
                            >
                                <ArrowRightIcon class="w-5 h-5" />
                            </Link>
                            <button
                                @click.prevent="deleteCampaign(campaign.id)"
                                class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                title="O'chirish"
                            >
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Tips -->
        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-2xl p-6 border border-purple-100 dark:border-purple-800/30">
            <div class="flex items-start">
                <div class="w-10 h-10 bg-white dark:bg-gray-800 rounded-xl flex items-center justify-center shadow-sm mr-4 flex-shrink-0">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Kampaniya turlari haqida</h4>
                    <ul class="mt-2 space-y-1.5 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-center">
                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full mr-2"></span>
                            <strong class="text-gray-700 dark:text-gray-300">Ommaviy:</strong>&nbsp;Bir vaqtda ko'p mijozlarga xabar yuborish
                        </li>
                        <li class="flex items-center">
                            <span class="w-1.5 h-1.5 bg-purple-500 rounded-full mr-2"></span>
                            <strong class="text-gray-700 dark:text-gray-300">Drip:</strong>&nbsp;Ketma-ket avtomatik xabarlar zanjiri
                        </li>
                        <li class="flex items-center">
                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-2"></span>
                            <strong class="text-gray-700 dark:text-gray-300">Trigger:</strong>&nbsp;Ma'lum hodisalarga javob sifatida yuboriladi
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
