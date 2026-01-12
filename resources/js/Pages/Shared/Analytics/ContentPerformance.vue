<script setup>
import { computed } from 'vue';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';

const props = defineProps({
    data: { type: Object, default: () => ({}) },
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

const getContentTypeName = (type) => {
    const names = {
        educational: "Ta'limiy",
        entertaining: "Ko'ngil ochuvchi",
        inspirational: 'Ilhomlantiruvchi',
        promotional: 'Reklama',
        behind_scenes: 'Sahna ortidan',
        ugc: 'UGC',
    };
    return names[type] || type;
};
</script>

<template>
    <component :is="layoutComponent" title="Kontent Samaradorligi">
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kontent Samaradorligi</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Kontent turlari va vaqtlari bo'yicha tahlil
                </p>
            </div>

            <!-- By Type -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Kontent turlari bo'yicha</h3>
                </div>
                <div class="p-6">
                    <div v-if="!data?.by_type?.length" class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400">Ma'lumotlar mavjud emas</p>
                    </div>
                    <div v-else class="space-y-4">
                        <div
                            v-for="item in data.by_type"
                            :key="item.content_type"
                            class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                        >
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ getContentTypeName(item.content_type) }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ item.posts }} post</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ formatNumber(item.reach) }} reach</p>
                                <p class="text-sm text-green-600 dark:text-green-400">{{ item.engagement_rate?.toFixed(2) || 0 }}% engagement</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Best Times -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Eng yaxshi joylash vaqtlari</h3>
                </div>
                <div class="p-6">
                    <div v-if="!data?.best_times?.length" class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400">Ma'lumotlar mavjud emas</p>
                    </div>
                    <div v-else class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div
                            v-for="(time, index) in data.best_times"
                            :key="time.hour"
                            class="text-center p-4 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-lg"
                        >
                            <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ time.hour }}:00</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ time.posts }} post</p>
                            <p class="text-xs text-green-600 dark:text-green-400">{{ time.engagement_rate?.toFixed(2) || 0 }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hashtag Performance -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Top Hashtaglar</h3>
                </div>
                <div class="p-6">
                    <div v-if="!data?.hashtag_performance || Object.keys(data.hashtag_performance).length === 0" class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400">Hashtag ma'lumotlari mavjud emas</p>
                    </div>
                    <div v-else class="flex flex-wrap gap-2">
                        <span
                            v-for="(count, tag) in data.hashtag_performance"
                            :key="tag"
                            class="inline-flex items-center px-3 py-1.5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full text-sm font-medium"
                        >
                            {{ tag.startsWith('#') ? tag : '#' + tag }}
                            <span class="ml-1.5 px-1.5 py-0.5 bg-indigo-200 dark:bg-indigo-800 rounded-full text-xs">{{ count }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
