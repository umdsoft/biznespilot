<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import {
    ExclamationTriangleIcon,
    UserMinusIcon,
    ArrowPathIcon,
    FunnelIcon,
    ShieldCheckIcon,
    PlusIcon,
    LightBulbIcon,
    InformationCircleIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    currentBusiness: Object,
});

const loading = ref(true);
const risks = ref([]);
const statistics = ref(null);
const selectedLevel = ref('');
const showMitigationModal = ref(false);
const selectedEmployee = ref(null);
const newMitigation = ref('');

const levelOptions = [
    { value: '', label: 'Barcha darajalar' },
    { value: 'critical', label: 'Juda yuqori xavf' },
    { value: 'high', label: 'Yuqori xavf' },
    { value: 'moderate', label: "O'rtacha xavf" },
    { value: 'low', label: 'Past xavf' },
];

// Risk level labels in Uzbek
const getLevelLabel = (level) => {
    const labels = {
        critical: 'Juda yuqori xavf',
        high: 'Yuqori xavf',
        moderate: "O'rtacha xavf",
        medium: "O'rtacha xavf",
        low: 'Past xavf',
    };
    return labels[level] || level;
};

// Insights based on statistics
const insights = computed(() => {
    if (!statistics.value) return [];

    const results = [];
    const dist = statistics.value.distribution || {};
    const factorAnalysis = statistics.value.factor_analysis || [];
    const avgScore = statistics.value.overview?.avg_risk_score || 0;

    // Total at-risk employees
    const criticalCount = dist.critical?.count || 0;
    const highCount = dist.high?.count || 0;
    const atRiskCount = criticalCount + highCount;

    if (atRiskCount > 0) {
        results.push({
            type: 'warning',
            title: 'Yuqori xavfdagi hodimlar',
            message: `${atRiskCount} ta hodim yuqori yoki juda yuqori ketish xavfida. Ular bilan shaxsiy suhbat o'tkazing va sabablarini aniqlang.`,
            icon: 'exclamation',
        });
    }

    // Average score analysis
    if (avgScore >= 60) {
        results.push({
            type: 'danger',
            title: "Umumiy xavf darajasi yuqori",
            message: `O'rtacha xavf balli ${avgScore}%. Kompaniyada umumiy muammo bo'lishi mumkin - ish sharoitlari, kompensatsiya yoki boshqaruv sifatini tekshiring.`,
            icon: 'alert',
        });
    } else if (avgScore >= 40) {
        results.push({
            type: 'warning',
            title: "O'rtacha xavf darajasi",
            message: `O'rtacha xavf balli ${avgScore}%. Proaktiv choralar ko'rish tavsiya etiladi.`,
            icon: 'info',
        });
    } else {
        results.push({
            type: 'success',
            title: 'Xavf darajasi nazorat ostida',
            message: `O'rtacha xavf balli ${avgScore}%. Hodimlar odatda kompaniyada qolishga tayyor.`,
            icon: 'check',
        });
    }

    // Top risk factors
    const highImpactFactors = factorAnalysis.filter(f => f.avg_score >= 50);
    if (highImpactFactors.length > 0) {
        const factorNames = highImpactFactors.map(f => f.label).join(', ');
        results.push({
            type: 'info',
            title: 'Asosiy xavf omillari',
            message: `Eng ko'p ta'sir qiluvchi omillar: ${factorNames}. Bu sohalarga e'tibor qarating.`,
            icon: 'lightbulb',
        });
    }

    // Recommendations based on factors
    if (factorAnalysis.length > 0) {
        const topFactor = factorAnalysis[0];
        const recommendation = getRecommendation(topFactor.key, topFactor.avg_score);
        if (recommendation) {
            results.push({
                type: 'tip',
                title: 'Tavsiya',
                message: recommendation,
                icon: 'lightbulb',
            });
        }
    }

    return results;
});

const getRecommendation = (factorKey, score) => {
    if (score < 40) return null;

    const recommendations = {
        engagement: "Hodimlar ishga qiziqishi past. Gallup Q12 so'rovnomasini o'tkazing va natijalarni tahlil qiling. Regular 1:1 uchrashuvlar o'tkazing.",
        tenure: "Yangi hodimlar ko'p ketmoqda. Onboarding jarayonini yaxshilang va birinchi 90 kun davomida ko'proq e'tibor bering.",
        compensation: "Kompensatsiya bilan bog'liq muammolar bor. Bozor narxlarini o'rganing va raqobatbardosh taklif qiling.",
        growth: "Hodimlar o'sish imkoniyatlarini ko'rmayapti. Aniq martaba yo'llarini belgilang va o'quv dasturlarini taklif qiling.",
        workload: "Ish yuki juda og'ir. Vazifalarni qayta taqsimlang va qo'shimcha resurslar yollashni ko'rib chiqing.",
        recognition: "Hodimlar tan olinmayapti. Muvaffaqiyatlarni nishonlash va tashakkur bildirish madaniyatini joriy qiling.",
    };

    return recommendations[factorKey] || null;
};

