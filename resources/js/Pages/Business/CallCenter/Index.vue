<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import {
    SparklesIcon,
    PhoneIcon,
    UserGroupIcon,
    ChartBarIcon,
    ArrowPathIcon,
    TrophyIcon,
    ExclamationTriangleIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    XMarkIcon,
    PlayIcon,
    DocumentTextIcon,
    ChatBubbleLeftRightIcon,
    CalendarIcon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon } from '@heroicons/vue/24/solid';
import axios from 'axios';

// State
const loading = ref(true);
const overview = ref(null);
const leaderboard = ref([]);
const period = ref('monthly');
const error = ref(null);

// Detail modal state
const showDetailModal = ref(false);
const selectedOperator = ref(null);
const operatorHistory = ref([]);
const loadingHistory = ref(false);
const selectedCall = ref(null);

// Fetch overview
const fetchOverview = async () => {
    try {
        const response = await axios.get('/api/v1/call-center/overview', {
            params: { period: period.value }
        });
        overview.value = response.data.data;
    } catch (err) {
        console.error('Failed to fetch overview:', err);
        error.value = 'Ma\'lumotlarni yuklashda xatolik';
    }
};

// Fetch leaderboard
const fetchLeaderboard = async () => {
    try {
        const response = await axios.get('/api/v1/call-center/leaderboard', {
            params: { period: period.value, limit: 10 }
        });
        leaderboard.value = response.data.data;
    } catch (err) {
        console.error('Failed to fetch leaderboard:', err);
    }
};

// Refresh data
const refresh = async () => {
    loading.value = true;
    error.value = null;
    await Promise.all([fetchOverview(), fetchLeaderboard()]);
    loading.value = false;
};

// Change period
const changePeriod = async (newPeriod) => {
    period.value = newPeriod;
    await refresh();
};

// Open operator detail
const openOperatorDetail = async (operator) => {
    selectedOperator.value = operator;
    showDetailModal.value = true;
    loadingHistory.value = true;
    selectedCall.value = null;

    try {
        const response = await axios.get(`/api/v1/call-center/operators/${operator.operator.id}/history`, {
            params: { limit: 50 }
        });
        operatorHistory.value = response.data.data;
    } catch (err) {
        console.error('Failed to fetch operator history:', err);
        operatorHistory.value = [];
    } finally {
        loadingHistory.value = false;
    }
};

// Close modal
const closeModal = () => {
    showDetailModal.value = false;
    selectedOperator.value = null;
    operatorHistory.value = [];
    selectedCall.value = null;
};

// Select call for detail view
const selectCall = (call) => {
    selectedCall.value = selectedCall.value?.id === call.id ? null : call;
};

// Get score color class
const getScoreColor = (score) => {
    if (score === null || score === undefined) return 'text-gray-400 dark:text-gray-500';
    if (score >= 80) return 'text-emerald-500';
    if (score >= 60) return 'text-amber-500';
    if (score >= 40) return 'text-orange-500';
    return 'text-red-500';
};

// Get score gradient
const getScoreGradient = (score) => {
    if (score === null || score === undefined) return 'from-gray-400 to-gray-500';
    if (score >= 80) return 'from-emerald-400 to-emerald-600';
    if (score >= 60) return 'from-amber-400 to-amber-600';
    if (score >= 40) return 'from-orange-400 to-orange-600';
    return 'from-red-400 to-red-600';
};

// Get score bg
const getScoreBg = (score) => {
    if (score === null || score === undefined) return 'bg-gray-100 dark:bg-gray-700';
    if (score >= 80) return 'bg-emerald-100 dark:bg-emerald-900/30';
    if (score >= 60) return 'bg-amber-100 dark:bg-amber-900/30';
    if (score >= 40) return 'bg-orange-100 dark:bg-orange-900/30';
    return 'bg-red-100 dark:bg-red-900/30';
};

