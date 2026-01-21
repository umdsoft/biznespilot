<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ChartBarIcon,
    CurrencyDollarIcon,
    UserGroupIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    ExclamationTriangleIcon,
    BanknotesIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    ChartPieIcon,
    CalendarIcon,
    TrophyIcon,
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
const selectedDate = ref(new Date().toISOString().slice(0, 7)); // YYYY-MM format
const dashboardData = ref(null);

// API Fetch
const fetchDashboard = async () => {
    if (!props.currentBusiness?.id) return;

    loading.value = true;
    try {
        const response = await axios.get(
            `/api/v1/businesses/${props.currentBusiness.id}/sales-analytics/dashboard`,
            { params: { date: selectedDate.value + '-01' } }
        );
        dashboardData.value = response.data.data;
    } catch (error) {
        console.error('Error fetching sales dashboard:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchDashboard();
});

watch(selectedDate, () => {
    fetchDashboard();
});

// Computed
const currentPeriod = computed(() => dashboardData.value?.current_period || {});
const managerRankings = computed(() => dashboardData.value?.manager_rankings || {});
const receivables = computed(() => dashboardData.value?.receivables || {});
const rejectionAnalysis = computed(() => dashboardData.value?.rejection_analysis || {});
const trendData = computed(() => dashboardData.value?.trend || []);

// KPI Score color
const getKpiColor = (score) => {
    if (score < 0) return 'text-red-600 bg-red-100';
    if (score < 0.5) return 'text-orange-600 bg-orange-100';
    if (score < 1) return 'text-yellow-600 bg-yellow-100';
    if (score >= 1) return 'text-green-600 bg-green-100';
    return 'text-gray-600 bg-gray-100';
};

// Progress bar color
const getProgressColor = (percent) => {
    if (percent >= 100) return 'bg-green-500';
    if (percent >= 80) return 'bg-blue-500';
    if (percent >= 50) return 'bg-yellow-500';
    return 'bg-red-500';
};

// Format number
const formatNumber = (num) => {
    if (!num) return '0';
    return new Intl.NumberFormat('uz-UZ').format(Math.round(num));
};

// Format currency
const formatCurrency = (amount) => {
    if (!amount) return '0 UZS';
    return new Intl.NumberFormat('uz-UZ', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount) + ' UZS';
};
</script>

<template>
    <AppLayout>
        <Head title="Sotuv Dashboardi - ROP" />

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Sotuv bo'limi dashboardi
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Denis Shenukov metodologiyasi asosida tahlil
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <input
                        v-model="selectedDate"
                        type="month"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    <button
                        @click="fetchDashboard"
                        :disabled="loading"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"
                    >
                        <span v-if="loading">Yuklanmoqda...</span>
                        <span v-else>Yangilash</span>
                    </button>
                </div>
            </div>

            <div v-if="loading" class="flex items-center justify-center py-20">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <div v-else-if="dashboardData" class="space-y-8">
                <!-- KPI Overview Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Reja bajarilishi -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                                <ChartBarIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <span
                                v-if="currentPeriod.has_target"
                                :class="[
                                    'px-3 py-1 text-sm font-medium rounded-full',
                                    getKpiColor(currentPeriod.kpi_score)
                                ]"
                            >
                                KPI: {{ (currentPeriod.kpi_score * 100).toFixed(1) }}%
                            </span>
                        </div>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Reja bajarilishi</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                {{ currentPeriod.completion_percent?.toFixed(1) || 0 }}%
                            </p>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div
                                    :class="['h-2 rounded-full transition-all', getProgressColor(currentPeriod.completion_percent)]"
                                    :style="{ width: Math.min(currentPeriod.completion_percent || 0, 100) + '%' }"
                                ></div>
                            </div>
                            <p class="text-xs text-gray-500">
                                {{ currentPeriod.kpi_interpretation?.message }}
                            </p>
                        </div>
                    </div>

                    <!-- Haqiqiy daromad -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                                <CurrencyDollarIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div v-if="currentPeriod.is_on_track" class="flex items-center text-green-600">
                                <ArrowTrendingUpIcon class="w-5 h-5 mr-1" />
                                <span class="text-sm font-medium">Trendda</span>
                            </div>
                            <div v-else class="flex items-center text-red-600">
                                <ArrowTrendingDownIcon class="w-5 h-5 mr-1" />
                                <span class="text-sm font-medium">Orqada</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Haqiqiy daromad</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ formatCurrency(currentPeriod.fact_revenue) }}
                            </p>
                            <p class="text-sm text-gray-500">
                                Reja: {{ formatCurrency(currentPeriod.plan_revenue) }}
                            </p>
                        </div>
                    </div>

                    <!-- Qolgan maqsad -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center">
                                <ClockIcon class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                            </div>
                            <span class="text-sm font-medium text-orange-600 dark:text-orange-400">
                                {{ currentPeriod.days_remaining }} kun qoldi
                            </span>
                        </div>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Qolgan maqsad</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ formatCurrency(currentPeriod.remaining_revenue) }}
                            </p>
                            <p class="text-sm text-gray-500">
                                Kunlik kerak: {{ formatCurrency(currentPeriod.daily_target) }}
                            </p>
                        </div>
                    </div>

                    <!-- Debitorka -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                                <BanknotesIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
                            </div>
                            <span
                                v-if="receivables.overdue_percent > 20"
                                class="px-2 py-1 text-xs font-medium text-red-600 bg-red-100 rounded-full"
                            >
                                Xavf!
                            </span>
                        </div>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Debitorka</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ formatCurrency(receivables.total_amount) }}
                            </p>
                            <p class="text-sm text-red-600">
                                Muddati o'tgan: {{ formatCurrency(receivables.overdue_amount) }}
                                ({{ receivables.overdue_percent?.toFixed(1) }}%)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Deals & New Clients -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <CheckCircleIcon class="w-5 h-5 mr-2 text-green-600" />
                            Bitimlar
                        </h3>
                        <div class="flex items-end justify-between">
                            <div>
                                <p class="text-4xl font-bold text-gray-900 dark:text-white">
                                    {{ currentPeriod.deals?.fact || 0 }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    Rejadan {{ currentPeriod.deals?.plan || 0 }} ta
                                </p>
                            </div>
                            <div class="text-right">
                                <p
                                    :class="[
                                        'text-2xl font-bold',
                                        (currentPeriod.deals?.percent || 0) >= 100 ? 'text-green-600' : 'text-orange-600'
                                    ]"
                                >
                                    {{ currentPeriod.deals?.percent?.toFixed(1) || 0 }}%
                                </p>
                                <p class="text-sm text-gray-500">bajarildi</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <UserGroupIcon class="w-5 h-5 mr-2 text-blue-600" />
                            Yangi mijozlar
                        </h3>
                        <div class="flex items-end justify-between">
                            <div>
                                <p class="text-4xl font-bold text-gray-900 dark:text-white">
                                    {{ currentPeriod.new_clients?.fact || 0 }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    Rejadan {{ currentPeriod.new_clients?.plan || 0 }} ta
                                </p>
                            </div>
                            <div class="h-16 w-16 rounded-full border-4 border-blue-500 flex items-center justify-center">
                                <span class="text-lg font-bold text-blue-600">
                                    {{ currentPeriod.new_clients?.plan > 0
                                        ? Math.round((currentPeriod.new_clients?.fact / currentPeriod.new_clients?.plan) * 100)
                                        : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Manager Rankings -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                            <TrophyIcon class="w-5 h-5 mr-2 text-yellow-500" />
                            Menejerlar reytingi
                        </h3>
                        <div class="flex gap-2 text-sm">
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full">
                                {{ managerRankings.on_target_count || 0 }} rejada
                            </span>
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full">
                                {{ managerRankings.below_target_count || 0 }} orqada
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Menejer</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Reja</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Fakt</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Bajarilish</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">KPI</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Bitimlar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr
                                    v-for="(manager, index) in managerRankings.rankings"
                                    :key="manager.user_id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                >
                                    <td class="px-4 py-4">
                                        <span
                                            :class="[
                                                'inline-flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm',
                                                index === 0 ? 'bg-yellow-100 text-yellow-700' :
                                                index === 1 ? 'bg-gray-100 text-gray-700' :
                                                index === 2 ? 'bg-orange-100 text-orange-700' :
                                                'bg-gray-50 text-gray-500'
                                            ]"
                                        >
                                            {{ index + 1 }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                                                {{ manager.user_name?.charAt(0) || '?' }}
                                            </div>
                                            <span class="ml-3 font-medium text-gray-900 dark:text-white">
                                                {{ manager.user_name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-right text-gray-500">
                                        {{ formatNumber(manager.plan) }}
                                    </td>
                                    <td class="px-4 py-4 text-right font-medium text-gray-900 dark:text-white">
                                        {{ formatNumber(manager.fact) }}
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <div class="flex items-center justify-end">
                                            <div class="w-20 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                                <div
                                                    :class="['h-2 rounded-full', getProgressColor(manager.completion_percent)]"
                                                    :style="{ width: Math.min(manager.completion_percent, 100) + '%' }"
                                                ></div>
                                            </div>
                                            <span :class="manager.completion_percent >= 100 ? 'text-green-600' : 'text-gray-600'">
                                                {{ manager.completion_percent?.toFixed(1) }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <span
                                            :class="[
                                                'px-2 py-1 text-sm font-medium rounded-full',
                                                getKpiColor(manager.kpi_score)
                                            ]"
                                        >
                                            {{ (manager.kpi_score * 100).toFixed(0) }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-right font-medium">
                                        {{ manager.deals_closed }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="!managerRankings.rankings?.length" class="text-center py-8 text-gray-500">
                        Menejer ma'lumotlari topilmadi
                    </div>
                </div>

                <!-- Receivables Aging & Lost Deals -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Debitorka yoshi -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <BanknotesIcon class="w-5 h-5 mr-2 text-red-600" />
                            Debitorka yoshi (Aging)
                        </h3>
                        <div class="space-y-4">
                            <div
                                v-for="(amount, key) in receivables.aging"
                                :key="key"
                                class="flex items-center justify-between"
                            >
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{
                                        key === 'current' ? 'Joriy' :
                                        key === '1_30_days' ? '1-30 kun' :
                                        key === '31_60_days' ? '31-60 kun' :
                                        key === '61_90_days' ? '61-90 kun' :
                                        '90+ kun'
                                    }}
                                </span>
                                <div class="flex items-center">
                                    <div
                                        :class="[
                                            'h-4 rounded mr-3',
                                            key === 'current' ? 'bg-green-500' :
                                            key === '1_30_days' ? 'bg-yellow-500' :
                                            key === '31_60_days' ? 'bg-orange-500' :
                                            'bg-red-500'
                                        ]"
                                        :style="{
                                            width: receivables.total_amount > 0
                                                ? Math.max((amount / receivables.total_amount) * 200, 4) + 'px'
                                                : '4px'
                                        }"
                                    ></div>
                                    <span class="font-medium text-gray-900 dark:text-white min-w-[100px] text-right">
                                        {{ formatCurrency(amount) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Yo'qotilgan bitimlar -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <XCircleIcon class="w-5 h-5 mr-2 text-red-600" />
                            Yo'qotilgan bitimlar tahlili
                        </h3>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4">
                                <p class="text-3xl font-bold text-red-600">{{ rejectionAnalysis.total_lost || 0 }}</p>
                                <p class="text-sm text-gray-500">Jami yo'qotilgan</p>
                            </div>
                            <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4">
                                <p class="text-2xl font-bold text-red-600">{{ formatCurrency(rejectionAnalysis.total_lost_value) }}</p>
                                <p class="text-sm text-gray-500">Potensial qiymat</p>
                            </div>
                        </div>
                        <div v-if="rejectionAnalysis.top_reason" class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                            <p class="text-sm text-gray-500 mb-1">Eng ko'p rad etish sababi:</p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ rejectionAnalysis.top_reason.reason_name }}
                                <span class="text-sm text-gray-500">({{ rejectionAnalysis.top_reason.count }} marta)</span>
                            </p>
                        </div>
                        <div v-if="rejectionAnalysis.top_competitor" class="mt-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                            <p class="text-sm text-gray-500 mb-1">Asosiy raqobatchi:</p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ rejectionAnalysis.top_competitor.competitor_name }}
                                <span class="text-sm text-gray-500">({{ rejectionAnalysis.top_competitor.count }} marta)</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Trend Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                        <ChartBarIcon class="w-5 h-5 mr-2 text-blue-600" />
                        6 oylik trend
                    </h3>
                    <div class="h-64 flex items-end justify-between gap-4">
                        <div
                            v-for="month in trendData"
                            :key="month.month"
                            class="flex-1 flex flex-col items-center"
                        >
                            <div class="w-full flex flex-col items-center gap-1 h-48">
                                <!-- Reja bar -->
                                <div
                                    class="w-6 bg-gray-300 dark:bg-gray-600 rounded-t transition-all"
                                    :style="{
                                        height: trendData.length > 0 && Math.max(...trendData.map(t => t.plan)) > 0
                                            ? (month.plan / Math.max(...trendData.map(t => t.plan))) * 100 + '%'
                                            : '0%'
                                    }"
                                    :title="'Reja: ' + formatNumber(month.plan)"
                                ></div>
                                <!-- Fakt bar -->
                                <div
                                    :class="[
                                        'w-6 rounded-t transition-all',
                                        month.completion_percent >= 100 ? 'bg-green-500' : 'bg-blue-500'
                                    ]"
                                    :style="{
                                        height: trendData.length > 0 && Math.max(...trendData.map(t => t.plan)) > 0
                                            ? (month.fact / Math.max(...trendData.map(t => t.plan))) * 100 + '%'
                                            : '0%'
                                    }"
                                    :title="'Fakt: ' + formatNumber(month.fact)"
                                ></div>
                            </div>
                            <div class="mt-2 text-center">
                                <p class="text-xs font-medium text-gray-900 dark:text-white">
                                    {{ month.completion_percent?.toFixed(0) }}%
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ month.month_name?.slice(0, 3) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-center gap-6 mt-4">
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-gray-300 dark:bg-gray-600 rounded mr-2"></div>
                            <span class="text-sm text-gray-500">Reja</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-blue-500 rounded mr-2"></div>
                            <span class="text-sm text-gray-500">Fakt</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-green-500 rounded mr-2"></div>
                            <span class="text-sm text-gray-500">100%+ bajarildi</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No data state -->
            <div v-else-if="!loading" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center">
                <ChartBarIcon class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    Ma'lumot topilmadi
                </h3>
                <p class="text-gray-500 mb-6">
                    Ushbu davr uchun sotuv ma'lumotlari mavjud emas. Avval sotuv rejasini yarating.
                </p>
                <Link
                    href="/sales/targets/create"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                    Reja yaratish
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
