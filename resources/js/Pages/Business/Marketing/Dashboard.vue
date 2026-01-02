<script setup>
import { Head, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import {
    ChartBarIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    UserGroupIcon,
    EyeIcon,
    HandThumbUpIcon,
    PlusIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    channels: {
        type: Array,
        default: () => []
    },
    analytics: {
        type: Object,
        default: () => ({
            total_channels: 0,
            active_channels: 0,
            total_reach: 0,
            total_engagement: 0,
            average_engagement_rate: 0
        })
    },
    lazyLoad: {
        type: Boolean,
        default: false
    }
});

// Lazy loading state
const isLoading = ref(false);
const loadedData = ref({
    channels: null,
    analytics: null,
});

// Default values
const defaultAnalytics = {
    total_channels: 0,
    active_channels: 0,
    total_reach: 0,
    total_engagement: 0,
    average_engagement_rate: 0
};

// Computed properties with null handling
const channels = computed(() => loadedData.value.channels || props.channels || []);
const analytics = computed(() => loadedData.value.analytics || props.analytics || defaultAnalytics);

// Fetch data via API
const fetchData = async () => {
    if (!props.lazyLoad) return;

    isLoading.value = true;
    try {
        const response = await axios.get('/business/marketing/api/dashboard');
        if (response.data) {
            loadedData.value = {
                channels: response.data.channels || [],
                analytics: response.data.analytics || defaultAnalytics,
            };
        }
    } catch (error) {
        console.error('Marketing dashboard data loading error:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    if (props.lazyLoad) {
        fetchData();
    }
});

// Format numbers with commas
const formatNumber = (num) => {
    if (!num) return '0';
    return new Intl.NumberFormat('uz-UZ').format(num);
};

// Format percentage
const formatPercentage = (num) => {
    if (!num) return '0.00';
    return Number(num).toFixed(2);
};

// Get channel icon based on platform
const getChannelIcon = (type) => {
    const icons = {
        instagram: '📸',
        telegram: '✈️',
        facebook: '👥',
        google_ads: '🎯',
        tiktok: '🎵',
        youtube: '📺'
    };
    return icons[type] || '📊';
};

// Get channel color
const getChannelColor = (type) => {
    const colors = {
        instagram: 'from-pink-500 to-purple-600',
        telegram: 'from-blue-400 to-blue-600',
        facebook: 'from-blue-600 to-indigo-700',
        google_ads: 'from-green-500 to-teal-600',
        tiktok: 'from-gray-800 to-black',
        youtube: 'from-red-500 to-red-700'
    };
    return colors[type] || 'from-gray-500 to-gray-700';
};

// Get platform display name
const getPlatformName = (type) => {
    const names = {
        instagram: 'Instagram',
        telegram: 'Telegram',
        facebook: 'Facebook',
        google_ads: 'Google Ads',
        tiktok: 'TikTok',
        youtube: 'YouTube'
    };
    return names[type] || type;
};
</script>

