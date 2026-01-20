<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from '@/i18n';
import {
    ChartBarIcon,
    UserGroupIcon,
    MegaphoneIcon,
    CalendarIcon,
    EyeIcon,
    ClockIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    HeartIcon,
    ChatBubbleLeftIcon,
    ShareIcon,
    DocumentTextIcon,
    PlusIcon,
    ArrowRightIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    panelType: {
        type: String,
        required: true,
        validator: (value) => ['business', 'marketing'].includes(value),
    },
    stats: Object,
    recentCampaigns: Array,
    upcomingContent: Array,
    currentBusiness: Object,
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

const formatCurrency = (value) => {
    if (!value) return '0 ' + t('common.currency');
    return new Intl.NumberFormat('uz-UZ').format(value) + ' ' + t('common.currency');
};

const formatNumber = (value) => {
    if (!value) return '0';
    if (value >= 1000000) {
        return (value / 1000000).toFixed(1) + 'M';
    }
    if (value >= 1000) {
        return (value / 1000).toFixed(1) + 'K';
    }
    return value.toString();
};

const budgetPercentage = computed(() => {
    if (!props.stats?.budget?.total || props.stats.budget.total === 0) return 0;
    return ((props.stats.budget.spent / props.stats.budget.total) * 100).toFixed(1);
});

const getStatusLabel = (status) => {
    const labels = {
        active: t('marketing.status_active'),
        completed: t('marketing.status_completed'),
        draft: t('marketing.status_draft'),
        paused: t('marketing.status_paused'),
        scheduled: t('marketing.status_scheduled'),
        approved: t('marketing.status_approved'),
        pending_review: t('marketing.status_pending_review'),
    };
    return labels[status] || status;
};

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        completed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
        paused: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        scheduled: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        approved: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        pending_review: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    };
    return colors[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400';
};

const getPlatformIcon = (platform) => {
    const icons = {
        instagram: 'IG',
        youtube: 'YT',
        facebook: 'FB',
        telegram: 'TG',
        twitter: 'X',
        linkedin: 'LI',
        email: '@',
    };
    return icons[platform?.toLowerCase()] || '?';
};

const getPlatformColor = (platform) => {
    const colors = {
        instagram: 'bg-gradient-to-br from-purple-500 to-pink-500',
        youtube: 'bg-red-500',
        facebook: 'bg-blue-600',
        telegram: 'bg-sky-500',
        twitter: 'bg-gray-900 dark:bg-gray-700',
        linkedin: 'bg-blue-700',
        email: 'bg-teal-500',
    };
    return colors[platform?.toLowerCase()] || 'bg-gray-500';
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const diff = date - now;
    const days = Math.ceil(diff / (1000 * 60 * 60 * 24));

    if (days === 0) return t('common.today');
    if (days === 1) return t('common.tomorrow');
    if (days > 1 && days < 7) return t('marketing.days_later', { days });

    return date.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' });
};
</script>

