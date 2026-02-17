<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import axios from 'axios';
import {
    PencilSquareIcon,
    GlobeAltIcon,
    EyeIcon,
    HeartIcon,
    UserPlusIcon,
    CheckCircleIcon,
    CurrencyDollarIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    LightBulbIcon,
    ExclamationTriangleIcon,
    ChartBarIcon,
    ArrowLeftIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    filters: Object,
    lazyLoad: { type: Boolean, default: false },
});

const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const isLoading = ref(false);
const data = ref(null);

const formatPrice = (price) => {
    if (!price) return '0';
    return new Intl.NumberFormat('uz-UZ').format(Math.round(price));
};

const formatNumber = (value) => {
    if (!value) return '0';
    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
    if (value >= 1000) return (value / 1000).toFixed(1) + 'K';
    return value.toString();
};

const funnelData = computed(() => data.value?.funnel?.funnel || []);
const dropoffs = computed(() => data.value?.funnel?.dropoffs || []);
const bottleneck = computed(() => data.value?.funnel?.bottleneck || null);
const summary = computed(() => data.value?.funnel?.summary || {});
const ranking = computed(() => data.value?.ranking || []);
const byType = computed(() => data.value?.by_type || []);
const trend = computed(() => data.value?.trend || { labels: [], datasets: [] });
const insights = computed(() => data.value?.insights || []);

// Funnel vizual kengligi (foiz)
const getFunnelWidth = (index) => {
    if (!funnelData.value.length) return 100;
    const first = funnelData.value[0]?.count || 1;
    const current = funnelData.value[index]?.count || 0;

    // Reach va engagement content count dan katta bo'lishi mumkin
    if (index <= 1) {
        return Math.max(100 - (index * 8), 60);
    }
    // Reach/Engagement → Lead/Sale orasida katta tushish
    if (index >= 4) {
        return Math.max(20 - ((index - 4) * 5), 5);
    }
    return Math.max(60 - ((index - 2) * 15), 15);
};

const stageColors = {
    content_created: { bg: 'bg-blue-500', text: 'text-blue-600', light: 'bg-blue-50 dark:bg-blue-900/30' },
    published: { bg: 'bg-indigo-500', text: 'text-indigo-600', light: 'bg-indigo-50 dark:bg-indigo-900/30' },
    reached: { bg: 'bg-purple-500', text: 'text-purple-600', light: 'bg-purple-50 dark:bg-purple-900/30' },
    engaged: { bg: 'bg-pink-500', text: 'text-pink-600', light: 'bg-pink-50 dark:bg-pink-900/30' },
    leads: { bg: 'bg-orange-500', text: 'text-orange-600', light: 'bg-orange-50 dark:bg-orange-900/30' },
    qualified: { bg: 'bg-yellow-500', text: 'text-yellow-600', light: 'bg-yellow-50 dark:bg-yellow-900/30' },
    won: { bg: 'bg-green-500', text: 'text-green-600', light: 'bg-green-50 dark:bg-green-900/30' },
};

const stageIcons = {
    content_created: PencilSquareIcon,
    published: GlobeAltIcon,
    reached: EyeIcon,
    engaged: HeartIcon,
    leads: UserPlusIcon,
    qualified: CheckCircleIcon,
    won: CurrencyDollarIcon,
};

const insightIcons = {
    warning: ExclamationTriangleIcon,
    danger: ExclamationTriangleIcon,
    success: ArrowTrendingUpIcon,
    info: LightBulbIcon,
};

const insightColors = {
    warning: 'border-yellow-300 bg-yellow-50 dark:border-yellow-700 dark:bg-yellow-900/20',
    danger: 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/20',
    success: 'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-900/20',
    info: 'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20',
};

const insightIconColors = {
    warning: 'text-yellow-600 dark:text-yellow-400',
    danger: 'text-red-600 dark:text-red-400',
    success: 'text-green-600 dark:text-green-400',
    info: 'text-blue-600 dark:text-blue-400',
};

const getContentTypeName = (type) => {
    const names = {
        educational: "Ta'limiy",
        promotional: 'Reklama',
        inspirational: 'Ilhomlantiruvchi',
        entertaining: "Ko'ngil ochar",
        behind_scenes: 'Sahna ortidan',
        ugc: 'UGC',
    };
    return names[type] || type;
};

