<script setup>
import { computed } from 'vue';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';

const props = defineProps({
    socialAnalytics: { type: Object, default: () => ({}) },
    period: { type: String, default: '30' },
    panelType: {
        type: String,
        required: true,
        validator: (v) => ['business', 'marketing', 'finance', 'operator', 'saleshead'].includes(v),
    },
});

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

const formatNumber = (value) => {
    if (!value) return '0';
    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
    if (value >= 1000) return (value / 1000).toFixed(1) + 'K';
    return value.toString();
};

const getPlatformColor = (platform) => {
    const colors = {
        instagram: 'from-purple-500 to-pink-500',
        facebook: 'from-blue-600 to-blue-700',
        telegram: 'from-sky-400 to-sky-500',
        youtube: 'from-red-500 to-red-600',
        twitter: 'from-gray-700 to-gray-800',
        linkedin: 'from-blue-700 to-blue-800',
    };
    return colors[platform] || 'from-gray-500 to-gray-600';
};

const getPlatformName = (key) => {
    const names = {
        instagram: 'Instagram',
        facebook: 'Facebook',
        telegram: 'Telegram',
        youtube: 'YouTube',
        twitter: 'Twitter/X',
        linkedin: 'LinkedIn',
    };
    return names[key] || key;
};

const socialPlatforms = computed(() => Object.entries(props.socialAnalytics || {}));
</script>

<template>
    <component :is="layoutComponent" title="Ijtimoiy Tarmoqlar">
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Ijtimoiy Tarmoqlar Analitikasi</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Har bir platformadagi samaradorlikni kuzating
                </p>
            </div>

            <div v-if="socialPlatforms.length === 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <div class="w-16 h-16 mx-auto bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Ma'lumotlar topilmadi</h3>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    Tanlangan davr uchun ijtimoiy tarmoq ma'lumotlari mavjud emas
                </p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="[key, data] in socialPlatforms"
                    :key="key"
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
                >
                    <div :class="`bg-gradient-to-r ${getPlatformColor(key)} p-4`">
                        <h3 class="text-lg font-semibold text-white">{{ getPlatformName(key) }}</h3>
                        <p class="text-white/80 text-sm">{{ data.posts }} post</p>
                    </div>
                    <div class="p-4 space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Reach</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ formatNumber(data.reach) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Ko'rishlar</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ formatNumber(data.views) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Likes</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ formatNumber(data.likes) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Izohlar</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ formatNumber(data.comments) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Ulashishlar</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ formatNumber(data.shares) }}</span>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400">Engagement Rate</span>
                            <span class="font-semibold text-green-600 dark:text-green-400">{{ data.engagement_rate }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
