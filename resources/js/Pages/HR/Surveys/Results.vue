<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import {
    ArrowLeftIcon,
    ArrowPathIcon,
    HeartIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    currentBusiness: Object,
    surveyId: String,
});

const loading = ref(true);
const data = ref(null);
const errorMessage = ref('');
const activeTab = ref('engagement');

// Engagement & Flight Risk data
const engagementData = ref(null);
const flightRiskData = ref(null);
const loadingEngagement = ref(false);
const loadingFlightRisk = ref(false);

const tabs = [
    { id: 'engagement', label: 'Ishga Qiziqish', icon: HeartIcon },
    { id: 'flightrisk', label: 'Ketish Xavfi', icon: ExclamationTriangleIcon },
];

const fetchResults = async () => {
    if (!props.currentBusiness?.id || !props.surveyId) {
        errorMessage.value = 'Biznes yoki so\'rovnoma tanlanmagan';
        loading.value = false;
        return;
    }

    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await axios.get(
            `/api/v1/businesses/${props.currentBusiness.id}/hr/surveys/${props.surveyId}/results`
        );
        data.value = response.data.data;
    } catch (error) {
        console.error('Error fetching results:', error);
        errorMessage.value = error.response?.data?.message || "Natijalarni yuklashda xatolik";
    } finally {
        loading.value = false;
    }
};

const fetchEngagementData = async () => {
    if (!props.currentBusiness?.id || !props.surveyId) return;

    loadingEngagement.value = true;
    try {
        const response = await axios.get(
            `/api/v1/businesses/${props.currentBusiness.id}/hr/surveys/${props.surveyId}/engagement`
        );
        engagementData.value = response.data.data;
    } catch (error) {
        console.error('Error fetching engagement data:', error);
        engagementData.value = null;
    } finally {
        loadingEngagement.value = false;
    }
};

const fetchFlightRiskData = async () => {
    if (!props.currentBusiness?.id || !props.surveyId) return;

    loadingFlightRisk.value = true;
    try {
        const response = await axios.get(
            `/api/v1/businesses/${props.currentBusiness.id}/hr/surveys/${props.surveyId}/flight-risk`
        );
        flightRiskData.value = response.data.data;
    } catch (error) {
        console.error('Error fetching flight risk data:', error);
        flightRiskData.value = null;
    } finally {
        loadingFlightRisk.value = false;
    }
};

// Watch for tab changes to load data
watch(activeTab, (newTab) => {
    if (newTab === 'engagement' && !engagementData.value && !loadingEngagement.value) {
        fetchEngagementData();
    }
    if (newTab === 'flightrisk' && !flightRiskData.value && !loadingFlightRisk.value) {
        fetchFlightRiskData();
    }
});

// Engagement helpers
const getEngagementScoreColor = (score) => {
    if (score >= 80) return 'text-emerald-500';
    if (score >= 65) return 'text-blue-500';
    if (score >= 50) return 'text-amber-500';
    return 'text-red-500';
};

const getEngagementBarColor = (score) => {
    if (score >= 80) return 'bg-emerald-500';
    if (score >= 65) return 'bg-blue-500';
    if (score >= 50) return 'bg-amber-500';
    return 'bg-red-500';
};

const getEngagementLevelLabel = (level) => {
    const labels = {
        highly_engaged: 'Juda qiziqgan',
        engaged: 'Qiziqgan',
        neutral: 'Neytral',
        disengaged: 'Qiziqmagan',
    };
    return labels[level] || level;
};

const getEngagementLevelColor = (level) => {
    const colors = {
        highly_engaged: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
        engaged: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        neutral: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
        disengaged: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };
    return colors[level] || 'bg-gray-100 text-gray-800';
};

const getComponentLabel = (key) => {
    const labels = {
        work_satisfaction: 'Ish qoniqishi',
        team_collaboration: 'Jamoa hamkorligi',
        growth_opportunities: "O'sish imkoniyatlari",
        recognition_frequency: 'Tan olinish',
        manager_support: "Menejer qo'llab-quvvatlashi",
        work_life_balance: 'Ish-hayot balansi',
        purpose_clarity: 'Maqsad aniqligi',
        resources_adequacy: 'Resurslar yetarliligi',
    };
    return labels[key] || key;
};

// Flight Risk helpers
const getRiskLevelLabel = (level) => {
    const labels = {
        critical: 'Juda yuqori xavf',
        high: 'Yuqori xavf',
        medium: "O'rtacha xavf",
        low: 'Past xavf',
    };
    return labels[level] || level;
};

const getRiskLevelColor = (level) => {
    const colors = {
        critical: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        low: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    };
    return colors[level] || 'bg-gray-100 text-gray-800';
};

const getRiskScoreColor = (score) => {
    if (score >= 70) return 'text-red-500';
    if (score >= 50) return 'text-orange-500';
    if (score >= 30) return 'text-yellow-500';
    return 'text-green-500';
};

onMounted(() => {
    fetchResults();
    fetchEngagementData();
});
</script>