const fetchData = async () => {
    if (!props.currentBusiness?.id) return;

    loading.value = true;
    try {
        const [risksRes, statsRes] = await Promise.all([
            axios.get(`/api/v1/businesses/${props.currentBusiness.id}/hr/flight-risk`, {
                params: { risk_level: selectedLevel.value || undefined }
            }),
            axios.get(`/api/v1/businesses/${props.currentBusiness.id}/hr/flight-risk/statistics`)
        ]);

        risks.value = risksRes.data.data?.data || [];
        statistics.value = statsRes.data.data;
    } catch (error) {
        console.error('Error fetching flight risk data:', error);
    } finally {
        loading.value = false;
    }
};

const recalculateRisk = async (userId) => {
    if (!props.currentBusiness?.id) return;

    try {
        await axios.post(`/api/v1/businesses/${props.currentBusiness.id}/hr/flight-risk/${userId}/recalculate`);
        fetchData();
    } catch (error) {
        console.error('Error recalculating risk:', error);
    }
};

const openMitigationModal = (risk) => {
    selectedEmployee.value = risk;
    showMitigationModal.value = true;
};

const addMitigation = async () => {
    if (!props.currentBusiness?.id || !selectedEmployee.value || !newMitigation.value.trim()) return;

    try {
        await axios.post(`/api/v1/businesses/${props.currentBusiness.id}/hr/flight-risk/${selectedEmployee.value.user?.id}/mitigation`, {
            action: newMitigation.value
        });
        showMitigationModal.value = false;
        newMitigation.value = '';
        fetchData();
    } catch (error) {
        console.error('Error adding mitigation:', error);
    }
};

