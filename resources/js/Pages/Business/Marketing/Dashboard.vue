<script setup>
import { Head, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import { computed } from 'vue';
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
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Marketing Analytics
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Barcha marketing kanallaringizni bir joyda kuzating
                    </p>
                </div>
                <Link
                    :href="route('business.marketing.channels')"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <PlusIcon class="w-5 h-5 mr-2" />
                    Kanal Qo'shish
                </Link>
            </div>
        </template>

        <div class="py-12 px-4 sm:px-6 lg:px-8">
            <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Channels -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <ChartBarIcon class="h-12 w-12 text-indigo-600" />
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-500 truncate">
                                        Jami Kanallar
                                    </p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ analytics.total_channels }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ analytics.active_channels }} faol
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Reach -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <EyeIcon class="h-12 w-12 text-blue-600" />
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-500 truncate">
                                        Jami Reach
                                    </p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ formatNumber(analytics.total_reach) }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        So'nggi o'lchov
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Engagement -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <HandThumbUpIcon class="h-12 w-12 text-green-600" />
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-500 truncate">
                                        Jami Engagement
                                    </p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ formatNumber(analytics.total_engagement) }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Likes, comments, shares
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Average Engagement Rate -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <UserGroupIcon class="h-12 w-12 text-purple-600" />
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-500 truncate">
                                        O'rtacha Engagement
                                    </p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ formatPercentage(analytics.average_engagement_rate) }}%
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Barcha kanallar
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Channels Grid -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Sizning Kanallaringiz
                            </h3>
                            <Link
                                :href="route('business.marketing.channels')"
                                class="text-sm text-indigo-600 hover:text-indigo-900"
                            >
                                Barchasini ko'rish →
                            </Link>
                        </div>

                        <div v-if="channels.length === 0" class="text-center py-12">
                            <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Marketing kanallar yo'q</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Birinchi marketing kanalingizni qo'shib boshlang
                            </p>
                            <div class="mt-6">
                                <Link
                                    :href="route('business.marketing.channels')"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    <PlusIcon class="-ml-1 mr-2 h-5 w-5" />
                                    Kanal Qo'shish
                                </Link>
                            </div>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <Link
                                v-for="channel in channels"
                                :key="channel.id"
                                :href="route('business.marketing.channels.show', channel.id)"
                                class="block group"
                            >
                                <div class="bg-white border-2 border-gray-200 rounded-lg overflow-hidden hover:border-indigo-500 transition-all duration-200 hover:shadow-lg">
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
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                                >
                                                    Faol
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
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
                                                <span class="text-gray-500">Oxirgi sync:</span>
                                                <span class="font-medium text-gray-900">
                                                    {{ channel.last_synced_at ? new Date(channel.last_synced_at).toLocaleDateString('uz-UZ') : 'Hech qachon' }}
                                                </span>
                                            </div>

                                            <div v-if="channel.description" class="text-sm text-gray-600">
                                                {{ channel.description }}
                                            </div>

                                            <div class="pt-3 border-t border-gray-200">
                                                <button class="text-sm text-indigo-600 hover:text-indigo-900 font-medium group-hover:underline">
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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Tez Amallar
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <Link
                                :href="route('business.marketing.channels')"
                                class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition-all duration-200"
                            >
                                <PlusIcon class="h-8 w-8 text-gray-400 mr-3" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Yangi Kanal</p>
                                    <p class="text-xs text-gray-500">Instagram, Telegram, Facebook</p>
                                </div>
                            </Link>

                            <a
                                href="#"
                                class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition-all duration-200"
                            >
                                <ChartBarIcon class="h-8 w-8 text-gray-400 mr-3" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Hisobot Ko'rish</p>
                                    <p class="text-xs text-gray-500">Batafsil analytics</p>
                                </div>
                            </a>

                            <a
                                href="#"
                                class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition-all duration-200"
                            >
                                <ArrowTrendingUpIcon class="h-8 w-8 text-gray-400 mr-3" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Xarajatlar</p>
                                    <p class="text-xs text-gray-500">Marketing spend tracking</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
        </div>
    </BusinessLayout>
</template>
