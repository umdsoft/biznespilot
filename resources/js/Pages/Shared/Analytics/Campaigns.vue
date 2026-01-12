<script setup>
import { computed } from 'vue';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';

const props = defineProps({
    campaignAnalytics: { type: Object, default: () => ({}) },
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

const formatCurrency = (value) => {
    if (!value) return '0';
    return new Intl.NumberFormat('uz-UZ').format(value);
};

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        paused: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <component :is="layoutComponent" title="Kampaniyalar Analitikasi">
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kampaniyalar Analitikasi</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Marketing kampaniyalari samaradorligi
                </p>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ campaignAnalytics?.summary?.total_campaigns || 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Kampaniyalar</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(campaignAnalytics?.summary?.total_spent || 0) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Sarflangan</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ campaignAnalytics?.summary?.total_leads || 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Lidlar</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(campaignAnalytics?.summary?.avg_cost_per_lead || 0) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Lid narxi</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                    <p :class="['text-2xl font-bold', (campaignAnalytics?.summary?.avg_roi || 0) >= 0 ? 'text-green-600' : 'text-red-600']">
                        {{ campaignAnalytics?.summary?.avg_roi || 0 }}%
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">ROI</p>
                </div>
            </div>

            <!-- Campaigns List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Kampaniyalar ro'yxati</h3>
                </div>
                <div class="p-6">
                    <div v-if="!campaignAnalytics?.campaigns?.length" class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400">Hozircha kampaniyalar yo'q</p>
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th class="pb-3">Kampaniya</th>
                                    <th class="pb-3">Status</th>
                                    <th class="pb-3 text-right">Byudjet</th>
                                    <th class="pb-3 text-right">Sarflangan</th>
                                    <th class="pb-3 text-right">Lidlar</th>
                                    <th class="pb-3 text-right">ROI</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="campaign in campaignAnalytics.campaigns" :key="campaign.id">
                                    <td class="py-3">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ campaign.name }}</span>
                                    </td>
                                    <td class="py-3">
                                        <span :class="[getStatusColor(campaign.status), 'px-2 py-1 rounded-full text-xs font-medium']">
                                            {{ campaign.status }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-right text-gray-600 dark:text-gray-400">
                                        {{ formatCurrency(campaign.budget) }}
                                    </td>
                                    <td class="py-3 text-right text-gray-600 dark:text-gray-400">
                                        {{ formatCurrency(campaign.spent) }}
                                    </td>
                                    <td class="py-3 text-right text-gray-600 dark:text-gray-400">
                                        {{ campaign.leads }}
                                    </td>
                                    <td class="py-3 text-right">
                                        <span :class="campaign.roi >= 0 ? 'text-green-600' : 'text-red-600'">
                                            {{ campaign.roi }}%
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
