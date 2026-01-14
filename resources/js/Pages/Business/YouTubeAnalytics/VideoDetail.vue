<script setup>
import { Head, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    currentBusiness: Object,
    videoData: Object,
    videoAnalytics: Object,
    apiErrors: Array,
});

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
        month: 'long',
        day: 'numeric',
    });
};

const formatPercent = (num) => {
    if (!num) return '0%';
    return Math.round(num) + '%';
};

// Calculate engagement rate
const engagementRate = computed(() => {
    if (!props.videoData || !props.videoData.views) return '0%';
    const engagement = props.videoData.likes + props.videoData.comments;
    const rate = (engagement / props.videoData.views) * 100;
    return rate.toFixed(2) + '%';
});

// Total traffic source views
const totalTrafficViews = computed(() => {
    if (!props.videoAnalytics?.trafficSources) return 0;
    return props.videoAnalytics.trafficSources.reduce((sum, s) => sum + s.views, 0);
});
</script>

<template>
    <Head :title="videoData?.title || 'Video Analitikasi'" />

    <BusinessLayout :title="videoData?.title || 'Video Analitikasi'">
        <div class="space-y-6">
            <!-- Back Button -->
            <Link
                :href="route('business.youtube-analytics.index')"
                class="inline-flex items-center text-gray-500 dark:text-gray-400 hover:text-red-500 transition-colors"
            >
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Barcha videolarga qaytish
            </Link>

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

            <!-- Video Header -->
            <div v-if="videoData" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="md:flex">
                    <!-- Video Thumbnail -->
                    <div class="md:w-2/5 relative">
                        <img
                            v-if="videoData.thumbnail"
                            :src="videoData.thumbnail"
                            :alt="videoData.title"
                            class="w-full h-64 md:h-full object-cover"
                        />
                        <div class="absolute bottom-4 right-4 px-2 py-1 bg-black/80 text-white text-sm rounded">
                            {{ videoData.duration_formatted }}
                        </div>
                        <a
                            :href="`https://www.youtube.com/watch?v=${videoData.id}`"
                            target="_blank"
                            class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 hover:opacity-100 transition-opacity"
                        >
                            <div class="w-16 h-16 bg-red-600 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                        </a>
                    </div>

                    <!-- Video Info -->
                    <div class="md:w-3/5 p-6">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            {{ videoData.title }}
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">
                            Joylangan: {{ formatDate(videoData.published_at) }}
                        </p>

                        <!-- Quick Stats -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(videoData.views) }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Ko'rishlar</p>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-2xl font-bold text-green-500">{{ formatNumber(videoData.likes) }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Layklar</p>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-2xl font-bold text-blue-500">{{ formatNumber(videoData.comments) }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Izohlar</p>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-2xl font-bold text-purple-500">{{ engagementRate }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Engagement</p>
                            </div>
                        </div>

                        <!-- Tags -->
                        <div v-if="videoData.tags && videoData.tags.length > 0" class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in videoData.tags.slice(0, 8)"
                                :key="tag"
                                class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm rounded-full"
                            >
                                #{{ tag }}
                            </span>
                            <span v-if="videoData.tags.length > 8" class="px-3 py-1 text-gray-500 text-sm">
                                +{{ videoData.tags.length - 8 }} ta
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 28-Day Analytics -->
            <div v-if="videoAnalytics" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                    Oxirgi 28 kun statistikasi
                    <span class="text-sm font-normal text-gray-500 ml-2">
                        ({{ formatDate(videoAnalytics.period?.start) }} - {{ formatDate(videoAnalytics.period?.end) }})
                    </span>
                </h2>

                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Ko'rishlar</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(videoAnalytics.totals?.views) }}</p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tomosha vaqti</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(Math.round(videoAnalytics.totals?.watchTime || 0)) }} min</p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha tomosha</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatDuration(videoAnalytics.totals?.avgViewDuration) }}</p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tomosha %</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatPercent(videoAnalytics.totals?.avgViewPercentage) }}</p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Yangi obunachilar</p>
                        <p class="text-2xl font-bold text-green-500">+{{ formatNumber(videoAnalytics.totals?.subscribersGained) }}</p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Ulashishlar</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(videoAnalytics.totals?.shares) }}</p>
                    </div>
                </div>
            </div>

            <!-- Traffic Sources & Countries -->
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Traffic Sources -->
                <div v-if="videoAnalytics?.trafficSources?.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Trafik manbalari</h2>
                    <div class="space-y-3">
                        <div
                            v-for="source in videoAnalytics.trafficSources"
                            :key="source.source"
                            class="flex items-center justify-between"
                        >
                            <span class="text-gray-700 dark:text-gray-300">{{ source.source }}</span>
                            <div class="flex items-center">
                                <div class="w-24 h-2 bg-gray-200 dark:bg-gray-700 rounded-full mr-3 overflow-hidden">
                                    <div
                                        class="h-full bg-red-500 rounded-full"
                                        :style="{ width: totalTrafficViews ? (source.views / totalTrafficViews * 100) + '%' : '0%' }"
                                    ></div>
                                </div>
                                <span class="text-gray-900 dark:text-white font-medium min-w-[60px] text-right">
                                    {{ formatNumber(source.views) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Countries -->
                <div v-if="videoAnalytics?.countries?.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Geografiya</h2>
                    <div class="space-y-3">
                        <div
                            v-for="country in videoAnalytics.countries"
                            :key="country.code"
                            class="flex items-center justify-between"
                        >
                            <span class="text-gray-700 dark:text-gray-300">{{ country.name }}</span>
                            <span class="text-gray-900 dark:text-white font-medium">{{ formatNumber(country.views) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Demographics -->
            <div v-if="videoAnalytics?.demographics?.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Demografiya (yosh va jins)</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <div
                        v-for="demo in videoAnalytics.demographics"
                        :key="`${demo.ageGroup}-${demo.gender}`"
                        class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl text-center"
                    >
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ demo.percentage }}%</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ demo.ageGroup }}</p>
                        <p class="text-xs text-gray-400">{{ demo.gender }}</p>
                    </div>
                </div>
            </div>

            <!-- Video Description -->
            <div v-if="videoData?.description" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Video tavsifi</h2>
                <div class="text-gray-700 dark:text-gray-300 whitespace-pre-line max-h-64 overflow-y-auto">
                    {{ videoData.description }}
                </div>
            </div>

            <!-- External Link -->
            <div class="text-center">
                <a
                    :href="`https://www.youtube.com/watch?v=${videoData?.id}`"
                    target="_blank"
                    class="inline-flex items-center px-6 py-3 bg-red-500 text-white font-semibold rounded-xl hover:bg-red-600 transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                    YouTube da ko'rish
                </a>
            </div>
        </div>
    </BusinessLayout>
</template>