// Get rank style
const getRankStyle = (rank) => {
    if (rank === 1) return { bg: 'bg-gradient-to-br from-yellow-400 to-amber-500', text: 'text-white', shadow: 'shadow-amber-200 dark:shadow-amber-900/50' };
    if (rank === 2) return { bg: 'bg-gradient-to-br from-gray-300 to-gray-400 dark:from-gray-400 dark:to-gray-500', text: 'text-gray-700 dark:text-gray-900', shadow: 'shadow-gray-200 dark:shadow-gray-700' };
    if (rank === 3) return { bg: 'bg-gradient-to-br from-amber-600 to-amber-700', text: 'text-white', shadow: 'shadow-amber-200 dark:shadow-amber-900/50' };
    return { bg: 'bg-gray-100 dark:bg-gray-700', text: 'text-gray-600 dark:text-gray-300', shadow: '' };
};

// Get initials
const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

// Period labels
const periodLabels = {
    daily: 'Bugun',
    weekly: 'Bu hafta',
    monthly: 'Bu oy',
};

// Anti-pattern labels (O'zbek tilida)
const antiPatternLabels = {
    'incomplete_call': 'Tugallanmagan suhbat',
    'no_greeting': 'Salomsiz boshlash',
    'no_closing': 'Yakunsiz tugatish',
    'interrupting': 'Gap kesish',
    'rushed': 'Shoshilinch gapirish',
    'no_discovery': 'Ehtiyoj aniqlanmagan',
    'no_objection_handling': 'E\'tirozga javob yo\'q',
    'no_cta': 'Harakatga chaqiruv yo\'q',
};

// Stage labels
const stageLabels = {
    greeting: 'Salomlashuv',
    discovery: 'Ehtiyoj aniqlash',
    presentation: 'Taqdimot',
    objection_handling: 'E\'tirozlarga javob',
    closing: 'Sotuvni yakunlash',
    rapport: 'Munosabat',
    cta: 'Harakatga chaqiruv',
};

onMounted(() => {
    refresh();
});
</script>