// Ma'lumotlarni yuklash
const fetchData = async () => {
    isLoading.value = true;
    try {
        const params = {};
        if (dateFrom.value) params.date_from = dateFrom.value;
        if (dateTo.value) params.date_to = dateTo.value;
        const response = await axios.get(route('business.analytics.api.content-funnel'), { params });
        data.value = response.data;
    } catch (error) {
        console.error('Content funnel data loading error:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => fetchData());

const applyFilters = () => fetchData();
const clearFilters = () => {
    dateFrom.value = '';
    dateTo.value = '';
    fetchData();
};

// Max value for type comparison bars
const maxLeadConversion = computed(() => {
    if (!byType.value.length) return 1;
    return Math.max(...byType.value.map(t => t.lead_conversion), 1);
});
</script>

<template>
    <BusinessLayout title="Kontent Funnel">
        <Head title="Kontent Funnel" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('business.analytics.index')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <ArrowLeftIcon class="w-5 h-5" />
                        </Link>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kontent Funnel</h1>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Kontent yaratishdan daromadgacha bo'lgan to'liq zanjir tahlili
                    </p>
                </div>

                <!-- Filters -->
                <div class="flex items-center gap-2">
                    <input
                        v-model="dateFrom"
                        type="date"
                        class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                    />
                    <span class="text-gray-400">—</span>
                    <input
                        v-model="dateTo"
                        type="date"
                        class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                    />
                    <button
                        @click="applyFilters"
                        class="px-3 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700"
                    >
                        Qo'llash
                    </button>
                    <button
                        v-if="dateFrom || dateTo"
                        @click="clearFilters"
                        class="px-3 py-2 text-gray-600 dark:text-gray-400 text-sm hover:text-gray-900"
                    >
                        Tozalash
                    </button>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="isLoading" class="flex items-center justify-center py-20">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            </div>

            <template v-else-if="data">
                <!-- KPI kartochkalar -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Kontent → Lid</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ summary.content_to_lead_rate || 0 }}%
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Lid → Sotuv</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ summary.lead_to_sale_rate || 0 }}%
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Daromad / Kontent</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ formatPrice(summary.revenue_per_content) }}
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Jami daromad</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                            {{ formatPrice(summary.total_revenue) }} so'm
                        </p>
                    </div>
                </div>

                <!-- Funnel vizualizatsiya -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Kontent → Daromad Funnel</h3>
                    </div>
                    <div class="p-6">
                        <div v-if="!funnelData.length" class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">Ma'lumotlar mavjud emas. Avval kontent yarating.</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="(stage, index) in funnelData"
                                :key="stage.stage"
                                class="relative"
                            >
                                <!-- Stage bar -->
                                <div class="flex items-center gap-4">
                                    <div class="w-32 flex-shrink-0 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <component
                                                :is="stageIcons[stage.stage]"
                                                :class="['w-4 h-4', stageColors[stage.stage]?.text || 'text-gray-500']"
                                            />
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ stage.label }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div
                                            :class="[
                                                'h-10 rounded-lg flex items-center px-3 transition-all duration-500',
                                                stageColors[stage.stage]?.bg || 'bg-gray-500',
                                                bottleneck && bottleneck.from === stage.stage ? 'ring-2 ring-red-500 ring-offset-2 dark:ring-offset-gray-800' : '',
                                            ]"
                                            :style="{ width: getFunnelWidth(index) + '%' }"
                                        >
                                            <span class="text-white text-sm font-bold whitespace-nowrap">
                                                {{ stage.stage === 'won' && stage.revenue
                                                    ? formatNumber(stage.count) + ' (' + formatPrice(stage.revenue) + ' so\'m)'
                                                    : formatNumber(stage.count)
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dropoff indicator -->
                                <div
                                    v-if="index < funnelData.length - 1 && dropoffs[index]"
                                    class="ml-36 pl-4 py-1"
                                >
                                    <span
                                        v-if="dropoffs[index].type === 'dropoff'"
                                        class="text-xs font-medium"
                                        :class="dropoffs[index].rate > 50 ? 'text-red-500' : dropoffs[index].rate > 30 ? 'text-yellow-500' : 'text-gray-400'"
                                    >
                                        ↓ {{ dropoffs[index].rate }}% tushkunlik
                                    </span>
                                    <span
                                        v-else-if="dropoffs[index].type === 'conversion'"
                                        class="text-xs font-medium text-blue-500"
                                    >
                                        → {{ dropoffs[index].rate }}% konversiya
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Bottleneck alert -->
                        <div
                            v-if="bottleneck"
                            class="mt-6 p-4 border border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/20 rounded-lg"
                        >
                            <div class="flex items-start gap-3">
                                <ExclamationTriangleIcon class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
                                <div>
                                    <p class="font-medium text-red-800 dark:text-red-300">
                                        Bottleneck: {{ bottleneck.from }} → {{ bottleneck.to }} ({{ bottleneck.rate }}% yo'qotish)
                                    </p>
                                    <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ bottleneck.message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- AI Tavsiyalar -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <LightBulbIcon class="w-5 h-5 text-yellow-500" />
                                Tavsiyalar
                            </h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <div v-if="!insights.length" class="text-center py-4">
                                <p class="text-gray-500 dark:text-gray-400">Yetarli ma'lumot yig'ilgach tavsiyalar paydo bo'ladi</p>
                            </div>
                            <div
                                v-for="(insight, i) in insights"
                                :key="i"
                                :class="['p-4 rounded-lg border', insightColors[insight.type] || insightColors.info]"
                            >
                                <div class="flex items-start gap-3">
                                    <component
                                        :is="insightIcons[insight.type] || LightBulbIcon"
                                        :class="['w-5 h-5 flex-shrink-0 mt-0.5', insightIconColors[insight.type] || 'text-gray-500']"
                                    />
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white text-sm">{{ insight.title }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ insight.description }}</p>
                                        <p class="text-xs font-medium text-indigo-600 dark:text-indigo-400 mt-2">{{ insight.action }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kontent turi bo'yicha -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Kontent turi bo'yicha</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div v-if="!byType.length" class="text-center py-4">
                                <p class="text-gray-500 dark:text-gray-400">Ma'lumotlar mavjud emas</p>
                            </div>
                            <div
                                v-for="item in byType"
                                :key="item.content_type"
                                class="space-y-2"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ item.label }}
                                    </span>
                                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                        <span>{{ item.posts_count }} post</span>
                                        <span>{{ item.leads_count }} lid</span>
                                        <span class="font-medium text-indigo-600 dark:text-indigo-400">
                                            {{ item.lead_conversion }}%
                                        </span>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div
                                        class="bg-indigo-500 h-2 rounded-full transition-all duration-500"
                                        :style="{ width: (item.lead_conversion / maxLeadConversion * 100) + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Performers -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Top Kontentlar</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <div v-if="!ranking.length" class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">Ma'lumotlar mavjud emas</p>
                        </div>
                        <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kontent</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reach</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Eng.</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Lidlar</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sotuvlar</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Daromad</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr
                                    v-for="(item, i) in ranking.slice(0, 10)"
                                    :key="item.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/30"
                                >
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ i + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="max-w-xs">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ item.title }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ getContentTypeName(item.content_type) }}
                                                <span v-if="item.published_at"> · {{ item.published_at }}</span>
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ formatNumber(item.reach) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ item.engagement_rate?.toFixed(1) || 0 }}%</td>
                                    <td class="px-4 py-3 text-sm text-right">
                                        <span :class="item.leads_count > 0 ? 'text-green-600 dark:text-green-400 font-medium' : 'text-gray-500'">
                                            {{ item.leads_count }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right">
                                        <span :class="item.sales_count > 0 ? 'text-green-600 dark:text-green-400 font-medium' : 'text-gray-500'">
                                            {{ item.sales_count }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">
                                        {{ item.revenue > 0 ? formatPrice(item.revenue) : '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Haftalik trend -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Haftalik Trend (8 hafta)</h3>
                    </div>
                    <div class="p-6">
                        <div v-if="!trend.labels?.length" class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">Ma'lumotlar mavjud emas</p>
                        </div>
                        <div v-else class="space-y-4">
                            <!-- Mini trend jadval -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:border-gray-700">
                                            <th class="px-3 py-2 text-left text-gray-500 dark:text-gray-400 font-medium">Hafta</th>
                                            <th
                                                v-for="label in trend.labels"
                                                :key="label"
                                                class="px-3 py-2 text-center text-gray-500 dark:text-gray-400 font-medium"
                                            >
                                                {{ label }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="dataset in trend.datasets"
                                            :key="dataset.label"
                                            class="border-b border-gray-100 dark:border-gray-700/50"
                                        >
                                            <td class="px-3 py-2 font-medium text-gray-700 dark:text-gray-300">
                                                <span
                                                    class="inline-block w-2 h-2 rounded-full mr-2"
                                                    :style="{ backgroundColor: dataset.borderColor }"
                                                ></span>
                                                {{ dataset.label }}
                                            </td>
                                            <td
                                                v-for="(val, j) in dataset.data"
                                                :key="j"
                                                class="px-3 py-2 text-center text-gray-900 dark:text-white"
                                            >
                                                {{ val }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Bo'sh holat -->
            <div v-else-if="!isLoading" class="text-center py-20">
                <ChartBarIcon class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto" />
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Ma'lumotlar yuklanmadi</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Sahifani qayta yuklang yoki keyinroq urinib ko'ring.</p>
            </div>
        </div>
    </BusinessLayout>
</template>
