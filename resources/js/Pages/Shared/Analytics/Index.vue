<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import {
    ChartBarIcon,
    EyeIcon,
    HeartIcon,
    ChatBubbleLeftIcon,
    ShareIcon,
    UserGroupIcon,
    DocumentTextIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    FunnelIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    overview: Object,
    channels: Array,
    topContent: Array,
    campaignPerformance: Object,
    trends: Object,
    leadSources: Array,
    period: String,
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

const getRoutePrefix = () => props.panelType;

const selectedPeriod = ref(props.period || '30');
const isLoading = ref(false);

const periods = [
    { value: '7', label: '7 kun' },
    { value: '30', label: '30 kun' },
    { value: '90', label: '90 kun' },
    { value: '365', label: '1 yil' },
];

const changePeriod = (period) => {
    selectedPeriod.value = period;
    isLoading.value = true;
    const prefix = getRoutePrefix();
    router.get(route(`${prefix}.analytics.index`), { period }, {
        preserveState: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

const formatNumber = (value) => {
    if (!value) return '0';
    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
    if (value >= 1000) return (value / 1000).toFixed(1) + 'K';
    return value.toString();
};

const formatCurrency = (value) => {
    if (!value) return '0';
    return new Intl.NumberFormat('uz-UZ').format(value);
};

const getPlatformIcon = (platform) => {
    const icons = {
        instagram: 'IG',
        facebook: 'FB',
        telegram: 'TG',
        youtube: 'YT',
        twitter: 'X',
        linkedin: 'IN',
        email: '@',
        blog: 'B',
    };
    return icons[platform] || platform?.charAt(0).toUpperCase();
};

const getPlatformColor = (platform) => {
    const colors = {
        instagram: 'bg-gradient-to-br from-purple-500 to-pink-500',
        facebook: 'bg-blue-600',
        telegram: 'bg-sky-500',
        youtube: 'bg-red-600',
        twitter: 'bg-gray-900 dark:bg-gray-700',
        linkedin: 'bg-blue-700',
        email: 'bg-gray-600',
        blog: 'bg-green-600',
    };
    return colors[platform] || 'bg-gray-500';
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

const overviewStats = computed(() => [
    {
        label: 'Umumiy Reach',
        value: formatNumber(props.overview?.total_reach),
        icon: EyeIcon,
        color: 'bg-blue-500',
        growth: props.overview?.reach_growth,
    },
    {
        label: 'Ko\'rishlar',
        value: formatNumber(props.overview?.total_views),
        icon: EyeIcon,
        color: 'bg-purple-500',
    },
    {
        label: 'Engagement',
        value: formatNumber(props.overview?.total_engagement),
        icon: HeartIcon,
        color: 'bg-pink-500',
    },
    {
        label: 'Engagement Rate',
        value: (props.overview?.engagement_rate || 0) + '%',
        icon: ChartBarIcon,
        color: 'bg-green-500',
    },
    {
        label: 'Nashr qilingan',
        value: props.overview?.content_published || 0,
        icon: DocumentTextIcon,
        color: 'bg-indigo-500',
    },
    {
        label: 'Yangi Lidlar',
        value: props.overview?.leads_generated || 0,
        icon: UserGroupIcon,
        color: 'bg-orange-500',
        growth: props.overview?.leads_growth,
    },
]);

const hasData = computed(() => {
    return props.overview?.total_reach > 0 ||
           props.overview?.content_published > 0 ||
           props.channels?.length > 0;
});
</script>

<template>
    <component :is="layoutComponent" title="Analitika">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Marketing Analitikasi</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Kontent va kampaniyalar samaradorligini kuzating
                    </p>
                </div>

                <!-- Period Filter -->
                <div class="flex items-center gap-2">
                    <FunnelIcon class="w-5 h-5 text-gray-400" />
                    <div class="flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                        <button
                            v-for="period in periods"
                            :key="period.value"
                            @click="changePeriod(period.value)"
                            :class="[
                                'px-3 py-1.5 text-sm font-medium rounded-md transition-colors',
                                selectedPeriod === period.value
                                    ? 'bg-white dark:bg-gray-700 text-purple-600 dark:text-purple-400 shadow-sm'
                                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                            ]"
                        >
                            {{ period.label }}
                        </button>
                    </div>
                    <button
                        @click="changePeriod(selectedPeriod)"
                        :disabled="isLoading"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800"
                    >
                        <ArrowPathIcon :class="['w-5 h-5', isLoading && 'animate-spin']" />
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
            </div>

            <!-- Content -->
            <template v-else>
                <!-- Empty State -->
                <div v-if="!hasData" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <div class="w-16 h-16 mx-auto bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mb-4">
                        <ChartBarIcon class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Ma'lumotlar topilmadi</h3>
                    <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6">
                        Tanlangan davr uchun analitik ma'lumotlar mavjud emas. Kontent yarating va nashr qiling.
                    </p>
                </div>

                <template v-else>
                    <!-- Overview Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div
                            v-for="stat in overviewStats"
                            :key="stat.label"
                            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div :class="[stat.color, 'w-10 h-10 rounded-lg flex items-center justify-center']">
                                    <component :is="stat.icon" class="w-5 h-5 text-white" />
                                </div>
                                <div v-if="stat.growth !== undefined" class="flex items-center text-xs">
                                    <component
                                        :is="stat.growth >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon"
                                        :class="[
                                            'w-4 h-4 mr-1',
                                            stat.growth >= 0 ? 'text-green-500' : 'text-red-500'
                                        ]"
                                    />
                                    <span :class="stat.growth >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ Math.abs(stat.growth) }}%
                                    </span>
                                </div>
                            </div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ stat.label }}</p>
                        </div>
                    </div>

                    <!-- Engagement Breakdown -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                                    <HeartIcon class="w-6 h-6 text-red-500" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(overview?.likes) }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Likes</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                    <ChatBubbleLeftIcon class="w-6 h-6 text-blue-500" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(overview?.comments) }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Izohlar</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                    <ShareIcon class="w-6 h-6 text-green-500" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(overview?.shares) }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Ulashishlar</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Channel Performance & Top Content -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Channel Performance -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Kanal samaradorligi</h3>
                            </div>
                            <div class="p-6">
                                <div v-if="channels?.length === 0" class="text-center py-8">
                                    <p class="text-gray-500 dark:text-gray-400">Ma'lumotlar mavjud emas</p>
                                </div>
                                <div v-else class="space-y-4">
                                    <div
                                        v-for="channel in channels"
                                        :key="channel.key"
                                        class="flex items-center gap-4"
                                    >
                                        <div :class="[getPlatformColor(channel.key), 'w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-sm']">
                                            {{ getPlatformIcon(channel.key) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between mb-1">
                                                <p class="font-medium text-gray-900 dark:text-white">{{ channel.name }}</p>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ channel.posts }} post</span>
                                            </div>
                                            <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                                <span>Reach: {{ formatNumber(channel.reach) }}</span>
                                                <span>Engagement: {{ channel.engagement_rate }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Content -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Eng yaxshi kontentlar</h3>
                            </div>
                            <div class="p-6">
                                <div v-if="topContent?.length === 0" class="text-center py-8">
                                    <p class="text-gray-500 dark:text-gray-400">Nashr qilingan kontent yo'q</p>
                                </div>
                                <div v-else class="space-y-4">
                                    <div
                                        v-for="(content, index) in topContent"
                                        :key="content.id"
                                        class="flex items-start gap-3"
                                    >
                                        <span class="w-6 h-6 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center text-xs font-medium text-gray-600 dark:text-gray-400">
                                            {{ index + 1 }}
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-gray-900 dark:text-white truncate">{{ content.title }}</p>
                                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                <span :class="[getPlatformColor(content.platform), 'px-1.5 py-0.5 rounded text-white text-[10px]']">
                                                    {{ getPlatformIcon(content.platform) }}
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <HeartIcon class="w-3 h-3" />
                                                    {{ formatNumber(content.likes) }}
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <ChatBubbleLeftIcon class="w-3 h-3" />
                                                    {{ content.comments }}
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <EyeIcon class="w-3 h-3" />
                                                    {{ formatNumber(content.reach) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Campaign Performance -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Kampaniya samaradorligi</h3>
                        </div>

                        <!-- Summary Stats -->
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 p-6 border-b border-gray-200 dark:border-gray-700">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ campaignPerformance?.summary?.total_campaigns || 0 }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Kampaniyalar</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(campaignPerformance?.summary?.total_spent || 0) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Sarflangan (so'm)</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ campaignPerformance?.summary?.total_leads || 0 }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Lidlar</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(campaignPerformance?.summary?.avg_cost_per_lead || 0) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Har bir lid (so'm)</p>
                            </div>
                            <div class="text-center">
                                <p :class="[
                                    'text-2xl font-bold',
                                    (campaignPerformance?.summary?.avg_roi || 0) >= 0 ? 'text-green-600' : 'text-red-600'
                                ]">
                                    {{ campaignPerformance?.summary?.avg_roi || 0 }}%
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">O'rtacha ROI</p>
                            </div>
                        </div>

                        <!-- Campaign List -->
                        <div class="p-6">
                            <div v-if="!campaignPerformance?.campaigns?.length" class="text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400">Hozircha kampaniyalar yo'q</p>
                            </div>
                            <div v-else class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            <th class="pb-3">Kampaniya</th>
                                            <th class="pb-3">Status</th>
                                            <th class="pb-3 text-right">Sarflangan</th>
                                            <th class="pb-3 text-right">Lidlar</th>
                                            <th class="pb-3 text-right">ROI</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="campaign in campaignPerformance.campaigns" :key="campaign.id">
                                            <td class="py-3">
                                                <div class="flex items-center gap-3">
                                                    <div :class="[getPlatformColor(campaign.channel), 'w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold']">
                                                        {{ getPlatformIcon(campaign.channel) }}
                                                    </div>
                                                    <span class="font-medium text-gray-900 dark:text-white">{{ campaign.name }}</span>
                                                </div>
                                            </td>
                                            <td class="py-3">
                                                <span :class="[getStatusColor(campaign.status), 'px-2 py-1 rounded-full text-xs font-medium']">
                                                    {{ campaign.status }}
                                                </span>
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

                    <!-- Lead Sources -->
                    <div v-if="leadSources?.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Lid manbalari</h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div
                                    v-for="source in leadSources"
                                    :key="source.key"
                                    class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 text-center"
                                >
                                    <div :class="[getPlatformColor(source.key), 'w-10 h-10 mx-auto rounded-lg flex items-center justify-center text-white font-bold text-sm mb-2']">
                                        {{ getPlatformIcon(source.key) }}
                                    </div>
                                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ source.leads }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ source.name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </template>
        </div>
    </component>
</template>