<template>
    <Head title="Qo'ng'iroq Tahlili" />

    <BusinessLayout title="Qo'ng'iroq Tahlili">
        <div class="space-y-6">
            <!-- Hero Header -->
            <div class="relative overflow-hidden bg-gradient-to-r from-violet-600 via-purple-600 to-indigo-600 rounded-2xl p-6 md:p-8">
                <!-- Background Pattern -->
                <div class="absolute inset-0 opacity-10">
                    <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <defs>
                            <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                                <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                            </pattern>
                        </defs>
                        <rect width="100" height="100" fill="url(#grid)"/>
                    </svg>
                </div>

                <!-- Floating Icons -->
                <div class="absolute top-4 right-4 opacity-20">
                    <SparklesIcon class="w-24 h-24 text-white" />
                </div>

                <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                            <SparklesIcon class="w-8 h-8 text-white" />
                        </div>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-white">Qo'ng'iroq Tahlili</h1>
                            <p class="text-purple-100 mt-1">AI yordamida operatorlar samaradorligini real vaqtda kuzating</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Period selector -->
                        <div class="flex bg-white/10 backdrop-blur-sm rounded-xl p-1">
                            <button
                                v-for="(label, key) in periodLabels"
                                :key="key"
                                @click="changePeriod(key)"
                                :class="[
                                    'px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200',
                                    period === key
                                        ? 'bg-white text-purple-600 shadow-lg'
                                        : 'text-white/80 hover:text-white hover:bg-white/10'
                                ]"
                            >
                                {{ label }}
                            </button>
                        </div>

                        <!-- Refresh button -->
                        <button
                            @click="refresh"
                            :disabled="loading"
                            class="p-2.5 bg-white/10 backdrop-blur-sm hover:bg-white/20 text-white rounded-xl transition-all duration-200"
                        >
                            <ArrowPathIcon class="w-5 h-5" :class="{ 'animate-spin': loading }" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Error state -->
            <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                <div class="flex items-center gap-3 text-red-600 dark:text-red-400">
                    <XCircleIcon class="w-6 h-6" />
                    <span class="font-medium">{{ error }}</span>
                </div>
            </div>

            <!-- Loading state -->
            <div v-else-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="i in 4" :key="i" class="bg-white dark:bg-gray-800 rounded-2xl p-6 animate-pulse">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
                    </div>
                    <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-20 mb-2"></div>
                    <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded w-32"></div>
                </div>
            </div>

            <!-- Overview Stats -->
            <div v-else-if="overview" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Calls -->
                <div class="group bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-lg hover:border-blue-200 dark:hover:border-blue-800 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg shadow-blue-200 dark:shadow-blue-900/50">
                            <PhoneIcon class="w-6 h-6 text-white" />
                        </div>
                        <span class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Jami</span>
                    </div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ overview.summary?.total_calls || 0 }}</div>
                    <div class="flex items-center gap-2 mt-2">
                        <CheckCircleIcon class="w-4 h-4 text-emerald-500" />
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ overview.summary?.analyzed_calls || 0 }} ta tahlil qilingan</span>
                    </div>
                </div>

                <!-- Average Score -->
                <div class="group bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-lg hover:border-purple-200 dark:hover:border-purple-800 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg shadow-purple-200 dark:shadow-purple-900/50">
                            <ChartBarIcon class="w-6 h-6 text-white" />
                        </div>
                        <span class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">O'rtacha</span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-bold" :class="getScoreColor(overview.summary?.avg_score)">
                            {{ overview.summary?.avg_score !== null ? Math.round(overview.summary.avg_score) : '-' }}
                        </span>
                        <span class="text-lg text-gray-400 dark:text-gray-500">/100</span>
                    </div>
                    <div class="mt-3 h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full bg-gradient-to-r transition-all duration-500"
                            :class="getScoreGradient(overview.summary?.avg_score)"
                            :style="{ width: `${overview.summary?.avg_score || 0}%` }"
                        ></div>
                    </div>
                </div>

                <!-- Operators -->
                <div class="group bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-lg hover:border-emerald-200 dark:hover:border-emerald-800 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow-lg shadow-emerald-200 dark:shadow-emerald-900/50">
                            <UserGroupIcon class="w-6 h-6 text-white" />
                        </div>
                        <span class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Jamoa</span>
                    </div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ overview.summary?.operator_count || 0 }}</div>
                    <div class="flex items-center gap-2 mt-2">
                        <div class="flex -space-x-2">
                            <div v-for="i in Math.min(overview.summary?.operator_count || 0, 3)" :key="i"
                                class="w-6 h-6 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 border-2 border-white dark:border-gray-800 flex items-center justify-center">
                                <span class="text-[10px] font-bold text-white">{{ i }}</span>
                            </div>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">faol operatorlar</span>
                    </div>
                </div>

                <!-- Success Rate -->
                <div class="group bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-lg hover:border-amber-200 dark:hover:border-amber-800 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow-lg shadow-amber-200 dark:shadow-amber-900/50">
                            <TrophyIcon class="w-6 h-6 text-white" />
                        </div>
                        <span class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Muvaffaqiyat</span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ overview.summary?.total_calls > 0
                                ? Math.round((overview.summary.successful_calls / overview.summary.total_calls) * 100)
                                : 0 }}
                        </span>
                        <span class="text-lg text-gray-400 dark:text-gray-500">%</span>
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                        <CheckCircleIcon class="w-4 h-4 text-emerald-500" />
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ overview.summary?.successful_calls || 0 }} ta muvaffaqiyatli</span>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Leaderboard -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-gradient-to-br from-amber-400 to-amber-500 rounded-lg shadow-md">
                                <TrophyIcon class="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Operatorlar Reytingi</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Batafsil ko'rish uchun bosing</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="leaderboard.length === 0" class="p-12 text-center">
                        <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                            <UserGroupIcon class="w-10 h-10 text-gray-300 dark:text-gray-500" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Hali ma'lumot yo'q</h3>
                        <p class="text-gray-500 dark:text-gray-400">Tahlil qilingan qo'ng'iroqlar paydo bo'lganda reyting ko'rsatiladi</p>
                    </div>

                    <div v-else class="divide-y divide-gray-50 dark:divide-gray-700/50">
                        <div
                            v-for="(operator, index) in leaderboard"
                            :key="operator.operator.id"
                            @click="openOperatorDetail(operator)"
                            class="px-6 py-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer group"
                            :class="{ 'bg-gradient-to-r from-amber-50/50 to-transparent dark:from-amber-900/10': operator.rank <= 3 }"
                        >
                            <!-- Rank Badge -->
                            <div
                                class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold shadow-md"
                                :class="[getRankStyle(operator.rank).bg, getRankStyle(operator.rank).text, getRankStyle(operator.rank).shadow]"
                            >
                                <StarIcon v-if="operator.rank === 1" class="w-5 h-5" />
                                <span v-else>{{ operator.rank }}</span>
                            </div>

                            <!-- Avatar -->
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-400 to-purple-500 flex items-center justify-center shadow-md">
                                <span class="text-white font-bold">{{ getInitials(operator.operator.name) }}</span>
                            </div>

                            <!-- Operator Info -->
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-gray-900 dark:text-white truncate">
                                    {{ operator.operator.name }}
                                </div>
                                <div class="flex items-center gap-3 mt-1">
                                    <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                        <PhoneIcon class="w-3.5 h-3.5" />
                                        {{ operator.total_calls }} qo'ng'iroq
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400">
                                        <CheckCircleIcon class="w-3.5 h-3.5" />
                                        {{ operator.analyzed_calls }} tahlil
                                    </span>
                                </div>
                            </div>

                            <!-- Score -->
                            <div class="text-right">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="text-2xl font-bold"
                                        :class="getScoreColor(operator.avg_score)"
                                    >
                                        {{ operator.avg_score !== null ? Math.round(operator.avg_score) : '-' }}
                                    </div>
                                </div>
                                <div
                                    v-if="operator.score_change !== null && operator.score_change !== 0"
                                    class="flex items-center justify-end gap-1 text-xs font-medium mt-1"
                                    :class="operator.score_change >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'"
                                >
                                    <ArrowTrendingUpIcon v-if="operator.score_change >= 0" class="w-3.5 h-3.5" />
                                    <ArrowTrendingDownIcon v-else class="w-3.5 h-3.5" />
                                    {{ operator.score_change >= 0 ? '+' : '' }}{{ operator.score_change.toFixed(1) }}
                                </div>
                            </div>

                            <!-- Arrow -->
                            <ChevronRightIcon class="w-5 h-5 text-gray-300 dark:text-gray-600 group-hover:text-gray-500 dark:group-hover:text-gray-400 transition-colors" />
                        </div>
                    </div>
                </div>

                <!-- Common Issues -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-gradient-to-br from-orange-400 to-red-500 rounded-lg shadow-md">
                                <ExclamationTriangleIcon class="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Xatolar Tahlili</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Tez-tez uchraydigan muammolar</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="!overview?.common_issues || Object.keys(overview.common_issues).length === 0" class="p-12 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center">
                            <CheckCircleIcon class="w-8 h-8 text-emerald-500" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Ajoyib!</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Xatolar aniqlanmagan</p>
                    </div>

                    <div v-else class="p-4 space-y-3">
                        <div
                            v-for="(count, type) in overview.common_issues"
                            :key="type"
                            class="group relative overflow-hidden"
                        >
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl border border-orange-100 dark:border-orange-800/50 hover:border-orange-200 dark:hover:border-orange-700 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900/50 rounded-lg flex items-center justify-center">
                                        <ExclamationTriangleIcon class="w-4 h-4 text-orange-600 dark:text-orange-400" />
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ antiPatternLabels[type] || type }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ count }}</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">marta</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Banner -->
            <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-6">
                <div class="absolute inset-0 opacity-10">
                    <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <defs>
                            <pattern id="dots" width="10" height="10" patternUnits="userSpaceOnUse">
                                <circle cx="2" cy="2" r="1" fill="white"/>
                            </pattern>
                        </defs>
                        <rect width="100" height="100" fill="url(#dots)"/>
                    </svg>
                </div>
                <div class="relative flex items-start gap-4">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <SparklesIcon class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h3 class="font-bold text-white text-lg">AI qo'ng'iroq tahlili qanday ishlaydi?</h3>
                        <p class="text-blue-100 mt-2 leading-relaxed">
                            Lead sahifalarida qo'ng'iroqlarni tanlab <strong class="text-white">"AI Tahlil"</strong> tugmasini bosing.
                            Tizim qo'ng'iroqni avtomatik transkript qiladi va <strong class="text-white">7 ta sotuv bosqichi</strong> bo'yicha baholaydi:
                            Salomlashuv, Ehtiyoj aniqlash, Taqdimot, E'tirozlarga javob, Sotuvni yakunlash, Munosabat va Harakatga chaqiruv.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Operator Detail Modal -->
        <Teleport to="body">
            <div
                v-if="showDetailModal"
                class="fixed inset-0 z-50 overflow-y-auto"
                @click.self="closeModal"
            >
                <div class="flex min-h-full items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal"></div>

                    <!-- Modal -->
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden">
                        <!-- Modal Header -->
                        <div class="sticky top-0 z-10 bg-gradient-to-r from-violet-600 to-purple-600 px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                                        <span class="text-white text-xl font-bold">{{ getInitials(selectedOperator?.operator?.name) }}</span>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold text-white">{{ selectedOperator?.operator?.name }}</h2>
                                        <p class="text-purple-100">
                                            {{ selectedOperator?.analyzed_calls }} ta tahlil qilingan qo'ng'iroq
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <div class="text-3xl font-bold text-white">{{ selectedOperator?.avg_score !== null ? Math.round(selectedOperator.avg_score) : '-' }}</div>
                                        <div class="text-purple-100 text-sm">O'rtacha ball</div>
                                    </div>
                                    <button
                                        @click="closeModal"
                                        class="p-2 hover:bg-white/20 rounded-lg transition-colors"
                                    >
                                        <XMarkIcon class="w-6 h-6 text-white" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Content -->
                        <div class="p-6 overflow-y-auto max-h-[calc(90vh-100px)]">
                            <!-- Loading -->
                            <div v-if="loadingHistory" class="flex items-center justify-center py-12">
                                <ArrowPathIcon class="w-8 h-8 text-purple-500 animate-spin" />
                            </div>

                            <!-- Empty -->
                            <div v-else-if="operatorHistory.length === 0" class="text-center py-12">
                                <DocumentTextIcon class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Tahlil topilmadi</h3>
                                <p class="text-gray-500 dark:text-gray-400 mt-1">Bu operator uchun hali tahlil qilingan qo'ng'iroqlar yo'q</p>
                            </div>

                            <!-- History List -->
                            <div v-else class="space-y-4">
                                <div
                                    v-for="call in operatorHistory"
                                    :key="call.id"
                                    class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden"
                                >
                                    <!-- Call Header -->
                                    <div
                                        @click="selectCall(call)"
                                        class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                    >
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" :class="getScoreBg(call.analysis?.overall_score)">
                                            <span class="text-lg font-bold" :class="getScoreColor(call.analysis?.overall_score)">
                                                {{ call.analysis?.overall_score !== null ? Math.round(call.analysis.overall_score) : '-' }}
                                            </span>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <CalendarIcon class="w-4 h-4 text-gray-400" />
                                                <span class="font-medium text-gray-900 dark:text-white">{{ call.date }}</span>
                                                <span class="text-gray-400 dark:text-gray-500">•</span>
                                                <ClockIcon class="w-4 h-4 text-gray-400" />
                                                <span class="text-gray-600 dark:text-gray-300">{{ call.duration }}</span>
                                            </div>
                                            <div v-if="call.lead" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                {{ call.lead.name }} - {{ call.lead.phone }}
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <span
                                                class="px-3 py-1 text-xs font-medium rounded-full"
                                                :class="getScoreBg(call.analysis?.overall_score)"
                                            >
                                                <span :class="getScoreColor(call.analysis?.overall_score)">{{ call.analysis?.score_label || 'Noma\'lum' }}</span>
                                            </span>
                                            <ChevronRightIcon
                                                class="w-5 h-5 text-gray-400 transition-transform"
                                                :class="{ 'rotate-90': selectedCall?.id === call.id }"
                                            />
                                        </div>
                                    </div>

                                    <!-- Call Detail (Expanded) -->
                                    <div v-if="selectedCall?.id === call.id" class="border-t border-gray-200 dark:border-gray-700">
                                        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-gray-200 dark:divide-gray-700">
                                            <!-- Left: Transcript -->
                                            <div class="p-4">
                                                <h4 class="flex items-center gap-2 font-semibold text-gray-900 dark:text-white mb-3">
                                                    <ChatBubbleLeftRightIcon class="w-5 h-5 text-purple-500" />
                                                    Transkript
                                                </h4>
                                                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 max-h-80 overflow-y-auto text-sm">
                                                    <div v-if="call.analysis?.formatted_transcript" class="whitespace-pre-wrap text-gray-700 dark:text-gray-300">
                                                        {{ call.analysis.formatted_transcript }}
                                                    </div>
                                                    <div v-else-if="call.analysis?.transcript" class="whitespace-pre-wrap text-gray-700 dark:text-gray-300">
                                                        {{ call.analysis.transcript }}
                                                    </div>
                                                    <div v-else class="text-gray-400 dark:text-gray-500 italic">
                                                        Transkript mavjud emas
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Right: Analysis -->
                                            <div class="p-4">
                                                <h4 class="flex items-center gap-2 font-semibold text-gray-900 dark:text-white mb-3">
                                                    <ChartBarIcon class="w-5 h-5 text-purple-500" />
                                                    Tahlil natijalari
                                                </h4>

                                                <!-- Stage Scores -->
                                                <div v-if="call.analysis?.stage_scores" class="space-y-2 mb-4">
                                                    <div
                                                        v-for="(score, stage) in call.analysis.stage_scores"
                                                        :key="stage"
                                                        class="flex items-center gap-3"
                                                    >
                                                        <span class="w-32 text-xs text-gray-600 dark:text-gray-400 truncate">{{ stageLabels[stage] || stage }}</span>
                                                        <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                            <div
                                                                class="h-full bg-gradient-to-r transition-all duration-300"
                                                                :class="getScoreGradient(score)"
                                                                :style="{ width: `${score || 0}%` }"
                                                            ></div>
                                                        </div>
                                                        <span class="w-8 text-xs font-medium" :class="getScoreColor(score)">{{ score || 0 }}</span>
                                                    </div>
                                                </div>

                                                <!-- Strengths -->
                                                <div v-if="call.analysis?.strengths?.length" class="mb-4">
                                                    <h5 class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-2">Kuchli tomonlar</h5>
                                                    <ul class="space-y-1">
                                                        <li v-for="(s, i) in call.analysis.strengths" :key="i" class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-300">
                                                            <CheckCircleIcon class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" />
                                                            {{ s }}
                                                        </li>
                                                    </ul>
                                                </div>

                                                <!-- Weaknesses -->
                                                <div v-if="call.analysis?.weaknesses?.length" class="mb-4">
                                                    <h5 class="text-xs font-semibold text-orange-600 dark:text-orange-400 uppercase tracking-wider mb-2">Yaxshilash kerak</h5>
                                                    <ul class="space-y-1">
                                                        <li v-for="(w, i) in call.analysis.weaknesses" :key="i" class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-300">
                                                            <ExclamationTriangleIcon class="w-4 h-4 text-orange-500 mt-0.5 flex-shrink-0" />
                                                            {{ w }}
                                                        </li>
                                                    </ul>
                                                </div>

                                                <!-- Recommendations -->
                                                <div v-if="call.analysis?.recommendations?.length">
                                                    <h5 class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-2">Tavsiyalar</h5>
                                                    <ul class="space-y-1">
                                                        <li v-for="(r, i) in call.analysis.recommendations" :key="i" class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-300">
                                                            <SparklesIcon class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" />
                                                            {{ r }}
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </BusinessLayout>
</template>
