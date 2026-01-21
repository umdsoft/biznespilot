<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import {
    ArrowLeftIcon,
    ArrowPathIcon,
    PlayIcon,
    StopIcon,
    ChartBarIcon,
    DocumentDuplicateIcon,
    ClipboardDocumentListIcon,
    UserGroupIcon,
    ClockIcon,
    CheckCircleIcon,
    LinkIcon,
    HeartIcon,
    ExclamationTriangleIcon,
    LightBulbIcon,
    InformationCircleIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    currentBusiness: Object,
    surveyId: String,
});

const loading = ref(true);
const survey = ref(null);
const errorMessage = ref('');
const copySuccess = ref(false);
const activeTab = ref('overview');

// Engagement & Flight Risk data
const engagementData = ref(null);
const flightRiskData = ref(null);
const loadingEngagement = ref(false);
const loadingFlightRisk = ref(false);

const tabs = [
    { id: 'overview', label: 'Umumiy', icon: ClipboardDocumentListIcon },
    { id: 'results', label: 'Natijalar', icon: ChartBarIcon },
    { id: 'engagement', label: 'Ishga Qiziqish', icon: HeartIcon },
    { id: 'flightrisk', label: 'Ketish Xavfi', icon: ExclamationTriangleIcon },
];

const fetchSurvey = async () => {
    if (!props.currentBusiness?.id || !props.surveyId) {
        errorMessage.value = 'Biznes yoki so\'rovnoma tanlanmagan';
        loading.value = false;
        return;
    }

    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await axios.get(
            `/api/v1/businesses/${props.currentBusiness.id}/hr/surveys/${props.surveyId}`
        );
        survey.value = response.data.data;
    } catch (error) {
        console.error('Error fetching survey:', error);
        errorMessage.value = error.response?.data?.message || "So'rovnomani yuklashda xatolik";
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

const activateSurvey = async () => {
    if (!props.currentBusiness?.id) return;

    try {
        await axios.post(
            `/api/v1/businesses/${props.currentBusiness.id}/hr/surveys/${props.surveyId}/activate`
        );
        await fetchSurvey();
    } catch (error) {
        errorMessage.value = error.response?.data?.message || "Faollashtirishda xatolik";
    }
};

const closeSurvey = async () => {
    if (!props.currentBusiness?.id) return;

    try {
        await axios.post(
            `/api/v1/businesses/${props.currentBusiness.id}/hr/surveys/${props.surveyId}/close`
        );
        await fetchSurvey();
    } catch (error) {
        errorMessage.value = error.response?.data?.message || "Yopishda xatolik";
    }
};

const fillSurveyUrl = computed(() => {
    return `${window.location.origin}/hr/surveys/${props.surveyId}/fill`;
});

const copyFillUrl = async () => {
    try {
        await navigator.clipboard.writeText(fillSurveyUrl.value);
        copySuccess.value = true;
        setTimeout(() => copySuccess.value = false, 2000);
    } catch (e) {
        console.error('Copy failed:', e);
    }
};

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        closed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getTypeColor = (type) => {
    const colors = {
        engagement: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
        pulse: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        exit: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        onboarding: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400',
        '360_feedback': 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-400',
        custom: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    };
    return colors[type] || 'bg-gray-100 text-gray-800';
};

const getQuestionTypeLabel = (type) => {
    const labels = {
        scale: 'Shkala (1-5)',
        rating: 'Reyting',
        choice: 'Tanlash',
        multiple_choice: "Ko'p tanlash",
        yes_no: 'Ha/Yo\'q',
        text: 'Matn',
    };
    return labels[type] || type;
};

// Engagement helpers
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
        highly_engaged: 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30',
        engaged: 'bg-blue-500/20 text-blue-400 border border-blue-500/30',
        neutral: 'bg-amber-500/20 text-amber-400 border border-amber-500/30',
        disengaged: 'bg-red-500/20 text-red-400 border border-red-500/30',
    };
    return colors[level] || 'bg-gray-500/20 text-gray-400';
};

const getScoreColor = (score) => {
    if (score >= 80) return 'text-emerald-400';
    if (score >= 65) return 'text-blue-400';
    if (score >= 50) return 'text-amber-400';
    return 'text-red-400';
};

