<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    ChartBarIcon,
    CurrencyDollarIcon,
    MegaphoneIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    UserGroupIcon,
    BanknotesIcon,
    SparklesIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ChartPieIcon,
    RocketLaunchIcon,
} from '@heroicons/vue/24/outline';
import { useI18n } from '@/i18n';
import axios from 'axios';

const { t } = useI18n();
const page = usePage();

const props = defineProps({
    currentBusiness: { type: Object, default: null },
});

// Data
const loading = ref(false);
const selectedDate = ref(new Date().toISOString().slice(0, 7));
const dashboardData = ref(null);
const bonusCalculation = ref(null);
const showBonusModal = ref(false);
const bonusFund = ref(10000000); // Default 10M UZS

// API Fetch
const fetchDashboard = async () => {
    if (!props.currentBusiness?.id) return;

    loading.value = true;
    try {
        const response = await axios.get(
            `/api/v1/businesses/${props.currentBusiness.id}/marketing-integration/dashboard`,
            { params: { date: selectedDate.value + '-01' } }
        );
        dashboardData.value = response.data.data;
    } catch (error) {
        console.error('Error fetching marketing dashboard:', error);
    } finally {
        loading.value = false;
    }
};

const calculateBonus = async () => {
    if (!props.currentBusiness?.id) return;

    try {
        const date = new Date(selectedDate.value + '-01');
        const periodStart = date.toISOString().slice(0, 10);
        const periodEnd = new Date(date.getFullYear(), date.getMonth() + 1, 0).toISOString().slice(0, 10);

        const response = await axios.post(
            `/api/v1/businesses/${props.currentBusiness.id}/marketing-integration/calculate-bonus`,
            {
                period_start: periodStart,
                period_end: periodEnd,
                base_bonus_fund: bonusFund.value,
            }
        );
        bonusCalculation.value = response.data.data;
        showBonusModal.value = true;
    } catch (error) {
        console.error('Error calculating bonus:', error);
    }
};

onMounted(() => {
    fetchDashboard();
});

watch(selectedDate, () => {
    fetchDashboard();
});

// Computed
const salesLinkage = computed(() => dashboardData.value?.sales_linkage || {});
const channelPerformance = computed(() => dashboardData.value?.channel_performance || {});
const budgetStatus = computed(() => dashboardData.value?.budget_status || {});
const leadQuality = computed(() => dashboardData.value?.lead_quality || {});
const campaignRoi = computed(() => dashboardData.value?.campaign_roi || {});

// ROI color
const getRoiColor = (roi) => {
    if (roi >= 200) return 'text-green-600 bg-green-100';
    if (roi >= 100) return 'text-blue-600 bg-blue-100';
    if (roi >= 50) return 'text-yellow-600 bg-yellow-100';
    if (roi >= 0) return 'text-orange-600 bg-orange-100';
    return 'text-red-600 bg-red-100';
};

// Format number
const formatNumber = (num) => {
    if (!num) return '0';
    return new Intl.NumberFormat('uz-UZ').format(Math.round(num));
};

// Format currency
const formatCurrency = (amount) => {
    if (!amount) return '0 UZS';
    return new Intl.NumberFormat('uz-UZ').format(Math.round(amount)) + ' UZS';
};

// Quality score color
const getQualityColor = (score) => {
    if (score >= 4.5) return 'text-green-600';
    if (score >= 3.5) return 'text-blue-600';
    if (score >= 2.5) return 'text-yellow-600';
    return 'text-red-600';
};
</script>