const getLevelColor = (level) => {
    const colors = {
        critical: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
        high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300',
        moderate: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
        medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
        low: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
    };
    return colors[level] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const getScoreColor = (score) => {
    if (score >= 70) return 'text-red-500 dark:text-red-400';
    if (score >= 50) return 'text-orange-500 dark:text-orange-400';
    if (score >= 30) return 'text-yellow-500 dark:text-yellow-400';
    return 'text-green-500 dark:text-green-400';
};

const getInsightBgColor = (type) => {
    const colors = {
        danger: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
        warning: 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800',
        success: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
        info: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
        tip: 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800',
    };
    return colors[type] || 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700';
};

const getInsightTextColor = (type) => {
    const colors = {
        danger: 'text-red-800 dark:text-red-300',
        warning: 'text-amber-800 dark:text-amber-300',
        success: 'text-green-800 dark:text-green-300',
        info: 'text-blue-800 dark:text-blue-300',
        tip: 'text-purple-800 dark:text-purple-300',
    };
    return colors[type] || 'text-gray-800 dark:text-gray-300';
};

const getInsightIconColor = (type) => {
    const colors = {
        danger: 'text-red-500 dark:text-red-400',
        warning: 'text-amber-500 dark:text-amber-400',
        success: 'text-green-500 dark:text-green-400',
        info: 'text-blue-500 dark:text-blue-400',
        tip: 'text-purple-500 dark:text-purple-400',
    };
    return colors[type] || 'text-gray-500 dark:text-gray-400';
};

onMounted(fetchData);
</script>

<template>
    <HRLayout title="Flight Risk">
        <Head title="Ketish Xavfi" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Ketish Xavfi Tahlili
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Hodimlarning ketish xavfini kuzatish va proaktiv choralar ko'rish
                    </p>
                </div>
                <button
                    @click="fetchData"
                    :disabled="loading"
                    class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50"
                >
                    <ArrowPathIcon :class="['w-5 h-5', loading ? 'animate-spin' : '']" />
                    Yangilash
                </button>
            </div>

            <!-- Statistics Cards -->
            <div v-if="statistics" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center mb-3">
                            <UserMinusIcon class="w-6 h-6 text-gray-600 dark:text-gray-300" />
                        </div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ statistics.overview?.total_employees || 0 }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Jami hodimlar</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border-l-4 border-red-500">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-red-500 dark:text-red-400">
                            {{ statistics.distribution?.critical?.count || 0 }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Juda yuqori xavf</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border-l-4 border-orange-500">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-orange-500 dark:text-orange-400">
                            {{ statistics.distribution?.high?.count || 0 }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Yuqori xavf</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border-l-4 border-yellow-500">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-yellow-500 dark:text-yellow-400">
                            {{ statistics.distribution?.moderate?.count || 0 }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha xavf</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border-l-4 border-green-500">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-green-500 dark:text-green-400">
                            {{ statistics.distribution?.low?.count || 0 }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Past xavf</p>
                    </div>
                </div>
            </div>

            <!-- Insights Section -->
            <div v-if="insights.length > 0" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <LightBulbIcon class="w-5 h-5 text-amber-500" />
                    Xulosa va Tavsiyalar
                </h3>
                <div class="space-y-3">
                    <div
                        v-for="(insight, idx) in insights"
                        :key="idx"
                        :class="['p-4 rounded-lg border', getInsightBgColor(insight.type)]"
                    >
                        <h4 :class="['font-medium mb-1', getInsightTextColor(insight.type)]">
                            {{ insight.title }}
                        </h4>
                        <p :class="['text-sm', getInsightTextColor(insight.type)]">
                            {{ insight.message }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Factor Analysis -->
            <div v-if="statistics?.factor_analysis" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Xavf omillari tahlili
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div
                        v-for="factor in statistics.factor_analysis"
                        :key="factor.key"
                        class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg text-center border border-gray-200 dark:border-gray-600"
                    >
                        <div :class="['text-2xl font-bold', getScoreColor(factor.avg_score)]">
                            {{ factor.avg_score }}%
                        </div>
                        <div class="text-sm text-gray-700 dark:text-gray-300 mt-1 font-medium">{{ factor.label }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ factor.impact }}</div>
                    </div>
                </div>
            </div>

            <!-- Flight Risk Information -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <InformationCircleIcon class="w-5 h-5 text-blue-500" />
                    Flight Risk haqida
                </h3>
                <div class="prose dark:prose-invert max-w-none text-sm">
                    <p class="text-gray-700 dark:text-gray-300 mb-3">
                        <strong>Flight Risk</strong> - bu hodimning kompaniyani tark etish ehtimolini baholash tizimi.
                        Bu ko'rsatkich bir nechta omillar asosida hisoblanadi va HR jamoasiga proaktiv choralar ko'rishga yordam beradi.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Xavf darajalari:</h4>
                            <ul class="space-y-1 text-gray-600 dark:text-gray-400">
                                <li><span class="text-red-500 font-medium">Juda yuqori (70%+):</span> Tezkor aralashuv zarur</li>
                                <li><span class="text-orange-500 font-medium">Yuqori (50-70%):</span> Diqqat talab qiladi</li>
                                <li><span class="text-yellow-500 font-medium">O'rtacha (30-50%):</span> Kuzatish ostida</li>
                                <li><span class="text-green-500 font-medium">Past (0-30%):</span> Barqaror</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Asosiy omillar:</h4>
                            <ul class="space-y-1 text-gray-600 dark:text-gray-400">
                                <li><strong>Ishga qiziqish:</strong> Ishga qiziqish darajasi (so'rovnoma asosida)</li>
                                <li><strong>Tenure:</strong> Kompaniyada ishlash muddati</li>
                                <li><strong>Growth:</strong> Martaba o'sish imkoniyatlari</li>
                                <li><strong>Recognition:</strong> Muvaffaqiyatlarni tan olish</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <FunnelIcon class="w-5 h-5 text-gray-400 dark:text-gray-500" />
                    <select
                        v-model="selectedLevel"
                        @change="fetchData"
                        class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white"
                    >
                        <option v-for="opt in levelOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Employees Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Hodim</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Xavf balli</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Daraja</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Asosiy omillar</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="risk in risks"
                                :key="risk.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/50 rounded-full flex items-center justify-center">
                                            <span class="text-sm font-medium text-purple-600 dark:text-purple-300">
                                                {{ risk.user?.name?.charAt(0) || '?' }}
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ risk.user?.name || 'Noma\'lum' }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ risk.user?.email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span :class="['text-lg font-bold', getScoreColor(risk.risk_score)]">
                                            {{ risk.risk_score }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-3 py-1 text-xs font-medium rounded-full', getLevelColor(risk.risk_level)]">
                                        {{ getLevelLabel(risk.risk_level) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="(factor, idx) in risk.top_risk_factors"
                                            :key="idx"
                                            class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded"
                                        >
                                            {{ factor }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openMitigationModal(risk)"
                                            class="p-2 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg"
                                            title="Chora-tadbir qo'shish"
                                        >
                                            <ShieldCheckIcon class="w-5 h-5" />
                                        </button>
                                        <button
                                            @click="recalculateRisk(risk.user?.id)"
                                            class="p-2 text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/30 rounded-lg"
                                            title="Qayta hisoblash"
                                        >
                                            <ArrowPathIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="loading" class="p-8 text-center">
                    <ArrowPathIcon class="w-8 h-8 mx-auto animate-spin text-purple-500 dark:text-purple-400" />
                    <p class="mt-2 text-gray-500 dark:text-gray-400">Yuklanmoqda...</p>
                </div>

                <div v-else-if="risks.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <ExclamationTriangleIcon class="w-12 h-12 mx-auto opacity-50 mb-3" />
                    <p>Flight risk ma'lumotlari topilmadi</p>
                </div>
            </div>
        </div>

        <!-- Mitigation Modal -->
        <div v-if="showMitigationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md mx-4 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Chora-tadbir qo'shish
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                    <span class="font-medium">{{ selectedEmployee?.user?.name }}</span> uchun
                </p>
                <textarea
                    v-model="newMitigation"
                    rows="3"
                    placeholder="Chora-tadbir tavsifi..."
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                ></textarea>
                <div class="flex justify-end gap-3 mt-4">
                    <button
                        @click="showMitigationModal = false"
                        class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    >
                        Bekor qilish
                    </button>
                    <button
                        @click="addMitigation"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                    >
                        Qo'shish
                    </button>
                </div>
            </div>
        </div>
    </HRLayout>
</template>
