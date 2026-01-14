<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    currentBusiness: Object,
    integration: Object,
    channelData: Object,
    analyticsData: Object,
    previousPeriodData: Object,
    recentVideos: Array,
    trafficSources: Array,
    demographics: Object,
    countries: Array,
    insights: Array,
    recommendations: Array,
    apiErrors: Array,
});

const page = usePage();
const flash = computed(() => page.props.flash || {});
const isSyncing = ref(false);
const activeTab = ref('overview');

const syncData = () => {
    isSyncing.value = true;
    router.post(route('business.youtube-analytics.sync'), {}, {
        onFinish: () => {
            isSyncing.value = false;
            router.reload();
        },
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

const formatDuration = (seconds) => {
    if (!seconds) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('uz-UZ', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getTrendIcon = (trend) => {
    if (trend === 'up') return '↑';
    if (trend === 'down') return '↓';
    return '→';
};

const getTrendColor = (trend, isGood = true) => {
    if (trend === 'up') return isGood ? 'text-green-500' : 'text-red-500';
    if (trend === 'down') return isGood ? 'text-red-500' : 'text-green-500';
    return 'text-gray-500';
};

const getPriorityBadge = (priority) => {
    const badges = {
        high: 'bg-red-500/20 text-red-400 border-red-500/30',
        medium: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
        info: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
    };
    return badges[priority] || badges.info;
};

const getPriorityText = (priority) => {
    const texts = {
        high: 'Muhim',
        medium: "O'rtacha",
        info: 'Ma\'lumot',
    };
    return texts[priority] || 'Info';
};

// Top performing videos (sorted by views)
const topVideos = computed(() => {
    if (!props.recentVideos || !props.recentVideos.length) return [];
    return [...props.recentVideos].sort((a, b) => b.views - a.views).slice(0, 5);
});

// Most engaging videos (sorted by engagement rate)
const mostEngagingVideos = computed(() => {
    if (!props.recentVideos || !props.recentVideos.length) return [];
    return [...props.recentVideos]
        .map(v => ({
            ...v,
            engagementRate: v.views > 0 ? ((v.likes + v.comments) / v.views * 100) : 0
        }))
        .sort((a, b) => b.engagementRate - a.engagementRate)
        .slice(0, 5);
});
</script>

<template>
    <Head title="YouTube Analitika" />

    <BusinessLayout title="YouTube Analitika">
        <div class="space-y-6">
            <!-- Flash Messages -->
            <div v-if="flash.success" class="p-4 bg-green-500/20 border border-green-500/30 rounded-xl flex items-center">
                <svg class="w-5 h-5 text-green-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <p class="text-green-400">{{ flash.success }}</p>
            </div>

            <div v-if="flash.error" class="p-4 bg-red-500/20 border border-red-500/30 rounded-xl flex items-center">
                <svg class="w-5 h-5 text-red-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-red-400">{{ flash.error }}</p>
            </div>

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
                        <Link
                            :href="route('business.settings.youtube')"
                            class="inline-flex items-center mt-3 text-sm text-yellow-400 hover:text-yellow-300"
                        >
                            Qaytadan ulash uchun bu yerga bosing
                            <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Not Connected State -->
            <div v-if="!integration || !integration.is_connected" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                <div class="w-20 h-20 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-red-500" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">YouTube ulanmagan</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">
                    YouTube kanalingizni ulang va video statistikalaringizni to'g'ridan-to'g'ri BiznesPilot orqali ko'ring
                </p>
                <Link
                    :href="route('business.settings.youtube')"
                    class="inline-flex items-center px-6 py-3 bg-red-500 text-white font-semibold rounded-xl hover:bg-red-600 transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                    YouTube bilan ulash
                </Link>
            </div>

            <!-- Connected State -->
            <template v-else>
                <!-- Header with Channel Info -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <img
                                v-if="channelData?.thumbnail_medium"
                                :src="channelData.thumbnail_medium"
                                :alt="channelData?.title"
                                class="w-16 h-16 rounded-full mr-4"
                            />
                            <div v-else class="w-16 h-16 bg-red-500/20 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-8 h-8 text-red-500" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ channelData?.title || integration.channel_name || 'YouTube Kanal' }}
                                </h1>
                                <p class="text-gray-500 dark:text-gray-400">
                                    {{ formatNumber(channelData?.subscribers) }} obunachi · {{ formatNumber(channelData?.videos) }} video
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                Yangilangan: {{ formatDate(integration.last_synced_at) }}
                            </span>
                            <button
                                @click="syncData"
                                :disabled="isSyncing"
                                class="inline-flex items-center px-4 py-2 bg-red-500 text-white font-medium rounded-xl hover:bg-red-600 transition-colors disabled:opacity-50"
                            >
                                <svg
                                    class="w-5 h-5 mr-2"
                                    :class="{ 'animate-spin': isSyncing }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ isSyncing ? 'Yangilanmoqda...' : 'Yangilash' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Key Insights Cards -->
                <div v-if="insights && insights.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                    <template v-for="insight in insights.slice(0, 5)" :key="insight.type">
                        <div
                            v-if="insight.type !== 'topVideo'"
                            class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ insight.title }}</span>
                                <span
                                    v-if="insight.trend"
                                    :class="getTrendColor(insight.trend)"
                                    class="text-sm font-medium"
                                >
                                    {{ getTrendIcon(insight.trend) }} {{ Math.abs(insight.change || 0) }}%
                                </span>
                            </div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ insight.formatted || formatNumber(insight.value) }}{{ insight.unit || '' }}
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
                                :class="activeTab === 'overview' ? 'border-red-500 text-red-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                                class="px-6 py-4 text-sm font-medium border-b-2 transition-colors"
                            >
                                Umumiy ko'rinish
                            </button>
                            <button
                                @click="activeTab = 'videos'"
                                :class="activeTab === 'videos' ? 'border-red-500 text-red-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                                class="px-6 py-4 text-sm font-medium border-b-2 transition-colors"
                            >
                                Videolar
                            </button>
                            <button
                                @click="activeTab = 'audience'"
                                :class="activeTab === 'audience' ? 'border-red-500 text-red-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                                class="px-6 py-4 text-sm font-medium border-b-2 transition-colors"
                            >
                                Auditoriya
                            </button>
                            <button
                                @click="activeTab = 'traffic'"
                                :class="activeTab === 'traffic' ? 'border-red-500 text-red-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                                class="px-6 py-4 text-sm font-medium border-b-2 transition-colors"
                            >
                                Trafik
                            </button>
                        </nav>
                    </div>

                    <!-- Overview Tab -->
                    <div v-if="activeTab === 'overview'" class="p-6">
                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- Top Performing Videos -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Eng ko'p ko'rilgan videolar</h3>
                                <div class="space-y-3">
                                    <Link
                                        v-for="video in topVideos"
                                        :key="video.id"
                                        :href="route('business.youtube-analytics.video', { videoId: video.id })"
                                        class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                    >
                                        <img
                                            v-if="video.thumbnail"
                                            :src="video.thumbnail"
                                            :alt="video.title"
                                            class="w-20 h-12 object-cover rounded-lg mr-3 flex-shrink-0"
                                        />
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-gray-900 dark:text-white text-sm line-clamp-1">{{ video.title }}</p>
                                            <p class="text-xs text-gray-500">{{ formatNumber(video.views) }} ko'rish</p>
                                        </div>
                                        <!-- Ko'rishlar soni badge -->
                                        <div class="ml-2 flex-shrink-0">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-green-500/20 text-green-500 text-xs font-bold">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                {{ formatNumber(video.views) }}
                                            </span>
                                        </div>
                                    </Link>
                                </div>
                            </div>

                            <!-- Most Engaging Videos -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Eng faol videolar (engagement)</h3>
                                <div class="space-y-3">
                                    <Link
                                        v-for="video in mostEngagingVideos"
                                        :key="video.id"
                                        :href="route('business.youtube-analytics.video', { videoId: video.id })"
                                        class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                    >
                                        <img
                                            v-if="video.thumbnail"
                                            :src="video.thumbnail"
                                            :alt="video.title"
                                            class="w-20 h-12 object-cover rounded-lg mr-3 flex-shrink-0"
                                        />
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-gray-900 dark:text-white text-sm line-clamp-1">{{ video.title }}</p>
                                            <p class="text-xs text-gray-500">{{ video.engagementRate.toFixed(2) }}% faollik</p>
                                        </div>
                                        <!-- Engagement rate badge -->
                                        <div class="ml-2 flex-shrink-0">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-purple-500/20 text-purple-500 text-xs font-bold">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                                {{ video.engagementRate.toFixed(2) }}%
                                            </span>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- 28-Day Stats -->
                        <div v-if="analyticsData" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Oxirgi 28 kun statistikasi</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Ko'rishlar</p>
                                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatNumber(analyticsData.totals?.views) }}</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Obunachilar</p>
                                    <p class="text-xl font-bold text-green-500">+{{ formatNumber(analyticsData.totals?.subscribersGained) }}</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Layklar</p>
                                    <p class="text-xl font-bold text-blue-500">{{ formatNumber(analyticsData.totals?.likes) }}</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Izohlar</p>
                                    <p class="text-xl font-bold text-purple-500">{{ formatNumber(analyticsData.totals?.comments) }}</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Ulashishlar</p>
                                    <p class="text-xl font-bold text-orange-500">{{ formatNumber(analyticsData.totals?.shares) }}</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">O'rtacha</p>
                                    <p class="text-xl font-bold text-cyan-500">{{ formatDuration(analyticsData.totals?.avgViewDuration) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Videos Tab -->
                    <div v-if="activeTab === 'videos'" class="p-6">
                        <div v-if="recentVideos && recentVideos.length > 0" class="space-y-4">
                            <Link
                                v-for="video in recentVideos"
                                :key="video.id"
                                :href="route('business.youtube-analytics.video', { videoId: video.id })"
                                class="flex items-start p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group cursor-pointer"
                            >
                                <div class="relative flex-shrink-0">
                                    <img
                                        v-if="video.thumbnail"
                                        :src="video.thumbnail"
                                        :alt="video.title"
                                        class="w-40 h-24 object-cover rounded-lg mr-4"
                                    />
                                    <div v-if="video.duration_formatted" class="absolute bottom-1 right-5 px-1.5 py-0.5 bg-black/80 text-white text-xs rounded">
                                        {{ video.duration_formatted }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 dark:text-white line-clamp-2 mb-1 group-hover:text-red-500 transition-colors">
                                        {{ video.title }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                                        {{ formatDate(video.published_at) }}
                                    </p>
                                    <div class="flex items-center space-x-4 text-sm">
                                        <span class="flex items-center text-gray-600 dark:text-gray-300">
                                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            {{ formatNumber(video.views) }}
                                        </span>
                                        <span class="flex items-center text-gray-600 dark:text-gray-300">
                                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                            </svg>
                                            {{ formatNumber(video.likes) }}
                                        </span>
                                        <span class="flex items-center text-gray-600 dark:text-gray-300">
                                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            {{ formatNumber(video.comments) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4 flex items-center space-x-2">
                                    <span class="text-sm text-gray-400 group-hover:text-red-500 transition-colors">
                                        Batafsil
                                    </span>
                                    <svg class="w-5 h-5 text-gray-400 group-hover:text-red-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </Link>
                        </div>
                        <div v-else class="text-center py-12">
                            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">Videolar topilmadi</p>
                        </div>
                    </div>

                    <!-- Audience Tab -->
                    <div v-if="activeTab === 'audience'" class="p-6">
                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- Demographics -->
                            <div v-if="demographics && demographics.byAge">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Yosh guruhlari</h3>
                                <div class="space-y-3">
                                    <div v-for="(percentage, age) in demographics.byAge" :key="age" class="flex items-center">
                                        <span class="w-20 text-sm text-gray-700 dark:text-gray-300">{{ age }}</span>
                                        <div class="flex-1 h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mx-3">
                                            <div
                                                class="h-full bg-red-500 rounded-full"
                                                :style="{ width: percentage + '%' }"
                                            ></div>
                                        </div>
                                        <span class="w-12 text-sm text-gray-600 dark:text-gray-400 text-right">{{ percentage }}%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Gender Distribution -->
                            <div v-if="demographics && demographics.byGender">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Jins</h3>
                                <div class="flex items-center justify-center space-x-8">
                                    <div class="text-center">
                                        <div class="w-24 h-24 rounded-full bg-blue-500/20 flex items-center justify-center mb-2">
                                            <span class="text-2xl font-bold text-blue-500">{{ demographics.byGender.male }}%</span>
                                        </div>
                                        <span class="text-gray-600 dark:text-gray-400">Erkaklar</span>
                                    </div>
                                    <div class="text-center">
                                        <div class="w-24 h-24 rounded-full bg-pink-500/20 flex items-center justify-center mb-2">
                                            <span class="text-2xl font-bold text-pink-500">{{ demographics.byGender.female }}%</span>
                                        </div>
                                        <span class="text-gray-600 dark:text-gray-400">Ayollar</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Countries -->
                            <div v-if="countries && countries.length > 0" class="md:col-span-2">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Geografiya</h3>
                                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                    <div
                                        v-for="country in countries"
                                        :key="country.code"
                                        class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl text-center"
                                    >
                                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ country.percentage }}%</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ country.name }}</p>
                                        <p class="text-xs text-gray-400">{{ formatNumber(country.views) }} ko'rish</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Traffic Tab -->
                    <div v-if="activeTab === 'traffic'" class="p-6">
                        <div v-if="trafficSources && trafficSources.length > 0">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Trafik manbalari</h3>
                            <div class="space-y-4">
                                <div
                                    v-for="source in trafficSources"
                                    :key="source.sourceKey"
                                    class="flex items-center"
                                >
                                    <span class="w-40 text-sm text-gray-700 dark:text-gray-300">{{ source.source }}</span>
                                    <div class="flex-1 h-6 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mx-4">
                                        <div
                                            class="h-full bg-gradient-to-r from-red-500 to-red-400 rounded-full flex items-center justify-end pr-2"
                                            :style="{ width: Math.max(source.percentage, 5) + '%' }"
                                        >
                                            <span v-if="source.percentage > 15" class="text-xs text-white font-medium">{{ source.percentage }}%</span>
                                        </div>
                                    </div>
                                    <div class="w-28 text-right">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ formatNumber(source.views) }}</span>
                                        <span v-if="source.percentage <= 15" class="text-xs text-gray-500 ml-1">({{ source.percentage }}%)</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Traffic insights -->
                            <div class="mt-6 p-4 bg-blue-500/10 border border-blue-500/30 rounded-xl">
                                <h4 class="font-semibold text-blue-400 mb-2">Trafik bo'yicha tavsiya</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <template v-if="trafficSources[0]?.sourceKey === 'YT_SEARCH'">
                                        YouTube qidiruvi asosiy trafik manbaingiz - bu SEO yaxshi ishlayotganini bildiradi. Kalit so'zlarni optimizatsiya qilishni davom ettiring.
                                    </template>
                                    <template v-else-if="trafficSources[0]?.sourceKey === 'RELATED_VIDEO'">
                                        Tavsiya etilgan videolar asosiy trafik manbaingiz - bu YouTube algoritmi videolaringizni yoqtirayotganini bildiradi.
                                    </template>
                                    <template v-else-if="trafficSources[0]?.sourceKey === 'EXT_URL'">
                                        Tashqi havolalar asosiy trafik manbaingiz - ijtimoiy tarmoqlar va veb-saytlar orqali targ'ibot yaxshi ishlayapti.
                                    </template>
                                    <template v-else>
                                        Trafik manbalarini diversifikatsiya qilish uchun SEO, ijtimoiy tarmoqlar va end screen'lardan foydalaning.
                                    </template>
                                </p>
                            </div>
                        </div>
                        <div v-else class="text-center py-12">
                            <p class="text-gray-500 dark:text-gray-400">Trafik ma'lumotlari topilmadi</p>
                        </div>
                    </div>
                </div>

                <!-- Settings Link -->
                <div class="text-center">
                    <Link
                        :href="route('business.settings.youtube')"
                        class="inline-flex items-center text-gray-500 dark:text-gray-400 hover:text-red-500 transition-colors"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        YouTube integratsiya sozlamalari
                    </Link>
                </div>
            </template>
        </div>
    </BusinessLayout>
</template>
