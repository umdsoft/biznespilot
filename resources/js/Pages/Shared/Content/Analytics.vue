<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';

const { t } = useI18n();

const props = defineProps({
    analytics: { type: Object, default: () => ({}) },
    start_date: { type: String, default: '' },
    end_date: { type: String, default: '' },
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

const formatNumber = (num) => {
    if (!num) return '0';
    return num.toLocaleString('uz-UZ');
};
</script>

<template>
    <component :is="layoutComponent" :title="t('content.analytics')">
        <Head :title="t('content.analytics')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">{{ t('content.analytics') }}</h1>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">
                        {{ start_date }} - {{ end_date }}
                    </span>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500">{{ t('content.total_posts') }}</div>
                    <div class="text-3xl font-bold text-gray-900">{{ formatNumber(analytics.total_posts) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500">{{ t('content.published') }}</div>
                    <div class="text-3xl font-bold text-green-600">{{ formatNumber(analytics.published_posts) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500">{{ t('content.scheduled') }}</div>
                    <div class="text-3xl font-bold text-blue-600">{{ formatNumber(analytics.scheduled_posts) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500">{{ t('content.draft') }}</div>
                    <div class="text-3xl font-bold text-gray-600">{{ formatNumber(analytics.draft_posts) }}</div>
                </div>
            </div>

            <!-- Engagement Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500">{{ t('content.views') }}</div>
                    <div class="text-2xl font-bold text-gray-900">{{ formatNumber(analytics.total_views) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500">{{ t('content.likes') }}</div>
                    <div class="text-2xl font-bold text-red-500">{{ formatNumber(analytics.total_likes) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500">{{ t('content.comments') }}</div>
                    <div class="text-2xl font-bold text-blue-500">{{ formatNumber(analytics.total_comments) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500">{{ t('content.shares') }}</div>
                    <div class="text-2xl font-bold text-green-500">{{ formatNumber(analytics.total_shares) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500">{{ t('content.avg_engagement') }}</div>
                    <div class="text-2xl font-bold text-purple-600">{{ analytics.avg_engagement }}</div>
                </div>
            </div>

            <!-- Top Performing Posts -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">{{ t('content.top_performing') }}</h2>
                </div>
                <div class="divide-y">
                    <div
                        v-for="post in analytics.top_performing"
                        :key="post.id"
                        class="p-4 flex items-center justify-between hover:bg-gray-50"
                    >
                        <div>
                            <div class="font-medium text-gray-900">{{ post.title }}</div>
                            <div class="text-sm text-gray-500">{{ post.platform }}</div>
                        </div>
                        <div class="flex items-center gap-4 text-sm">
                            <span class="text-gray-500">{{ formatNumber(post.views) }} {{ t('content.view') }}</span>
                            <span class="text-red-500">{{ formatNumber(post.likes) }} {{ t('content.like') }}</span>
                            <span class="text-blue-500">{{ formatNumber(post.comments) }} {{ t('content.comment') }}</span>
                        </div>
                    </div>
                    <div v-if="!analytics.top_performing?.length" class="p-8 text-center text-gray-500">
                        {{ t('content.no_content') }}
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
