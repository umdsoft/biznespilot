<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import {
    HeartIcon,
    UserIcon,
    ChartBarIcon,
    ArrowPathIcon,
    FunnelIcon,
    LightBulbIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    currentBusiness: Object,
});

const loading = ref(true);
const engagements = ref([]);
const statistics = ref(null);
const selectedLevel = ref('');

const levelOptions = [
    { value: '', label: 'Barcha darajalar' },
    { value: 'highly_engaged', label: 'Juda qiziqgan' },
    { value: 'engaged', label: 'Qiziqgan' },
    { value: 'neutral', label: 'Neytral' },
    { value: 'disengaged', label: 'Qiziqmagan' },
];

// Engagement level labellarni o'zbekchaga tarjima
const getLevelLabel = (level) => {
    const labels = {
        highly_engaged: 'Juda qiziqgan',
        engaged: 'Qiziqgan',
        neutral: 'Neytral',
        disengaged: 'Qiziqmagan',
    };
    return labels[level] || level;
};

const fetchData = async () => {
    if (!props.currentBusiness?.id) return;

    loading.value = true;
    try {
        const [engagementsRes, statsRes] = await Promise.all([
            axios.get(`/api/v1/businesses/${props.currentBusiness.id}/hr/engagement`, {
                params: { engagement_level: selectedLevel.value || undefined }
            }),
            axios.get(`/api/v1/businesses/${props.currentBusiness.id}/hr/engagement/statistics`)
        ]);

        engagements.value = engagementsRes.data.data?.data || [];
        statistics.value = statsRes.data.data;
    } catch (error) {
        console.error('Error fetching engagement data:', error);
    } finally {
        loading.value = false;
    }
};

const recalculateEngagement = async (userId) => {
    if (!props.currentBusiness?.id) return;

    try {
        await axios.post(`/api/v1/businesses/${props.currentBusiness.id}/hr/engagement/${userId}/recalculate`);
        fetchData();
    } catch (error) {
        console.error('Error recalculating engagement:', error);
    }
};