<template>
    <HRLayout title="So'rovnoma natijalari">
        <Head :title="data?.survey?.title ? `${data.survey.title} - Natijalar` : 'Natijalar'" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="`/hr/surveys/${surveyId}`"
                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        <ArrowLeftIcon class="w-5 h-5 text-gray-500" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ data?.survey?.title || "So'rovnoma" }}
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">
                            Hodimlar javoblarining tahlili
                        </p>
                    </div>
                </div>
                <button
                    @click="fetchResults"
                    :disabled="loading"
                    class="flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300"
                >
                    <ArrowPathIcon :class="['w-5 h-5', loading ? 'animate-spin' : '']" />
                    Yangilash
                </button>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex space-x-8">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'flex items-center gap-2 py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                            activeTab === tab.id
                                ? 'border-purple-500 text-purple-600 dark:text-purple-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                        ]"
                    >
                        <component :is="tab.icon" class="w-5 h-5" />
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <!-- Tab: Ishga Qiziqish -->
            <div v-if="activeTab === 'engagement'" class="space-y-6">
                <!-- Loading -->
                <div v-if="loadingEngagement" class="flex items-center justify-center py-12">
                    <ArrowPathIcon class="w-8 h-8 animate-spin text-purple-600" />
                </div>

                <!-- No Data -->
                <div v-else-if="!engagementData" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700">
                    <HeartIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Ma'lumot yo'q</h3>
                    <p class="text-gray-500 dark:text-gray-400">
                        Bu so'rovnoma uchun ishga qiziqish ma'lumotlari mavjud emas
                    </p>
                </div>

                <!-- Engagement Data -->
                <template v-else>
                    <!-- Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                                    <HeartIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha ball</p>
                                    <p :class="['text-2xl font-bold', getEngagementScoreColor(engagementData.avg_score || 0)]">
                                        {{ (engagementData.avg_score || 0).toFixed(1) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Qiziqgan</p>
                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                {{ (engagementData.distribution?.highly_engaged?.count || 0) + (engagementData.distribution?.engaged?.count || 0) }}
                            </p>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Neytral</p>
                            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                                {{ engagementData.distribution?.neutral?.count || 0 }}
                            </p>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Qiziqmagan</p>
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                                {{ engagementData.distribution?.disengaged?.count || 0 }}
                            </p>
                        </div>
                    </div>

                    <!-- Components -->
                    <div v-if="engagementData.components" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Komponent bo'yicha balllar
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div
                                v-for="(score, key) in engagementData.components"
                                :key="key"
                                class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ getComponentLabel(key) }}</span>
                                    <span :class="['text-lg font-bold', getEngagementScoreColor(score)]">{{ score }}</span>
                                </div>
                                <div class="bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                    <div
                                        :class="['h-2 rounded-full', getEngagementBarColor(score)]"
                                        :style="{ width: `${score}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Employees List -->
                    <div v-if="engagementData.employees?.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Hodimlar ro'yxati</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Hodim</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ball</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Daraja</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="emp in engagementData.employees" :key="emp.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ emp.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="['text-lg font-bold', getEngagementScoreColor(emp.overall_score)]">
                                                {{ emp.overall_score?.toFixed(1) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="['px-3 py-1 text-xs font-medium rounded-full', getEngagementLevelColor(emp.engagement_level)]">
                                                {{ getEngagementLevelLabel(emp.engagement_level) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Tab: Ketish Xavfi -->
            <div v-else-if="activeTab === 'flightrisk'" class="space-y-6">
                <!-- Loading -->
                <div v-if="loadingFlightRisk" class="flex items-center justify-center py-12">
                    <ArrowPathIcon class="w-8 h-8 animate-spin text-purple-600" />
                </div>

                <!-- No Data -->
                <div v-else-if="!flightRiskData" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700">
                    <ExclamationTriangleIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Ma'lumot yo'q</h3>
                    <p class="text-gray-500 dark:text-gray-400">
                        Bu so'rovnoma uchun ketish xavfi ma'lumotlari mavjud emas
                    </p>
                </div>

                <!-- Flight Risk Data -->
                <template v-else>
                    <!-- Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami hodimlar</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ flightRiskData.total_employees || 0 }}
                            </p>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border-l-4 border-red-500">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Juda yuqori</p>
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                                {{ flightRiskData.risk_distribution?.critical?.count || 0 }}
                            </p>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border-l-4 border-orange-500">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Yuqori</p>
                            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                {{ flightRiskData.risk_distribution?.high?.count || 0 }}
                            </p>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border-l-4 border-yellow-500">
                            <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha</p>
                            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                {{ flightRiskData.risk_distribution?.medium?.count || 0 }}
                            </p>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border-l-4 border-green-500">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Past</p>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ flightRiskData.risk_distribution?.low?.count || 0 }}
                            </p>
                        </div>
                    </div>

                    <!-- Employees List -->
                    <div v-if="flightRiskData.employees?.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Hodimlar ro'yxati</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Hodim</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Xavf balli</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Daraja</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="emp in flightRiskData.employees" :key="emp.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ emp.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="['text-lg font-bold', getRiskScoreColor(emp.risk_score)]">
                                                {{ emp.risk_score }}%
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="['px-3 py-1 text-xs font-medium rounded-full', getRiskLevelColor(emp.risk_level)]">
                                                {{ getRiskLevelLabel(emp.risk_level) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </HRLayout>
</template>