<template>
    <Head title="Marketing Analytics" />

    <BusinessLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-white leading-tight">
                        Dashboard
                    </h2>
                    <p class="text-sm text-slate-400 mt-1">
                        Barcha marketing kanallaringizni bir joyda kuzating
                    </p>
                </div>
                <Link
                    :href="route('business.marketing.channels')"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-900 transition ease-in-out duration-150"
                >
                    <PlusIcon class="w-5 h-5 mr-2" />
                    Kanal Qo'shish
                </Link>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Channels -->
                    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 overflow-hidden rounded-xl">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 p-3 bg-indigo-500/10 rounded-xl">
                                    <ChartBarIcon class="h-8 w-8 text-indigo-400" />
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-slate-400 truncate">
                                        Jami Kanallar
                                    </p>
                                    <p v-if="isLoading" class="h-8 w-16 bg-slate-700 rounded animate-pulse"></p>
                                    <p v-else class="text-2xl font-bold text-white">
                                        {{ analytics?.total_channels || 0 }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1">
                                        {{ analytics?.active_channels || 0 }} faol
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Reach -->
                    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 overflow-hidden rounded-xl">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 p-3 bg-blue-500/10 rounded-xl">
                                    <EyeIcon class="h-8 w-8 text-blue-400" />
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-slate-400 truncate">
                                        Jami Reach
                                    </p>
                                    <p v-if="isLoading" class="h-8 w-20 bg-slate-700 rounded animate-pulse"></p>
                                    <p v-else class="text-2xl font-bold text-white">
                                        {{ formatNumber(analytics?.total_reach) }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1">
                                        So'nggi o'lchov
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Engagement -->
                    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 overflow-hidden rounded-xl">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 p-3 bg-green-500/10 rounded-xl">
                                    <HandThumbUpIcon class="h-8 w-8 text-green-400" />
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-slate-400 truncate">
                                        Jami Engagement
                                    </p>
                                    <p v-if="isLoading" class="h-8 w-20 bg-slate-700 rounded animate-pulse"></p>
                                    <p v-else class="text-2xl font-bold text-white">
                                        {{ formatNumber(analytics?.total_engagement) }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Likes, comments, shares
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Average Engagement Rate -->
                    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 overflow-hidden rounded-xl">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 p-3 bg-purple-500/10 rounded-xl">
                                    <UserGroupIcon class="h-8 w-8 text-purple-400" />
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-slate-400 truncate">
                                        O'rtacha Engagement
                                    </p>
                                    <p v-if="isLoading" class="h-8 w-16 bg-slate-700 rounded animate-pulse"></p>
                                    <p v-else class="text-2xl font-bold text-white">
                                        {{ formatPercentage(analytics?.average_engagement_rate) }}%
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Barcha kanallar
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Channels Grid -->
                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 overflow-hidden rounded-xl mb-8">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold text-white">
                                Sizning Kanallaringiz
                            </h3>
                            <Link
                                :href="route('business.marketing.channels')"
                                class="text-sm text-indigo-400 hover:text-indigo-300 transition-colors"
                            >
                                Barchasini ko'rish →
                            </Link>
                        </div>

                        <!-- Loading Skeleton -->
                        <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="i in 3" :key="i" class="bg-slate-700/50 border border-slate-600/50 rounded-xl overflow-hidden animate-pulse">
                                <div class="bg-slate-600 p-4 h-20"></div>
                                <div class="p-4 space-y-3">
                                    <div class="h-4 bg-slate-600 rounded w-3/4"></div>
                                    <div class="h-4 bg-slate-600 rounded w-1/2"></div>
                                    <div class="h-4 bg-slate-600 rounded w-2/3"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="!channels?.length" class="text-center py-12">
                            <ChartBarIcon class="mx-auto h-12 w-12 text-slate-500" />
                            <h3 class="mt-2 text-sm font-medium text-white">Marketing kanallar yo'q</h3>
                            <p class="mt-1 text-sm text-slate-400">
                                Birinchi marketing kanalingizni qo'shib boshlang
                            </p>
                            <div class="mt-6">
                                <Link
                                    :href="route('business.marketing.channels')"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-800 focus:ring-indigo-500 transition-colors"
                                >
                                    <PlusIcon class="-ml-1 mr-2 h-5 w-5" />
                                    Kanal Qo'shish
                                </Link>
                            </div>
                        </div>

                        <!-- Channels Grid -->
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <Link
                                v-for="channel in channels"
                                :key="channel.id"
                                :href="route('business.marketing.channels.show', channel.id)"
                                class="block group"
                            >
                                <div class="bg-slate-700/30 border border-slate-600/50 rounded-xl overflow-hidden hover:border-indigo-500/50 transition-all duration-200 hover:shadow-lg hover:shadow-indigo-500/10">
                                    <!-- Channel Header -->
                                    <div :class="`bg-gradient-to-r ${getChannelColor(channel.platform || channel.type)} p-4`">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <span class="text-3xl">{{ getChannelIcon(channel.platform || channel.type) }}</span>
                                                <div>
                                                    <h4 class="text-white font-semibold">{{ channel.name }}</h4>
                                                    <p class="text-white text-xs opacity-90">
                                                        {{ getPlatformName(channel.platform || channel.type) }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div>
                                                <span
                                                    v-if="channel.is_active"
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-400 border border-green-500/30"
                                                >
                                                    Faol
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-500/20 text-slate-400 border border-slate-500/30"
                                                >
                                                    Nofaol
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Channel Stats -->
                                    <div class="p-4">
                                        <div class="space-y-3">
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-slate-400">Oxirgi sync:</span>
                                                <span class="font-medium text-slate-200">
                                                    {{ channel.last_synced_at ? new Date(channel.last_synced_at).toLocaleDateString('uz-UZ') : 'Hech qachon' }}
                                                </span>
                                            </div>

                                            <div v-if="channel.description" class="text-sm text-slate-400">
                                                {{ channel.description }}
                                            </div>

                                            <div class="pt-3 border-t border-slate-600/50">
                                                <button class="text-sm text-indigo-400 hover:text-indigo-300 font-medium group-hover:underline transition-colors">
                                                    Batafsil ko'rish →
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 overflow-hidden rounded-xl">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">
                            Tez Amallar
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <Link
                                :href="route('business.marketing.channels')"
                                class="flex items-center p-4 border-2 border-dashed border-slate-600/50 rounded-xl hover:border-indigo-500/50 hover:bg-indigo-500/10 transition-all duration-200 group"
                            >
                                <div class="p-2 bg-slate-700/50 rounded-lg mr-3 group-hover:bg-indigo-500/20 transition-colors">
                                    <PlusIcon class="h-6 w-6 text-slate-400 group-hover:text-indigo-400 transition-colors" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-white">Yangi Kanal</p>
                                    <p class="text-xs text-slate-400">Instagram, Telegram, Facebook</p>
                                </div>
                            </Link>

                            <a
                                href="#"
                                class="flex items-center p-4 border-2 border-dashed border-slate-600/50 rounded-xl hover:border-indigo-500/50 hover:bg-indigo-500/10 transition-all duration-200 group"
                            >
                                <div class="p-2 bg-slate-700/50 rounded-lg mr-3 group-hover:bg-indigo-500/20 transition-colors">
                                    <ChartBarIcon class="h-6 w-6 text-slate-400 group-hover:text-indigo-400 transition-colors" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-white">Hisobot Ko'rish</p>
                                    <p class="text-xs text-slate-400">Batafsil analytics</p>
                                </div>
                            </a>

                            <a
                                href="#"
                                class="flex items-center p-4 border-2 border-dashed border-slate-600/50 rounded-xl hover:border-indigo-500/50 hover:bg-indigo-500/10 transition-all duration-200 group"
                            >
                                <div class="p-2 bg-slate-700/50 rounded-lg mr-3 group-hover:bg-indigo-500/20 transition-colors">
                                    <ArrowTrendingUpIcon class="h-6 w-6 text-slate-400 group-hover:text-indigo-400 transition-colors" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-white">Xarajatlar</p>
                                    <p class="text-xs text-slate-400">Marketing spend tracking</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
        </div>
    </BusinessLayout>
</template>