<template>
    <Head title="Marketing Dashboard" />

    <div class="space-y-6">
        <!-- Welcome Header -->
        <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-purple-700 rounded-2xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Marketing Dashboard</h1>
                    <p class="text-purple-100 mt-1">{{ currentBusiness?.name }} - Marketing statistikasi</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="getHref('/campaigns/create')"
                        class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-xl font-medium transition-colors flex items-center gap-2"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Yangi Kampaniya
                    </Link>
                </div>
            </div>
        </div>

        <!-- Main Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Campaigns -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-purple-100 dark:border-purple-900/30 shadow-sm hover:shadow-lg transition-all group">
                <div class="flex items-center justify-between">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <MegaphoneIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div class="flex items-center gap-1 text-green-600 dark:text-green-400 text-sm font-medium">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                        {{ stats?.campaigns?.active || 0 }} faol
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats?.campaigns?.total || 0 }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Jami kampaniyalar</p>
                </div>
                <div class="mt-3 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1">
                        <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                        {{ stats?.campaigns?.draft || 0 }} qoralama
                    </span>
                    <span class="flex items-center gap-1">
                        <div class="w-2 h-2 rounded-full bg-gray-400"></div>
                        {{ stats?.campaigns?.completed || 0 }} tugallangan
                    </span>
                </div>
            </div>

            <!-- Leads -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-purple-100 dark:border-purple-900/30 shadow-sm hover:shadow-lg transition-all group">
                <div class="flex items-center justify-between">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-100 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <UserGroupIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="flex items-center gap-1 text-sm font-medium" :class="stats?.leads?.growth >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                        <component :is="stats?.leads?.growth >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" class="w-4 h-4" />
                        {{ Math.abs(stats?.leads?.growth || 0) }}%
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats?.leads?.total || 0 }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Jami leadlar</p>
                </div>
                <div class="mt-3 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1">
                        <CheckCircleIcon class="w-3.5 h-3.5 text-green-500" />
                        {{ stats?.leads?.won || 0 }} yutilgan
                    </span>
                    <span>{{ stats?.leads?.conversion_rate || 0 }}% konversiya</span>
                </div>
            </div>

            <!-- Content -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-purple-100 dark:border-purple-900/30 shadow-sm hover:shadow-lg transition-all group">
                <div class="flex items-center justify-between">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-100 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <CalendarIcon class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                    </div>
                    <span class="text-sm font-medium text-orange-600 dark:text-orange-400">
                        {{ stats?.content?.posts_scheduled || 0 }} kutilmoqda
                    </span>
                </div>
                <div class="mt-4">
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats?.content?.posts_published || 0 }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Nashr qilingan</p>
                </div>
                <div class="mt-3 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1">
                        <DocumentTextIcon class="w-3.5 h-3.5" />
                        {{ stats?.content?.posts_draft || 0 }} qoralama
                    </span>
                    <span>{{ stats?.content?.engagement_rate || 0 }}% engagement</span>
                </div>
            </div>

            <!-- Social Reach -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-purple-100 dark:border-purple-900/30 shadow-sm hover:shadow-lg transition-all group">
                <div class="flex items-center justify-between">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-pink-100 to-rose-100 dark:from-pink-900/30 dark:to-rose-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <EyeIcon class="w-6 h-6 text-pink-600 dark:text-pink-400" />
                    </div>
                    <div class="flex items-center gap-1 text-sm font-medium" :class="stats?.social?.growth >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                        <component :is="stats?.social?.growth >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" class="w-4 h-4" />
                        {{ Math.abs(stats?.social?.growth || 0) }}%
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ formatNumber(stats?.social?.reach || 0) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Jami ko'rishlar</p>
                </div>
                <div class="mt-3 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1">
                        <HeartIcon class="w-3.5 h-3.5 text-red-500" />
                        {{ formatNumber(stats?.social?.likes || 0) }}
                    </span>
                    <span class="flex items-center gap-1">
                        <ChatBubbleLeftIcon class="w-3.5 h-3.5 text-blue-500" />
                        {{ formatNumber(stats?.social?.comments || 0) }}
                    </span>
                    <span class="flex items-center gap-1">
                        <ShareIcon class="w-3.5 h-3.5 text-green-500" />
                        {{ formatNumber(stats?.social?.shares || 0) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Budget & Tasks Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Budget Overview -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl p-6 border border-purple-100 dark:border-purple-900/30">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Marketing Byudjeti</h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ budgetPercentage }}% ishlatilgan</span>
                </div>
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats?.budget?.total) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Jami byudjet</p>
                    </div>
                    <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ formatCurrency(stats?.budget?.spent) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sarflangan</p>
                    </div>
                    <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency(stats?.budget?.remaining) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Qolgan</p>
                    </div>
                </div>
                <div class="relative">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                        <div
                            class="bg-gradient-to-r from-purple-600 to-pink-600 h-4 rounded-full transition-all duration-500"
                            :style="{ width: budgetPercentage + '%' }"
                        ></div>
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-xs font-medium text-white drop-shadow">{{ budgetPercentage }}%</span>
                    </div>
                </div>
            </div>

            <!-- Tasks Overview -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-purple-100 dark:border-purple-900/30">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Vazifalar</h3>
                    <Link :href="getHref('/tasks')" class="text-purple-600 hover:text-purple-700 dark:text-purple-400 text-sm font-medium">
                        Barchasi
                    </Link>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                                <ClockIcon class="w-4 h-4 text-yellow-600 dark:text-yellow-400" />
                            </div>
                            <span class="text-gray-700 dark:text-gray-300">Kutilmoqda</span>
                        </div>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ stats?.tasks?.pending || 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                <ChartBarIcon class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            <span class="text-gray-700 dark:text-gray-300">Jarayonda</span>
                        </div>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ stats?.tasks?.in_progress || 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                <ExclamationTriangleIcon class="w-4 h-4 text-red-600 dark:text-red-400" />
                            </div>
                            <span class="text-gray-700 dark:text-gray-300">Muddati o'tgan</span>
                        </div>
                        <span class="text-lg font-semibold text-red-600 dark:text-red-400">{{ stats?.tasks?.overdue || 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                <CheckCircleIcon class="w-4 h-4 text-green-600 dark:text-green-400" />
                            </div>
                            <span class="text-gray-700 dark:text-gray-300">Bajarilgan</span>
                        </div>
                        <span class="text-lg font-semibold text-green-600 dark:text-green-400">{{ stats?.tasks?.completed || 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Campaigns & Content Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Campaigns -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-purple-100 dark:border-purple-900/30 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Faol Kampaniyalar</h3>
                    <Link :href="getHref('/campaigns')" class="text-purple-600 hover:text-purple-700 dark:text-purple-400 text-sm font-medium flex items-center gap-1">
                        Barchasi
                        <ArrowRightIcon class="w-4 h-4" />
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-if="!recentCampaigns || recentCampaigns.length === 0" class="p-8 text-center">
                    <div class="w-16 h-16 mx-auto rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-4">
                        <MegaphoneIcon class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                    </div>
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Kampaniyalar yo'q</h4>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">Birinchi marketing kampaniyangizni yarating</p>
                    <Link
                        :href="getHref('/campaigns/create')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-xl transition-colors"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Kampaniya yaratish
                    </Link>
                </div>

                <!-- Campaign List -->
                <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div v-for="campaign in recentCampaigns" :key="campaign.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 dark:text-white truncate">{{ campaign.name }}</h4>
                                <div class="flex items-center gap-2 mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ campaign.leads }} lead</span>
                                    <span class="text-gray-300 dark:text-gray-600">|</span>
                                    <span>{{ formatCurrency(campaign.spent) }}</span>
                                </div>
                            </div>
                            <span :class="['px-2.5 py-1 text-xs font-medium rounded-full', getStatusColor(campaign.status)]">
                                {{ getStatusLabel(campaign.status) }}
                            </span>
                        </div>
                        <div class="mt-3">
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                <span>Byudjet ishlatilishi</span>
                                <span>{{ campaign.progress || 0 }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                <div
                                    class="bg-gradient-to-r from-purple-600 to-pink-600 h-1.5 rounded-full transition-all"
                                    :style="{ width: (campaign.progress || 0) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Content -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-purple-100 dark:border-purple-900/30 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rejalashtirilgan Kontent</h3>
                    <Link :href="getHref('/content')" class="text-purple-600 hover:text-purple-700 dark:text-purple-400 text-sm font-medium flex items-center gap-1">
                        Kalendarni ko'rish
                        <ArrowRightIcon class="w-4 h-4" />
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-if="!upcomingContent || upcomingContent.length === 0" class="p-8 text-center">
                    <div class="w-16 h-16 mx-auto rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mb-4">
                        <CalendarIcon class="w-8 h-8 text-orange-600 dark:text-orange-400" />
                    </div>
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Rejalashtirilgan kontent yo'q</h4>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">Kontent rejasini kalendarga qo'shing</p>
                    <Link
                        :href="getHref('/content')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-xl transition-colors"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Kontent qo'shish
                    </Link>
                </div>

                <!-- Content List -->
                <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div v-for="content in upcomingContent" :key="content.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', getPlatformColor(content.platform)]">
                                <span class="text-white text-sm font-bold">{{ getPlatformIcon(content.platform) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 dark:text-white truncate">{{ content.title }}</h4>
                                <div class="flex items-center gap-2 mt-1">
                                    <span :class="['px-2 py-0.5 text-xs font-medium rounded-full', getStatusColor(content.status)]">
                                        {{ getStatusLabel(content.status) }}
                                    </span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(content.scheduled_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- This Month Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-purple-100 dark:border-purple-900/30">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Bu oy statistikasi</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl">
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ stats?.leads?.this_month || 0 }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Yangi leadlar</p>
                </div>
                <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl">
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ formatNumber(stats?.content?.total_views || 0) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Kontent ko'rishlar</p>
                </div>
                <div class="text-center p-4 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl">
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ stats?.tasks?.today || 0 }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Bugungi vazifalar</p>
                </div>
                <div class="text-center p-4 bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-xl">
                    <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ stats?.content?.engagement_rate || 0 }}%</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Engagement rate</p>
                </div>
            </div>
        </div>
    </div>
</template>
