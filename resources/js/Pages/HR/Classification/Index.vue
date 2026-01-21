<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    UserGroupIcon,
    AcademicCapIcon,
    BriefcaseIcon,
    ExclamationTriangleIcon,
    StarIcon,
    DocumentTextIcon,
    LightBulbIcon,
    WrenchScrewdriverIcon,
    ScaleIcon,
    ShieldExclamationIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowPathIcon,
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
const dashboardData = ref(null);
const classifications = ref([]);
const showClassifyModal = ref(false);
const selectedUser = ref(null);
const classifyForm = ref({
    user_id: '',
    employee_type: 'doer',
    position_fit: true,
    position_fit_notes: '',
    competency_scores: {
        leadership: 5,
        execution: 5,
        communication: 5,
        problem_solving: 5,
        teamwork: 5,
    },
});

// API Fetch
const fetchDashboard = async () => {
    if (!props.currentBusiness?.id) return;

    loading.value = true;
    try {
        const [dashboardRes, classificationsRes] = await Promise.all([
            axios.get(`/api/v1/businesses/${props.currentBusiness.id}/employee-classification/dashboard`),
            axios.get(`/api/v1/businesses/${props.currentBusiness.id}/employee-classification/`),
        ]);
        dashboardData.value = dashboardRes.data.data;
        classifications.value = classificationsRes.data.data?.data || [];
    } catch (error) {
        console.error('Error fetching classification data:', error);
    } finally {
        loading.value = false;
    }
};

const submitClassification = async () => {
    if (!props.currentBusiness?.id) return;

    try {
        await axios.post(
            `/api/v1/businesses/${props.currentBusiness.id}/employee-classification/classify`,
            classifyForm.value
        );
        showClassifyModal.value = false;
        fetchDashboard();
    } catch (error) {
        console.error('Error classifying employee:', error);
    }
};

const markAsStar = async (userId) => {
    if (!props.currentBusiness?.id) return;

    try {
        await axios.post(
            `/api/v1/businesses/${props.currentBusiness.id}/employee-classification/mark-star`,
            {
                user_id: userId,
                has_unique_knowledge: true,
                departure_risk: 'medium',
                replacement_difficulty: 3,
            }
        );
        fetchDashboard();
    } catch (error) {
        console.error('Error marking as star:', error);
    }
};

onMounted(() => {
    fetchDashboard();
});

// Computed
const summary = computed(() => dashboardData.value?.classification_summary || {});
const starEmployees = computed(() => dashboardData.value?.star_employees || {});
const positionMismatches = computed(() => dashboardData.value?.position_mismatches || {});
const knowledgeRisks = computed(() => dashboardData.value?.knowledge_risks || {});
const recommendations = computed(() => dashboardData.value?.recommendations || []);

// Employee type labels and colors
const getTypeLabel = (type) => {
    const labels = {
        thinker: "Думатель (Mustaqil)",
        doer: "Делатель (Ijrochi)",
        mixed: "Aralash",
    };
    return labels[type] || type;
};

const getTypeColor = (type) => {
    const colors = {
        thinker: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        doer: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        mixed: 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400',
    };
    return colors[type] || 'bg-gray-100 text-gray-700';
};

const getPriorityColor = (priority) => {
    const colors = {
        critical: 'bg-red-100 text-red-700 border-red-200',
        high: 'bg-orange-100 text-orange-700 border-orange-200',
        medium: 'bg-yellow-100 text-yellow-700 border-yellow-200',
        low: 'bg-green-100 text-green-700 border-green-200',
    };
    return colors[priority] || 'bg-gray-100 text-gray-700 border-gray-200';
};
</script>