const getScoreBarColor = (score) => {
    if (score >= 80) return 'bg-emerald-500';
    if (score >= 65) return 'bg-blue-500';
    if (score >= 50) return 'bg-amber-500';
    return 'bg-red-500';
};

// Flight Risk helpers
const getRiskLevelLabel = (level) => {
    const labels = {
        critical: 'Juda yuqori xavf',
        high: 'Yuqori xavf',
        moderate: "O'rtacha xavf",
        medium: "O'rtacha xavf",
        low: 'Past xavf',
    };
    return labels[level] || level;
};

const getRiskLevelColor = (level) => {
    const colors = {
        critical: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
        high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300',
        moderate: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
        medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
        low: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
    };
    return colors[level] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const getRiskScoreColor = (score) => {
    if (score >= 70) return 'text-red-500 dark:text-red-400';
    if (score >= 50) return 'text-orange-500 dark:text-orange-400';
    if (score >= 30) return 'text-yellow-500 dark:text-yellow-400';
    return 'text-green-500 dark:text-green-400';
};

// Engagement insights
const engagementInsights = computed(() => {
    if (!engagementData.value) return [];

    const results = [];
    const avgScore = engagementData.value.avg_score || 0;

    if (avgScore >= 80) {
        results.push({
            type: 'success',
            title: "A'lo natija!",
            message: "Bu so'rovnomada hodimlar ishga qiziqish darajasi juda yuqori.",
        });
    } else if (avgScore < 50) {
        results.push({
            type: 'danger',
            title: 'Diqqat talab qilinadi!',
            message: "Bu so'rovnomada umumiy ishga qiziqish darajasi past.",
        });
    }

    // Component analysis
    if (engagementData.value.components) {
        const components = engagementData.value.components;
        const lowComponents = Object.entries(components)
            .filter(([key, score]) => score < 50)
            .map(([key, score]) => getComponentLabel(key));
        if (lowComponents.length > 0) {
            results.push({
                type: 'warning',
                title: 'Diqqat qilish kerak',
                message: `Quyidagi sohalarda ball past: ${lowComponents.join(', ')}`,
            });
        }
    }

    return results;
});

const getComponentLabel = (key) => {
    const labels = {
        work_satisfaction: 'Ish qoniqishi',
        team_collaboration: 'Jamoa hamkorligi',
        growth_opportunities: "O'sish imkoniyatlari",
        recognition_frequency: 'Tan olinish',
        manager_support: 'Menejer qo\'llab-quvvatlashi',
        work_life_balance: 'Ish-hayot balansi',
        purpose_clarity: 'Maqsad aniqligi',
        resources_adequacy: 'Resurslar yetarliligi',
    };
    return labels[key] || key;
};

// Flight Risk insights
const flightRiskInsights = computed(() => {
    if (!flightRiskData.value) return [];

    const results = [];
    const dist = flightRiskData.value.risk_distribution || {};

    const criticalCount = dist.critical?.count || 0;
    const highCount = dist.high?.count || 0;
    const atRiskCount = criticalCount + highCount;

    if (atRiskCount > 0) {
        results.push({
            type: 'warning',
            title: 'Yuqori xavfdagi hodimlar',
            message: `${atRiskCount} ta hodim yuqori ketish xavfida. Ular bilan suhbat o'tkazing.`,
        });
    }

    const totalEmployees = flightRiskData.value.total_employees || 0;
    const atRiskPercentage = totalEmployees > 0 ? Math.round((atRiskCount / totalEmployees) * 100) : 0;

    if (atRiskPercentage >= 30) {
        results.push({
            type: 'danger',
            title: 'Umumiy xavf yuqori',
            message: `Hodimlarning ${atRiskPercentage}% i yuqori xavfda. Kompaniyada umumiy muammo bo'lishi mumkin.`,
        });
    } else if (atRiskCount === 0) {
        results.push({
            type: 'success',
            title: 'Xavf nazorat ostida',
            message: 'Hech qaysi hodim yuqori ketish xavfida emas. Hodimlar barqaror.',
        });
    }

    return results;
});

onMounted(fetchSurvey);
</script>

<template>
    <HRLayout title="So'rovnoma tafsilotlari">
        <Head :title="survey?.title || 'So\'rovnoma'" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        href="/hr/surveys"
                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        <ArrowLeftIcon class="w-5 h-5 text-gray-500" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ survey?.title || "So'rovnoma" }}
                        </h1>
                        <div v-if="survey" class="flex items-center gap-3 mt-1">
                            <span :class="['px-3 py-1 text-xs font-medium rounded-full', getTypeColor(survey.type)]">
                                {{ survey.type_label }}
                            </span>
                            <span :class="['px-3 py-1 text-xs font-medium rounded-full', getStatusColor(survey.status)]">
                                {{ survey.status_label }}
                            </span>
                            <span v-if="survey.is_anonymous" class="text-xs text-purple-600 dark:text-purple-400">
                                Anonim
                            </span>
                        </div>
                    </div>
                </div>
                <div v-if="survey" class="flex items-center gap-3">
                    <button
                        v-if="survey.status === 'draft'"
                        @click="activateSurvey"
                        class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                    >
                        <PlayIcon class="w-5 h-5" />
                        Faollashtirish
                    </button>
                    <button
                        v-if="survey.status === 'active'"
                        @click="closeSurvey"
                        class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                    >
                        <StopIcon class="w-5 h-5" />
                        Yopish
                    </button>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex items-center justify-center py-12">
                <ArrowPathIcon class="w-8 h-8 animate-spin text-purple-600" />
            </div>

            <!-- Error -->
            <div v-else-if="errorMessage" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <p class="text-red-800 dark:text-red-400">{{ errorMessage }}</p>
            </div>

            <!-- Content -->
            <template v-else-if="survey">
                <!-- Tabs -->
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex space-x-8" aria-label="Tabs">
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
                            <span
                                v-if="tab.id === 'results' && survey.response_count > 0"
                                class="ml-1 px-2 py-0.5 text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-full"
                            >
                                {{ survey.response_count }}
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Tab Content: Overview -->
                <div v-if="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column - Survey Info -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Description -->
                        <div v-if="survey.description" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Tavsif</h3>
                            <p class="text-gray-900 dark:text-white">{{ survey.description }}</p>
                        </div>

                        <!-- Fill Link (for active surveys) -->
                        <div v-if="survey.status === 'active'" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-6">
                            <h3 class="text-sm font-medium text-green-800 dark:text-green-400 mb-3 flex items-center gap-2">
                                <LinkIcon class="w-5 h-5" />
                                Xodimlarga yuborish uchun havola
                            </h3>
                            <div class="flex items-center gap-3">
                                <input
                                    type="text"
                                    :value="fillSurveyUrl"
                                    readonly
                                    class="flex-1 px-4 py-2 bg-white dark:bg-gray-800 border border-green-300 dark:border-green-700 rounded-lg text-sm text-gray-900 dark:text-white"
                                />
                                <button
                                    @click="copyFillUrl"
                                    :class="[
                                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                                        copySuccess
                                            ? 'bg-green-600 text-white'
                                            : 'bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-700'
                                    ]"
                                >
                                    {{ copySuccess ? 'Nusxalandi!' : 'Nusxalash' }}
                                </button>
                            </div>
                        </div>

                        <!-- Questions -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border border-gray-200 dark:border-gray-700">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="font-semibold text-gray-900 dark:text-white">
                                    Savollar ({{ survey.questions?.length || 0 }})
                                </h3>
                            </div>
                            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                <div
                                    v-for="(question, index) in survey.questions"
                                    :key="index"
                                    class="px-6 py-4"
                                >
                                    <div class="flex items-start gap-4">
                                        <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center shrink-0">
                                            <span class="text-sm font-medium text-purple-600 dark:text-purple-400">
                                                {{ index + 1 }}
                                            </span>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-gray-900 dark:text-white font-medium">
                                                {{ question.text }}
                                            </p>
                                            <div class="flex items-center gap-3 mt-2">
                                                <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                                                    {{ getQuestionTypeLabel(question.type) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Stats -->
                    <div class="space-y-6">
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm space-y-4 border border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Statistika</h3>

                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <UserGroupIcon class="w-5 h-5 text-gray-400" />
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Maqsadli auditoriya</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ survey.target_count }} kishi</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <CheckCircleIcon class="w-5 h-5 text-green-500" />
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Javoblar</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        {{ survey.response_count }} ({{ survey.response_rate }}%)
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <ClipboardDocumentListIcon class="w-5 h-5 text-purple-500" />
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Savollar soni</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ survey.questions?.length || 0 }}</p>
                                </div>
                            </div>

                            <div v-if="survey.start_date" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <ClockIcon class="w-5 h-5 text-blue-500" />
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Boshlanish</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ survey.start_date }}</p>
                                </div>
                            </div>

                            <div v-if="survey.end_date" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <ClockIcon class="w-5 h-5 text-red-500" />
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Tugash</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ survey.end_date }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="survey.creator" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Yaratuvchi</h3>
                            <p class="text-gray-600 dark:text-gray-400">{{ survey.creator.name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">{{ survey.created_at }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Results -->
                <div v-else-if="activeTab === 'results'" class="space-y-6">
                    <div v-if="survey.response_count === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700">
                        <ChartBarIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Hali javoblar yo'q</h3>
                        <p class="text-gray-500 dark:text-gray-400">
                            So'rovnomani faollashtiring va xodimlarga havolani yuboring
                        </p>
                    </div>
                    <div v-else class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Javoblar natijalari
                            </h3>
                            <Link
                                :href="`/hr/surveys/${surveyId}/results`"
                                class="text-purple-600 dark:text-purple-400 hover:underline text-sm"
                            >
                                To'liq ko'rish →
                            </Link>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">
                            Jami {{ survey.response_count }} ta javob qabul qilindi ({{ survey.response_rate }}% javob berdi)
                        </p>
                    </div>
                </div>

                <!-- Tab Content: Engagement -->
                <div v-else-if="activeTab === 'engagement'" class="space-y-6">
                    <!-- Loading -->
                    <div v-if="loadingEngagement" class="flex items-center justify-center py-12">
                        <ArrowPathIcon class="w-8 h-8 animate-spin text-purple-600" />
                    </div>

                    <!-- No Data -->
                    <div v-else-if="!engagementData || survey.response_count === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700">
                        <HeartIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Ma'lumot yo'q</h3>
                        <p class="text-gray-500 dark:text-gray-400">
                            Bu so'rovnomaga javoblar kelgandan so'ng ishga qiziqish tahlili ko'rinadi
                        </p>
                    </div>

                    <!-- Engagement Content -->
                    <template v-else>
                        <!-- Statistics Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                                        <HeartIcon class="w-6 h-6 text-purple-500 dark:text-purple-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha ball</p>
                                        <p :class="['text-2xl font-bold', getScoreColor(engagementData.avg_score || 0)]">
                                            {{ (engagementData.avg_score || 0).toFixed(1) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Qiziqgan hodimlar</p>
                                <p class="text-2xl font-bold text-emerald-500 dark:text-emerald-400">
                                    {{ (engagementData.distribution?.highly_engaged?.count || 0) + (engagementData.distribution?.engaged?.count || 0) }}
                                </p>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Neytral</p>
                                <p class="text-2xl font-bold text-amber-500 dark:text-amber-400">
                                    {{ engagementData.distribution?.neutral?.count || 0 }}
                                </p>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Qiziqmagan</p>
                                <p class="text-2xl font-bold text-red-500 dark:text-red-400">
                                    {{ engagementData.distribution?.disengaged?.count || 0 }}
                                </p>
                            </div>
                        </div>

                        <!-- Insights -->
                        <div v-if="engagementInsights.length > 0" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <LightBulbIcon class="w-5 h-5 text-amber-500" />
                                Xulosa va tavsiyalar
                            </h3>
                            <div class="space-y-3">
                                <div
                                    v-for="(insight, idx) in engagementInsights"
                                    :key="idx"
                                    :class="[
                                        'p-4 rounded-lg border',
                                        insight.type === 'success' ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/30' :
                                        insight.type === 'danger' ? 'bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/30' :
                                        'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/30'
                                    ]"
                                >
                                    <h4 :class="[
                                        'font-medium',
                                        insight.type === 'success' ? 'text-emerald-700 dark:text-emerald-400' :
                                        insight.type === 'danger' ? 'text-red-700 dark:text-red-400' :
                                        'text-amber-700 dark:text-amber-400'
                                    ]">
                                        {{ insight.title }}
                                    </h4>
                                    <p class="text-gray-600 dark:text-gray-300 text-sm mt-1">{{ insight.message }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Component Scores -->
                        <div v-if="engagementData.components" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Komponent bo'yicha o'rtacha balllar
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div
                                    v-for="(score, key) in engagementData.components"
                                    :key="key"
                                    class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ getComponentLabel(key) }}</span>
                                        <span :class="['text-lg font-bold', getScoreColor(score)]">
                                            {{ score }}
                                        </span>
                                    </div>
                                    <div class="bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                        <div
                                            :class="['h-2 rounded-full transition-all duration-500', getScoreBarColor(score)]"
                                            :style="{ width: `${score}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Employee List -->
                        <div v-if="engagementData.employees?.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="font-semibold text-gray-900 dark:text-white">Hodimlar ro'yxati</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Hodim</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ball</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Daraja</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="emp in engagementData.employees" :key="emp.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ emp.name || 'Noma\'lum' }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span :class="['text-lg font-bold', getScoreColor(emp.overall_score)]">
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

                <!-- Tab Content: Flight Risk -->
                <div v-else-if="activeTab === 'flightrisk'" class="space-y-6">
                    <!-- Loading -->
                    <div v-if="loadingFlightRisk" class="flex items-center justify-center py-12">
                        <ArrowPathIcon class="w-8 h-8 animate-spin text-purple-600" />
                    </div>

                    <!-- No Data -->
                    <div v-else-if="!flightRiskData || survey.response_count === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700">
                        <ExclamationTriangleIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Ma'lumot yo'q</h3>
                        <p class="text-gray-500 dark:text-gray-400">
                            Bu so'rovnomaga javoblar kelgandan so'ng ketish xavfi tahlili ko'rinadi
                        </p>
                    </div>

                    <!-- Flight Risk Content -->
                    <template v-else>
                        <!-- Statistics Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ flightRiskData.total_employees || 0 }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Jami hodimlar</p>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border-l-4 border-red-500">
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-red-500 dark:text-red-400">
                                        {{ flightRiskData.risk_distribution?.critical?.count || 0 }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Juda yuqori</p>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border-l-4 border-orange-500">
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-orange-500 dark:text-orange-400">
                                        {{ flightRiskData.risk_distribution?.high?.count || 0 }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Yuqori</p>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border-l-4 border-yellow-500">
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-yellow-500 dark:text-yellow-400">
                                        {{ flightRiskData.risk_distribution?.medium?.count || 0 }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha</p>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border-l-4 border-green-500">
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-green-500 dark:text-green-400">
                                        {{ flightRiskData.risk_distribution?.low?.count || 0 }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Past</p>
                                </div>
                            </div>
                        </div>

                        <!-- Insights -->
                        <div v-if="flightRiskInsights.length > 0" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <LightBulbIcon class="w-5 h-5 text-amber-500" />
                                Xulosa va Tavsiyalar
                            </h3>
                            <div class="space-y-3">
                                <div
                                    v-for="(insight, idx) in flightRiskInsights"
                                    :key="idx"
                                    :class="[
                                        'p-4 rounded-lg border',
                                        insight.type === 'success' ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' :
                                        insight.type === 'danger' ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' :
                                        'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800'
                                    ]"
                                >
                                    <h4 :class="[
                                        'font-medium',
                                        insight.type === 'success' ? 'text-green-800 dark:text-green-300' :
                                        insight.type === 'danger' ? 'text-red-800 dark:text-red-300' :
                                        'text-amber-800 dark:text-amber-300'
                                    ]">
                                        {{ insight.title }}
                                    </h4>
                                    <p :class="[
                                        'text-sm mt-1',
                                        insight.type === 'success' ? 'text-green-700 dark:text-green-400' :
                                        insight.type === 'danger' ? 'text-red-700 dark:text-red-400' :
                                        'text-amber-700 dark:text-amber-400'
                                    ]">
                                        {{ insight.message }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Employee List -->
                        <div v-if="flightRiskData.employees?.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border border-gray-200 dark:border-gray-700">
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
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ emp.name || 'Noma\'lum' }}</span>
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
            </template>
        </div>
    </HRLayout>
</template>