<template>
    <AppLayout>
        <Head title="Marketing Dashboard - Sotuv bilan integratsiya" />

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Marketing - Sotuv integratsiyasi
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        70/30 qoidasi: Marketing bonusi 70% sotuvdan, 30% vazifalardan
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <input
                        v-model="selectedDate"
                        type="month"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm"
                    />
                    <button
                        @click="calculateBonus"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center"
                    >
                        <SparklesIcon class="w-5 h-5 mr-2" />
                        Bonus hisoblash
                    </button>
                </div>
            </div>

            <div v-if="loading" class="flex items-center justify-center py-20">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
            </div>

            <div v-else-if="dashboardData" class="space-y-8">
                <!-- Sales Linkage Banner (70/30 Rule) -->
                <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-purple-700 rounded-3xl p-8 shadow-2xl">
                    <div class="absolute inset-0 bg-grid-white/[0.05] bg-[size:20px_20px]"></div>
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                    <div class="relative">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                <ChartPieIcon class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-white">Marketing-Sotuv bog'liqligi</h2>
                                <p class="text-purple-100">70% sotuvdan + 30% o'z vazifalaridan</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                            <!-- Sotuv rejasi -->
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                                <p class="text-sm text-purple-100 mb-1">Sotuv rejasi bajarilishi</p>
                                <p class="text-4xl font-bold text-white">
                                    {{ salesLinkage.sales_plan_completion?.toFixed(1) || 0 }}%
                                </p>
                                <div class="w-full bg-white/20 rounded-full h-2 mt-3">
                                    <div
                                        class="h-2 rounded-full bg-white transition-all"
                                        :style="{ width: Math.min(salesLinkage.sales_plan_completion || 0, 100) + '%' }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Sotuvdan bonus ulushi -->
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                                <p class="text-sm text-purple-100 mb-1">Sotuvdan bonus (70%)</p>
                                <p class="text-4xl font-bold text-white">
                                    {{ salesLinkage.marketing_bonus_impact?.contribution_percent?.toFixed(1) || 0 }}%
                                </p>
                                <p class="text-sm text-purple-200 mt-2">
                                    Maksimal: {{ salesLinkage.marketing_bonus_impact?.max_possible }}%
                                </p>
                            </div>

                            <!-- Vazifalar -->
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                                <p class="text-sm text-purple-100 mb-1">Vazifalar bajarilishi (30%)</p>
                                <p class="text-4xl font-bold text-white">
                                    {{ salesLinkage.avg_tasks_completion?.toFixed(1) || 0 }}%
                                </p>
                                <p class="text-sm text-purple-200 mt-2">
                                    {{ salesLinkage.total_marketing_kpis || 0 }} ta KPI qayd etilgan
                                </p>
                            </div>
                        </div>

                        <p class="mt-6 text-purple-100 text-sm bg-white/10 rounded-lg p-3">
                            {{ salesLinkage.explanation }}
                        </p>
                    </div>
                </div>

                <!-- Channel Performance -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                            <MegaphoneIcon class="w-5 h-5 mr-2 text-purple-600" />
                            Kanal samaradorligi
                        </h3>
                        <div class="flex items-center gap-4 text-sm">
                            <span class="text-gray-500">
                                Umumiy ROI:
                                <span :class="[getRoiColor(channelPerformance.overall_roi), 'font-bold px-2 py-1 rounded-full ml-1']">
                                    {{ channelPerformance.overall_roi?.toFixed(1) || 0 }}%
                                </span>
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kanal</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sarflangan</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Lidlar</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">CPL</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Bitimlar</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Daromad</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">ROI</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tavsiya</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr
                                    v-for="channel in channelPerformance.channels"
                                    :key="channel.channel_id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                >
                                    <td class="px-4 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center text-white font-bold">
                                                {{ channel.channel_name?.charAt(0) || '?' }}
                                            </div>
                                            <div class="ml-3">
                                                <p class="font-medium text-gray-900 dark:text-white">
                                                    {{ channel.channel_name }}
                                                </p>
                                                <p class="text-xs text-gray-500">{{ channel.channel_type }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-right text-gray-500">
                                        {{ formatCurrency(channel.spent) }}
                                    </td>
                                    <td class="px-4 py-4 text-right font-medium text-gray-900 dark:text-white">
                                        {{ channel.leads }}
                                    </td>
                                    <td class="px-4 py-4 text-right text-gray-500">
                                        {{ formatCurrency(channel.cpl) }}
                                    </td>
                                    <td class="px-4 py-4 text-right font-medium text-green-600">
                                        {{ channel.deals }}
                                    </td>
                                    <td class="px-4 py-4 text-right font-medium text-gray-900 dark:text-white">
                                        {{ formatCurrency(channel.revenue) }}
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <span :class="[getRoiColor(channel.roi), 'px-2 py-1 text-sm font-medium rounded-full']">
                                            {{ channel.roi?.toFixed(1) }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-left text-sm text-gray-500 max-w-xs">
                                        {{ channel.recommendation }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="!channelPerformance.channels?.length" class="text-center py-8 text-gray-500">
                        Kanal ma'lumotlari topilmadi
                    </div>
                </div>

                <!-- Budget & Lead Quality -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Budget Status -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <BanknotesIcon class="w-5 h-5 mr-2 text-green-600" />
                            Byudjet holati
                        </h3>

                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4">
                                <p class="text-sm text-gray-500">Jami limit</p>
                                <p class="text-2xl font-bold text-green-600">
                                    {{ formatCurrency(budgetStatus.total_limit) }}
                                </p>
                            </div>
                            <div :class="[
                                'rounded-xl p-4',
                                budgetStatus.is_within_budget ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-red-50 dark:bg-red-900/20'
                            ]">
                                <p class="text-sm text-gray-500">Sarflangan</p>
                                <p :class="[
                                    'text-2xl font-bold',
                                    budgetStatus.is_within_budget ? 'text-blue-600' : 'text-red-600'
                                ]">
                                    {{ formatCurrency(budgetStatus.total_spent) }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Foydalanish</span>
                                <span class="font-medium">{{ budgetStatus.usage_percent?.toFixed(1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div
                                    :class="[
                                        'h-3 rounded-full transition-all',
                                        budgetStatus.usage_percent > 100 ? 'bg-red-500' :
                                        budgetStatus.usage_percent > 80 ? 'bg-yellow-500' : 'bg-green-500'
                                    ]"
                                    :style="{ width: Math.min(budgetStatus.usage_percent || 0, 100) + '%' }"
                                ></div>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Qolgan</span>
                                <span class="font-medium text-green-600">
                                    {{ formatCurrency(budgetStatus.total_remaining) }}
                                </span>
                            </div>
                        </div>

                        <div v-if="budgetStatus.over_budget_count > 0" class="mt-4 bg-red-50 dark:bg-red-900/20 rounded-lg p-3 flex items-center">
                            <ExclamationTriangleIcon class="w-5 h-5 text-red-600 mr-2" />
                            <span class="text-sm text-red-600">
                                {{ budgetStatus.over_budget_count }} ta kanal byudjetdan oshgan!
                            </span>
                        </div>
                    </div>

                    <!-- Lead Quality -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <UserGroupIcon class="w-5 h-5 mr-2 text-blue-600" />
                            Lid sifati (Sotuv feedbacki)
                        </h3>

                        <div class="flex items-center justify-center mb-6">
                            <div class="relative">
                                <div class="w-32 h-32 rounded-full border-8 border-gray-200 dark:border-gray-700 flex items-center justify-center">
                                    <div class="text-center">
                                        <p :class="['text-3xl font-bold', getQualityColor(leadQuality.avg_quality_score)]">
                                            {{ leadQuality.avg_quality_score?.toFixed(1) || 0 }}
                                        </p>
                                        <p class="text-sm text-gray-500">/ 5.0</p>
                                    </div>
                                </div>
                                <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2">
                                    <span :class="[
                                        'px-3 py-1 text-sm font-medium rounded-full',
                                        getQualityColor(leadQuality.avg_quality_score),
                                        'bg-opacity-20'
                                    ]">
                                        {{ leadQuality.quality_label }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ leadQuality.acceptance_rate?.toFixed(1) || 0 }}%
                                </p>
                                <p class="text-sm text-gray-500">Qabul qilingan</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-green-600">
                                    {{ leadQuality.conversion_rate?.toFixed(1) || 0 }}%
                                </p>
                                <p class="text-sm text-gray-500">Konversiya</p>
                            </div>
                        </div>

                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-500">Jami lidlar:</span>
                            <span class="font-medium">{{ leadQuality.total_generated || 0 }}</span>
                        </div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-500">Qabul qilingan:</span>
                            <span class="font-medium text-green-600">{{ leadQuality.total_accepted || 0 }}</span>
                        </div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-500">Rad etilgan:</span>
                            <span class="font-medium text-red-600">{{ leadQuality.total_rejected || 0 }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Bitimga aylangan:</span>
                            <span class="font-medium text-blue-600">{{ leadQuality.total_converted || 0 }}</span>
                        </div>

                        <div v-if="leadQuality.improvement_areas?.length" class="mt-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200 font-medium mb-1">Yaxshilash kerak:</p>
                            <ul class="text-sm text-yellow-700 dark:text-yellow-300 list-disc list-inside">
                                <li v-for="area in leadQuality.improvement_areas" :key="area">{{ area }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Campaign ROI -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                        <RocketLaunchIcon class="w-5 h-5 mr-2 text-purple-600" />
                        Kampaniyalar ROI tahlili
                    </h3>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 text-center">
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                {{ campaignRoi.total_campaigns || 0 }}
                            </p>
                            <p class="text-sm text-gray-500">Jami kampaniyalar</p>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 text-center">
                            <p class="text-3xl font-bold text-green-600">
                                {{ campaignRoi.profitable_count || 0 }}
                            </p>
                            <p class="text-sm text-gray-500">Foydali</p>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4 text-center">
                            <p class="text-3xl font-bold text-red-600">
                                {{ campaignRoi.unprofitable_count || 0 }}
                            </p>
                            <p class="text-sm text-gray-500">Zararli</p>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 text-center">
                            <p class="text-3xl font-bold text-blue-600">
                                {{ campaignRoi.avg_roi?.toFixed(1) || 0 }}%
                            </p>
                            <p class="text-sm text-gray-500">O'rtacha ROI</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="campaignRoi.best_campaign" class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4">
                            <div class="flex items-center mb-2">
                                <CheckCircleIcon class="w-5 h-5 text-green-600 mr-2" />
                                <span class="font-medium text-green-800 dark:text-green-200">Eng yaxshi kampaniya</span>
                            </div>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ campaignRoi.best_campaign.name }}
                            </p>
                            <p class="text-sm text-gray-500">
                                ROI: {{ campaignRoi.best_campaign.roi?.toFixed(1) }}% |
                                Daromad: {{ formatCurrency(campaignRoi.best_campaign.revenue_generated) }}
                            </p>
                        </div>

                        <div v-if="campaignRoi.worst_campaign" class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4">
                            <div class="flex items-center mb-2">
                                <ExclamationTriangleIcon class="w-5 h-5 text-red-600 mr-2" />
                                <span class="font-medium text-red-800 dark:text-red-200">Eng yomon kampaniya</span>
                            </div>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ campaignRoi.worst_campaign.name }}
                            </p>
                            <p class="text-sm text-gray-500">
                                ROI: {{ campaignRoi.worst_campaign.roi?.toFixed(1) }}% |
                                Daromad: {{ formatCurrency(campaignRoi.worst_campaign.revenue_generated) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4">
                        <p class="text-sm text-gray-500">Umumiy foyda:</p>
                        <p :class="[
                            'text-2xl font-bold',
                            (campaignRoi.total_profit || 0) >= 0 ? 'text-green-600' : 'text-red-600'
                        ]">
                            {{ formatCurrency(campaignRoi.total_profit) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- No data state -->
            <div v-else-if="!loading" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center">
                <MegaphoneIcon class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    Ma'lumot topilmadi
                </h3>
                <p class="text-gray-500">
                    Marketing ma'lumotlari mavjud emas
                </p>
            </div>
        </div>

        <!-- Bonus Calculation Modal -->
        <div
            v-if="showBonusModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showBonusModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full mx-4 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                    Marketing bonus hisoblash natijasi
                </h3>

                <div v-if="bonusCalculation" class="space-y-4">
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4">
                        <p class="text-sm text-gray-500">Bazaviy bonus fondi</p>
                        <p class="text-2xl font-bold text-purple-600">
                            {{ formatCurrency(bonusCalculation.base_fund) }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4">
                            <p class="text-sm text-gray-500">Sotuv bajarilishi</p>
                            <p class="text-xl font-bold text-blue-600">
                                {{ bonusCalculation.sales_completion?.toFixed(1) }}%
                            </p>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4">
                            <p class="text-sm text-gray-500">Vazifalar bajarilishi</p>
                            <p class="text-xl font-bold text-green-600">
                                {{ bonusCalculation.tasks_completion?.toFixed(1) }}%
                            </p>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div v-for="item in bonusCalculation.breakdown" :key="item.name" class="flex justify-between py-2">
                            <span class="text-gray-600 dark:text-gray-400">
                                {{ item.name }} ({{ item.completion?.toFixed(1) }}%)
                            </span>
                            <span class="font-medium">{{ formatCurrency(item.amount) }}</span>
                        </div>
                    </div>

                    <div class="bg-green-100 dark:bg-green-900/30 rounded-xl p-4 flex justify-between items-center">
                        <span class="font-medium text-green-800 dark:text-green-200">Jami bonus:</span>
                        <span class="text-2xl font-bold text-green-600">
                            {{ formatCurrency(bonusCalculation.total_bonus) }}
                        </span>
                    </div>

                    <p class="text-sm text-gray-500 text-center">
                        Bazadan {{ bonusCalculation.bonus_percent?.toFixed(1) }}% olindi
                    </p>
                </div>

                <button
                    @click="showBonusModal = false"
                    class="mt-6 w-full py-3 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors"
                >
                    Yopish
                </button>
            </div>
        </div>
    </AppLayout>
</template>