<template>
    <AppLayout>
        <Head title="Xodim klassifikatsiyasi - HR" />

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Xodim klassifikatsiyasi
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Думатель vs Делатель metodologiyasi (Denis Shenukov)
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <button
                        @click="fetchDashboard"
                        :disabled="loading"
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <ArrowPathIcon :class="['w-5 h-5', loading && 'animate-spin']" />
                    </button>
                    <button
                        @click="showClassifyModal = true"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center"
                    >
                        <UserGroupIcon class="w-5 h-5 mr-2" />
                        Xodimni klassifikatsiya qilish
                    </button>
                </div>
            </div>

            <div v-if="loading" class="flex items-center justify-center py-20">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
            </div>

            <div v-else-if="dashboardData" class="space-y-8">
                <!-- Classification Summary -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Thinkers -->
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl p-6 border border-purple-100 dark:border-purple-800/30">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl flex items-center justify-center">
                                <LightBulbIcon class="w-6 h-6 text-white" />
                            </div>
                            <span class="text-3xl font-bold text-purple-600">{{ summary.thinkers?.count || 0 }}</span>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Думатель</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ summary.thinkers?.description }}</p>
                        <p class="text-sm text-purple-600 font-medium mt-2">
                            {{ summary.thinkers?.percent || 0 }}% jamoa
                        </p>
                    </div>

                    <!-- Doers -->
                    <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-2xl p-6 border border-blue-100 dark:border-blue-800/30">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-600 to-cyan-600 rounded-xl flex items-center justify-center">
                                <WrenchScrewdriverIcon class="w-6 h-6 text-white" />
                            </div>
                            <span class="text-3xl font-bold text-blue-600">{{ summary.doers?.count || 0 }}</span>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Делатель</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ summary.doers?.description }}</p>
                        <p class="text-sm text-blue-600 font-medium mt-2">
                            {{ summary.doers?.percent || 0 }}% jamoa
                        </p>
                    </div>

                    <!-- Mixed -->
                    <div class="bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-900/20 dark:to-slate-900/20 rounded-2xl p-6 border border-gray-100 dark:border-gray-800/30">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-gray-600 to-slate-600 rounded-xl flex items-center justify-center">
                                <ScaleIcon class="w-6 h-6 text-white" />
                            </div>
                            <span class="text-3xl font-bold text-gray-600">{{ summary.mixed?.count || 0 }}</span>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Aralash</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ summary.mixed?.description }}</p>
                        <p class="text-sm text-gray-600 font-medium mt-2">
                            {{ summary.mixed?.percent || 0 }}% jamoa
                        </p>
                    </div>

                    <!-- Unclassified -->
                    <div class="bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-2xl p-6 border border-orange-100 dark:border-orange-800/30">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-600 to-amber-600 rounded-xl flex items-center justify-center">
                                <ExclamationTriangleIcon class="w-6 h-6 text-white" />
                            </div>
                            <span class="text-3xl font-bold text-orange-600">{{ summary.unclassified || 0 }}</span>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Klassifikatsiya qilinmagan</h3>
                        <p class="text-sm text-gray-500 mt-1">Baholash kerak</p>
                        <button
                            @click="showClassifyModal = true"
                            class="mt-2 text-sm text-orange-600 hover:text-orange-700 font-medium"
                        >
                            Baholashni boshlash →
                        </button>
                    </div>
                </div>

                <!-- Recommendations -->
                <div v-if="recommendations.length" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <DocumentTextIcon class="w-5 h-5 mr-2 text-purple-600" />
                        Tavsiyalar
                    </h3>
                    <div class="space-y-3">
                        <div
                            v-for="(rec, index) in recommendations"
                            :key="index"
                            :class="[
                                'p-4 rounded-xl border',
                                getPriorityColor(rec.priority)
                            ]"
                        >
                            <div class="flex items-start gap-3">
                                <ExclamationTriangleIcon
                                    v-if="rec.priority === 'critical'"
                                    class="w-5 h-5 flex-shrink-0 mt-0.5"
                                />
                                <ShieldExclamationIcon
                                    v-else-if="rec.priority === 'high'"
                                    class="w-5 h-5 flex-shrink-0 mt-0.5"
                                />
                                <LightBulbIcon
                                    v-else
                                    class="w-5 h-5 flex-shrink-0 mt-0.5"
                                />
                                <div>
                                    <p class="font-medium">{{ rec.message }}</p>
                                    <p class="text-sm opacity-75 mt-1">Soha: {{ rec.area }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Star Employees Risk -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                            <StarIcon class="w-5 h-5 mr-2 text-yellow-500" />
                            "Yulduz" xodimlar xavfi
                        </h3>
                        <div class="flex gap-2 text-sm">
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full">
                                {{ starEmployees.count || 0 }} yulduz
                            </span>
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full">
                                {{ starEmployees.high_risk_count || 0 }} yuqori xavf
                            </span>
                        </div>
                    </div>

                    <div v-if="starEmployees.employees?.length" class="space-y-4">
                        <div
                            v-for="employee in starEmployees.employees"
                            :key="employee.user_id"
                            class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold">
                                        {{ employee.user_name?.charAt(0) || '?' }}
                                    </div>
                                    <div class="ml-4">
                                        <p class="font-medium text-gray-900 dark:text-white flex items-center">
                                            {{ employee.user_name }}
                                            <StarIcon class="w-4 h-4 text-yellow-500 ml-2" />
                                        </p>
                                        <span :class="[getTypeColor(employee.employee_type), 'text-xs px-2 py-0.5 rounded-full']">
                                            {{ getTypeLabel(employee.employee_type) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">Ketish xavfi:</p>
                                    <span :class="[
                                        'px-2 py-1 text-sm font-medium rounded-full',
                                        employee.departure_risk === 'critical' ? 'bg-red-100 text-red-700' :
                                        employee.departure_risk === 'high' ? 'bg-orange-100 text-orange-700' :
                                        'bg-yellow-100 text-yellow-700'
                                    ]">
                                        {{ employee.departure_risk }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="employee.warnings?.length" class="mt-4 space-y-2">
                                <div
                                    v-for="warning in employee.warnings"
                                    :key="warning.type"
                                    :class="[
                                        'flex items-center gap-2 text-sm p-2 rounded-lg',
                                        warning.severity === 'high' ? 'bg-red-50 text-red-700' : 'bg-yellow-50 text-yellow-700'
                                    ]"
                                >
                                    <ExclamationTriangleIcon class="w-4 h-4 flex-shrink-0" />
                                    {{ warning.message }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-gray-500">
                        Hozircha "yulduz" xodim belgilanmagan
                    </div>
                </div>

                <!-- Position Mismatches -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                            <XCircleIcon class="w-5 h-5 mr-2 text-red-500" />
                            Lavozim mos kelmasliklari
                        </h3>
                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-sm">
                            {{ positionMismatches.count || 0 }} ta topildi
                        </span>
                    </div>

                    <div v-if="positionMismatches.employees?.length" class="space-y-4">
                        <div
                            v-for="employee in positionMismatches.employees"
                            :key="employee.user_id"
                            class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4 border border-red-100 dark:border-red-800/30"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ employee.user_name }}
                                    </p>
                                    <span :class="[getTypeColor(employee.employee_type), 'text-xs px-2 py-0.5 rounded-full']">
                                        {{ employee.employee_type_label }}
                                    </span>
                                </div>
                                <XCircleIcon class="w-6 h-6 text-red-500" />
                            </div>
                            <p class="mt-3 text-sm text-red-700 dark:text-red-300">
                                {{ employee.recommendation }}
                            </p>
                            <p v-if="employee.notes" class="mt-2 text-sm text-gray-500">
                                {{ employee.notes }}
                            </p>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-gray-500">
                        Lavozim mos kelmasligi topilmadi
                    </div>
                </div>

                <!-- Knowledge Risks -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                            <AcademicCapIcon class="w-5 h-5 mr-2 text-orange-500" />
                            Bilim xavflari
                        </h3>
                        <div class="flex gap-2 text-sm">
                            <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded-full">
                                {{ knowledgeRisks.at_risk_count || 0 }} ta xavfda
                            </span>
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full">
                                {{ knowledgeRisks.critical_risks || 0 }} ta kritik
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4 text-center">
                            <p class="text-3xl font-bold text-orange-600">{{ knowledgeRisks.at_risk_count || 0 }}</p>
                            <p class="text-sm text-gray-500">Xavfdagi funksiyalar</p>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 text-center">
                            <p class="text-3xl font-bold text-yellow-600">{{ knowledgeRisks.not_documented_count || 0 }}</p>
                            <p class="text-sm text-gray-500">Hujjatlashtirilmagan</p>
                        </div>
                    </div>

                    <div v-if="knowledgeRisks.functions_at_risk?.length" class="space-y-3">
                        <div
                            v-for="func in knowledgeRisks.functions_at_risk"
                            :key="func.id"
                            :class="[
                                'p-4 rounded-xl border',
                                func.criticality === 'critical' ? 'bg-red-50 border-red-200' :
                                func.criticality === 'high' ? 'bg-orange-50 border-orange-200' :
                                'bg-yellow-50 border-yellow-200'
                            ]"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-medium text-gray-900">{{ func.name }}</p>
                                    <p class="text-sm text-gray-500">
                                        Darajasi: {{ func.criticality_label }} |
                                        Biluvchilar: {{ func.holders_count }} ta
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span v-if="!func.is_documented" class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">
                                        Hujjatlashtirilmagan
                                    </span>
                                    <CheckCircleIcon v-else class="w-5 h-5 text-green-500" />
                                </div>
                            </div>
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ func.action_needed }}
                            </p>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-gray-500">
                        Bilim xavflari topilmadi
                    </div>
                </div>

                <!-- Recent Classifications -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        So'nggi klassifikatsiyalar
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Xodim</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Turi</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Lavozim mos</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Yulduz</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Baholangan</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amallar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr
                                    v-for="classification in classifications"
                                    :key="classification.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                >
                                    <td class="px-4 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold">
                                                {{ classification.user?.name?.charAt(0) || '?' }}
                                            </div>
                                            <span class="ml-3 font-medium text-gray-900 dark:text-white">
                                                {{ classification.user?.name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span :class="[getTypeColor(classification.employee_type), 'px-2 py-1 text-sm rounded-full']">
                                            {{ getTypeLabel(classification.employee_type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <CheckCircleIcon v-if="classification.position_fit" class="w-5 h-5 text-green-500 mx-auto" />
                                        <XCircleIcon v-else class="w-5 h-5 text-red-500 mx-auto" />
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <StarIcon v-if="classification.is_star_employee" class="w-5 h-5 text-yellow-500 mx-auto" />
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-500">
                                        {{ new Date(classification.assessed_at).toLocaleDateString('uz-UZ') }}
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <button
                                            v-if="!classification.is_star_employee"
                                            @click="markAsStar(classification.user_id)"
                                            class="text-yellow-600 hover:text-yellow-700 text-sm"
                                        >
                                            Yulduz qilish
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="!classifications?.length" class="text-center py-8 text-gray-500">
                        Klassifikatsiya ma'lumotlari topilmadi
                    </div>
                </div>
            </div>

            <!-- No data state -->
            <div v-else-if="!loading" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center">
                <UserGroupIcon class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    Ma'lumot topilmadi
                </h3>
                <p class="text-gray-500 mb-6">
                    Xodim klassifikatsiyasi hali boshlanmagan
                </p>
                <button
                    @click="showClassifyModal = true"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
                >
                    <UserGroupIcon class="w-5 h-5 mr-2" />
                    Klassifikatsiyani boshlash
                </button>
            </div>
        </div>

        <!-- Classify Modal -->
        <div
            v-if="showClassifyModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showClassifyModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full mx-4 p-6 max-h-[90vh] overflow-y-auto">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                    Xodimni klassifikatsiya qilish
                </h3>

                <form @submit.prevent="submitClassification" class="space-y-6">
                    <!-- User selection would go here - simplified for demo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Xodim ID
                        </label>
                        <input
                            v-model="classifyForm.user_id"
                            type="text"
                            placeholder="Xodim UUID kiriting"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Xodim turi
                        </label>
                        <div class="grid grid-cols-3 gap-4">
                            <label
                                :class="[
                                    'flex flex-col items-center p-4 rounded-xl border-2 cursor-pointer transition-all',
                                    classifyForm.employee_type === 'thinker'
                                        ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-purple-300'
                                ]"
                            >
                                <input
                                    v-model="classifyForm.employee_type"
                                    type="radio"
                                    value="thinker"
                                    class="sr-only"
                                />
                                <LightBulbIcon class="w-8 h-8 text-purple-600 mb-2" />
                                <span class="font-medium">Думатель</span>
                                <span class="text-xs text-gray-500 text-center mt-1">Mustaqil qaror qiladi</span>
                            </label>

                            <label
                                :class="[
                                    'flex flex-col items-center p-4 rounded-xl border-2 cursor-pointer transition-all',
                                    classifyForm.employee_type === 'doer'
                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-blue-300'
                                ]"
                            >
                                <input
                                    v-model="classifyForm.employee_type"
                                    type="radio"
                                    value="doer"
                                    class="sr-only"
                                />
                                <WrenchScrewdriverIcon class="w-8 h-8 text-blue-600 mb-2" />
                                <span class="font-medium">Делатель</span>
                                <span class="text-xs text-gray-500 text-center mt-1">Aniq ko'rsatma kerak</span>
                            </label>

                            <label
                                :class="[
                                    'flex flex-col items-center p-4 rounded-xl border-2 cursor-pointer transition-all',
                                    classifyForm.employee_type === 'mixed'
                                        ? 'border-gray-500 bg-gray-50 dark:bg-gray-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                                ]"
                            >
                                <input
                                    v-model="classifyForm.employee_type"
                                    type="radio"
                                    value="mixed"
                                    class="sr-only"
                                />
                                <ScaleIcon class="w-8 h-8 text-gray-600 mb-2" />
                                <span class="font-medium">Aralash</span>
                                <span class="text-xs text-gray-500 text-center mt-1">Ikki rolda ishlaydi</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input
                                v-model="classifyForm.position_fit"
                                type="checkbox"
                                class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                            />
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                Lavozim mos keladi
                            </span>
                        </label>
                    </div>

                    <div v-if="!classifyForm.position_fit">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nima uchun mos emas?
                        </label>
                        <textarea
                            v-model="classifyForm.position_fit_notes"
                            rows="3"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            placeholder="Izoh kiriting..."
                        ></textarea>
                    </div>

                    <!-- Competency Scores -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                            Kompetensiya baholari (1-10)
                        </label>
                        <div class="space-y-4">
                            <div v-for="(value, key) in classifyForm.competency_scores" :key="key" class="flex items-center gap-4">
                                <span class="w-32 text-sm text-gray-600 dark:text-gray-400 capitalize">
                                    {{
                                        key === 'leadership' ? 'Liderlik' :
                                        key === 'execution' ? 'Ijro' :
                                        key === 'communication' ? 'Muloqot' :
                                        key === 'problem_solving' ? 'Muammo hal qilish' :
                                        'Jamoaviylik'
                                    }}
                                </span>
                                <input
                                    v-model.number="classifyForm.competency_scores[key]"
                                    type="range"
                                    min="1"
                                    max="10"
                                    class="flex-1"
                                />
                                <span class="w-8 text-center font-medium">{{ classifyForm.competency_scores[key] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button
                            type="button"
                            @click="showClassifyModal = false"
                            class="flex-1 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        >
                            Bekor qilish
                        </button>
                        <button
                            type="submit"
                            class="flex-1 py-3 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors"
                        >
                            Saqlash
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