const getLevelColor = (level) => {
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

// Past balllar asosida xulosa va tavsiyalar
const insights = computed(() => {
    if (!statistics.value?.components) return [];

    const results = [];
    const components = statistics.value.components;

    // Eng past balllarni topish
    const sorted = [...components].sort((a, b) => a.score - b.score);
    const lowest = sorted.slice(0, 3).filter(c => c.score < 60);

    lowest.forEach(comp => {
        const insight = getInsightForComponent(comp.key, comp.score);
        if (insight) results.push(insight);
    });

    // Umumiy xulosa
    const avgScore = statistics.value.overview?.avg_score || 0;
    if (avgScore >= 80) {
        results.unshift({
            type: 'success',
            title: "A'lo natija!",
            message: "Hodimlar ishga qiziqish darajasi juda yuqori. Jamoangiz motivatsiyali va samarali ishlaydi.",
            icon: CheckCircleIcon,
        });
    } else if (avgScore < 50) {
        results.unshift({
            type: 'danger',
            title: 'Diqqat talab qilinadi!',
            message: 'Umumiy ishga qiziqish darajasi past. Hodimlar ishdan qoniqmayotgan bo\'lishi mumkin.',
            icon: ExclamationTriangleIcon,
        });
    }

    return results;
});

const getInsightForComponent = (key, score) => {
    const insights = {
        work_satisfaction: {
            title: 'Ish qoniqishi past',
            message: 'Hodimlar kundalik vazifalardan qoniqmayapti. Vazifalarni qayta ko\'rib chiqing va ularning kuchli tomonlaridan foydalaning.',
            action: 'Har bir hodim bilan 1:1 suhbat o\'tkazing',
        },
        team_collaboration: {
            title: 'Jamoa hamkorligi zaif',
            message: 'Jamoa a\'zolari orasida yetarli aloqa yo\'q. Team-building tadbirlar va hamkorlik loyihalarini ko\'paytiring.',
            action: 'Haftalik jamoa uchrashuvlarini joriy qiling',
        },
        growth_opportunities: {
            title: "O'sish imkoniyatlari cheklangan",
            message: 'Hodimlar karyera rivojlanishi imkoniyatini ko\'rmayapti. Treninglar va mentor dasturlarini taklif qiling.',
            action: 'Har bir hodim uchun rivojlanish rejasi tuzing',
        },
        recognition_frequency: {
            title: 'Tan olish yetishmaydi',
            message: 'Hodimlar ishlarini e\'tirof etilganini his qilmayapti. Muntazam ravishda minnatdorchilik bildiring.',
            action: 'Haftalik "Star Employee" dasturini boshlang',
        },
        manager_support: {
            title: 'Rahbar qo\'llab-quvvatlashi kam',
            message: 'Hodimlar menejerdan yetarli yordam olmayapti. Menejerlar uchun coaching treningi o\'tkazing.',
            action: 'Menejerlar bilan muloqot sifatini yaxshilang',
        },
        work_life_balance: {
            title: 'Ish-hayot balansi buzilgan',
            message: 'Hodimlar ish yukidan charchagan. Moslashuvchan ish jadvalini ko\'rib chiqing.',
            action: 'Overtime soatlarini kamaytiring',
        },
        purpose_clarity: {
            title: 'Maqsad aniqligi past',
            message: 'Hodimlar kompaniya missiyasini tushunmayapti. Kompaniya qadriyatlarini muntazam targ\'ib qiling.',
            action: 'Oylik "All-hands" uchrashuvlar o\'tkazing',
        },
        resources_adequacy: {
            title: 'Resurslar yetishmaydi',
            message: 'Hodimlar ishni bajarish uchun kerakli vositalardan mahrum. Zarur jihozlar va dasturlarni ta\'minlang.',
            action: 'Resurs so\'rovlarini ko\'rib chiqing',
        },
    };

    if (score >= 60 || !insights[key]) return null;

    return {
        type: score < 40 ? 'danger' : 'warning',
        title: insights[key].title,
        message: insights[key].message,
        action: insights[key].action,
        icon: score < 40 ? ExclamationTriangleIcon : LightBulbIcon,
    };
};

onMounted(fetchData);
</script>

<template>
    <HRLayout title="Ishga Qiziqish">
        <Head title="Hodimlar Ishga Qiziqishi" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">
                        Hodimlar Ishga Qiziqishi
                    </h1>
                    <p class="text-gray-400 mt-1">
                        Gallup Q12 metodologiyasiga asoslangan ishga qiziqish tahlili
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
            <div v-if="statistics" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                            <HeartIcon class="w-6 h-6 text-purple-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-400">O'rtacha ball</p>
                            <p :class="['text-2xl font-bold', getScoreColor(statistics.overview?.avg_score)]">
                                {{ statistics.overview?.avg_score?.toFixed(1) || 0 }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center">
                            <UserIcon class="w-6 h-6 text-emerald-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-400">Qiziqgan hodimlar</p>
                            <p class="text-2xl font-bold text-emerald-400">
                                {{ (statistics.distribution?.highly_engaged?.count || 0) + (statistics.distribution?.engaged?.count || 0) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-amber-500/20 rounded-xl flex items-center justify-center">
                            <ChartBarIcon class="w-6 h-6 text-amber-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-400">Neytral</p>
                            <p class="text-2xl font-bold text-amber-400">
                                {{ statistics.distribution?.neutral?.count || 0 }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center">
                            <UserIcon class="w-6 h-6 text-red-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-400">Qiziqmagan</p>
                            <p class="text-2xl font-bold text-red-400">
                                {{ statistics.distribution?.disengaged?.count || 0 }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Component Scores -->
            <div v-if="statistics?.components" class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                <h3 class="text-lg font-semibold text-white mb-4">
                    Komponent bo'yicha o'rtacha balllar
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div
                        v-for="component in statistics.components"
                        :key="component.key"
                        class="p-4 bg-gray-700/50 rounded-lg border border-gray-600"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-300">{{ component.label }}</span>
                            <span :class="['text-lg font-bold', getScoreColor(component.score)]">
                                {{ component.score }}
                            </span>
                        </div>
                        <div class="bg-gray-600 rounded-full h-2">
                            <div
                                :class="['h-2 rounded-full transition-all duration-500', getScoreBarColor(component.score)]"
                                :style="{ width: `${component.score}%` }"
                            ></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            {{ component.score >= 80 ? "A'lo" : component.score >= 65 ? 'Yaxshi' : component.score >= 50 ? "O'rtacha" : 'Diqqat talab' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Insights & Recommendations -->
            <div v-if="insights.length > 0" class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <LightBulbIcon class="w-5 h-5 text-amber-400" />
                    Xulosa va tavsiyalar
                </h3>
                <div class="space-y-4">
                    <div
                        v-for="(insight, index) in insights"
                        :key="index"
                        :class="[
                            'p-4 rounded-lg border',
                            insight.type === 'success' ? 'bg-emerald-500/10 border-emerald-500/30' :
                            insight.type === 'danger' ? 'bg-red-500/10 border-red-500/30' :
                            'bg-amber-500/10 border-amber-500/30'
                        ]"
                    >
                        <div class="flex items-start gap-3">
                            <component
                                :is="insight.icon"
                                :class="[
                                    'w-5 h-5 mt-0.5 flex-shrink-0',
                                    insight.type === 'success' ? 'text-emerald-400' :
                                    insight.type === 'danger' ? 'text-red-400' :
                                    'text-amber-400'
                                ]"
                            />
                            <div class="flex-1">
                                <h4 :class="[
                                    'font-medium',
                                    insight.type === 'success' ? 'text-emerald-400' :
                                    insight.type === 'danger' ? 'text-red-400' :
                                    'text-amber-400'
                                ]">
                                    {{ insight.title }}
                                </h4>
                                <p class="text-gray-300 text-sm mt-1">{{ insight.message }}</p>
                                <p v-if="insight.action" class="text-gray-400 text-sm mt-2 flex items-center gap-1">
                                    <ArrowTrendingUpIcon class="w-4 h-4" />
                                    Tavsiya: {{ insight.action }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gallup Q12 Info -->
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                <h3 class="text-lg font-semibold text-white mb-4">
                    Gallup Q12 haqida
                </h3>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-gray-300 text-sm leading-relaxed">
                            <strong class="text-white">Gallup Q12</strong> - 30 yildan ortiq tadqiqotlarga asoslangan
                            xodimlar ishga qiziqish darajasini o'lchovchi standart metodologiya. 17 milliondan ortiq
                            xodim ustida sinovdan o'tgan 12 ta asosiy savoldan iborat.
                        </p>
                        <div class="mt-4 space-y-2">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="w-3 h-3 bg-emerald-500 rounded-full"></span>
                                <span class="text-gray-300">80-100: Juda qiziqgan (Highly Engaged)</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="w-3 h-3 bg-blue-500 rounded-full"></span>
                                <span class="text-gray-300">65-79: Qiziqgan (Engaged)</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="w-3 h-3 bg-amber-500 rounded-full"></span>
                                <span class="text-gray-300">50-64: Neytral (Neutral)</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                                <span class="text-gray-300">0-49: Qiziqmagan (Disengaged)</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-white font-medium mb-2">Nima uchun muhim?</h4>
                        <ul class="text-gray-300 text-sm space-y-2">
                            <li class="flex items-start gap-2">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0" />
                                <span>Yuqori ishga qiziqish = 23% ko'proq foyda (Gallup)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0" />
                                <span>18% yuqoriroq mijozlar sodiqlihi</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0" />
                                <span>Kadr aylanmasi kamayadi</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <CheckCircleIcon class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0" />
                                <span>Ish sifati va unumdorlik ortadi</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <FunnelIcon class="w-5 h-5 text-gray-400" />
                    <select
                        v-model="selectedLevel"
                        @change="fetchData"
                        class="px-4 py-2 bg-gray-800 border border-gray-700 text-white rounded-lg focus:ring-2 focus:ring-purple-500"
                    >
                        <option v-for="opt in levelOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Employees Table -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead class="bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Hodim
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Umumiy ball
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Daraja
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Davr
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Amallar
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <tr
                                v-for="engagement in engagements"
                                :key="engagement.id"
                                class="hover:bg-gray-700/50"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-purple-500/20 rounded-full flex items-center justify-center">
                                            <span class="text-sm font-medium text-purple-400">
                                                {{ engagement.user?.name?.charAt(0) || '?' }}
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-white">
                                                {{ engagement.user?.name || 'Noma\'lum' }}
                                            </div>
                                            <div class="text-sm text-gray-400">
                                                {{ engagement.user?.email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <span :class="['text-lg font-bold', getScoreColor(engagement.overall_score)]">
                                            {{ engagement.overall_score?.toFixed(1) }}
                                        </span>
                                        <div class="w-24 bg-gray-600 rounded-full h-2">
                                            <div
                                                :class="['h-2 rounded-full', getScoreBarColor(engagement.overall_score)]"
                                                :style="{ width: `${engagement.overall_score}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-3 py-1 text-xs font-medium rounded-full', getLevelColor(engagement.engagement_level)]">
                                        {{ getLevelLabel(engagement.engagement_level) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                    {{ engagement.period }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <button
                                        @click="recalculateEngagement(engagement.user?.id)"
                                        class="text-purple-400 hover:text-purple-300"
                                        title="Qayta hisoblash"
                                    >
                                        <ArrowPathIcon class="w-5 h-5" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="loading" class="p-8 text-center">
                    <ArrowPathIcon class="w-8 h-8 mx-auto animate-spin text-purple-400" />
                    <p class="mt-2 text-gray-400">Yuklanmoqda...</p>
                </div>

                <div v-else-if="engagements.length === 0" class="p-8 text-center text-gray-400">
                    <HeartIcon class="w-12 h-12 mx-auto opacity-50 mb-3" />
                    <p>Ishga qiziqish ma'lumotlari topilmadi</p>
                </div>
            </div>
        </div>
    </HRLayout>
</template>
