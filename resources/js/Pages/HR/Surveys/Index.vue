<script setup>
import { ref, onMounted } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import {
    ClipboardDocumentListIcon,
    PlusIcon,
    ArrowPathIcon,
    PlayIcon,
    StopIcon,
    ChartBarIcon,
    DocumentDuplicateIcon,
    EyeIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    currentBusiness: Object,
});

const loading = ref(true);
const creating = ref(false);
const surveys = ref([]);
const statistics = ref(null);
const showCreateModal = ref(false);
const selectedTemplate = ref('q12');
const errorMessage = ref('');

const templates = [
    { value: 'q12', label: 'Gallup Q12 Ishga Qiziqish', description: "Hodimlar ishga qiziqishini o'lchash" },
    { value: 'pulse', label: 'Haftalik Pulse', description: 'Tezkor kayfiyat tekshiruvi' },
    { value: 'exit', label: 'Exit Interview', description: 'Ketayotgan hodimlar uchun' },
];

const fetchData = async () => {
    if (!props.currentBusiness?.id) {
        errorMessage.value = 'Biznes tanlanmagan';
        loading.value = false;
        return;
    }

    loading.value = true;
    errorMessage.value = '';
    try {
        const [surveysRes, statsRes] = await Promise.all([
            axios.get(`/api/v1/businesses/${props.currentBusiness.id}/hr/surveys`),
            axios.get(`/api/v1/businesses/${props.currentBusiness.id}/hr/surveys/statistics`)
        ]);

        surveys.value = surveysRes.data.data?.data || surveysRes.data.data || [];
        statistics.value = statsRes.data.data;
    } catch (error) {
        console.error('Error fetching surveys:', error);
        errorMessage.value = error.response?.data?.message || "Ma'lumotlarni yuklashda xatolik";
    } finally {
        loading.value = false;
    }
};

const createFromTemplate = async () => {
    if (!props.currentBusiness?.id) {
        errorMessage.value = 'Biznes tanlanmagan';
        return;
    }

    creating.value = true;
    errorMessage.value = '';
    try {
        const response = await axios.post(`/api/v1/businesses/${props.currentBusiness.id}/hr/surveys/from-template`, {
            template: selectedTemplate.value
        });
        if (response.data.success) {
            showCreateModal.value = false;
            await fetchData();
        } else {
            errorMessage.value = response.data.message || 'Xatolik yuz berdi';
        }
    } catch (error) {
        console.error('Error creating survey:', error);
        errorMessage.value = error.response?.data?.message || "So'rovnoma yaratishda xatolik";
    } finally {
        creating.value = false;
    }
};

const activateSurvey = async (surveyId) => {
    if (!props.currentBusiness?.id) return;

    try {
        await axios.post(`/api/v1/businesses/${props.currentBusiness.id}/hr/surveys/${surveyId}/activate`);
        fetchData();
    } catch (error) {
        console.error('Error activating survey:', error);
    }
};

const closeSurvey = async (surveyId) => {
    if (!props.currentBusiness?.id) return;

    try {
        await axios.post(`/api/v1/businesses/${props.currentBusiness.id}/hr/surveys/${surveyId}/close`);
        fetchData();
    } catch (error) {
        console.error('Error closing survey:', error);
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

onMounted(fetchData);
</script>

<template>
    <HRLayout title="So'rovnomalar">
        <Head title="HR So'rovnomalar" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        HR So'rovnomalar
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Hodimlar fikrlarini yig'ish va tahlil qilish
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="fetchData"
                        :disabled="loading"
                        class="flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <ArrowPathIcon :class="['w-5 h-5', loading ? 'animate-spin' : '']" />
                        Yangilash
                    </button>
                    <button
                        @click="showCreateModal = true"
                        class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Yangi so'rovnoma
                    </button>
                </div>
            </div>

            <!-- Error Message -->
            <div v-if="errorMessage" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <p class="text-red-800 dark:text-red-400">{{ errorMessage }}</p>
            </div>

            <!-- Statistics Cards -->
            <div v-if="statistics" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                            <ClipboardDocumentListIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ statistics.total_surveys || 0 }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                            <PlayIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Faol</p>
                            <p class="text-2xl font-bold text-green-600">
                                {{ statistics.status_distribution?.active || 0 }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                            <ChartBarIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha javob</p>
                            <p class="text-2xl font-bold text-blue-600">
                                {{ statistics.avg_response_rate?.toFixed(1) || 0 }}%
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center">
                            <DocumentDuplicateIcon class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami javoblar</p>
                            <p class="text-2xl font-bold text-orange-600">
                                {{ statistics.total_responses || 0 }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Surveys List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">So'rovnoma</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Turi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Javoblar</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Muddat</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="survey in surveys"
                                :key="survey.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ survey.title }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ survey.questions_count }} ta savol
                                            <span v-if="survey.is_anonymous" class="ml-2 text-xs text-purple-600">
                                                (Anonim)
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-3 py-1 text-xs font-medium rounded-full', getTypeColor(survey.type)]">
                                        {{ survey.type_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-3 py-1 text-xs font-medium rounded-full', getStatusColor(survey.status)]">
                                        {{ survey.status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ survey.response_count }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            ({{ survey.response_rate?.toFixed(1) }}%)
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <div v-if="survey.end_date">
                                        {{ survey.end_date }}gacha
                                    </div>
                                    <div v-else>-</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- View Details -->
                                        <Link
                                            :href="`/hr/surveys/${survey.id}`"
                                            class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                                            title="Tafsilotlar"
                                        >
                                            <EyeIcon class="w-5 h-5" />
                                        </Link>
                                        <!-- Activate -->
                                        <button
                                            v-if="survey.status === 'draft'"
                                            @click="activateSurvey(survey.id)"
                                            class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg"
                                            title="Faollashtirish"
                                        >
                                            <PlayIcon class="w-5 h-5" />
                                        </button>
                                        <!-- Close -->
                                        <button
                                            v-if="survey.status === 'active'"
                                            @click="closeSurvey(survey.id)"
                                            class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"
                                            title="Yopish"
                                        >
                                            <StopIcon class="w-5 h-5" />
                                        </button>
                                        <!-- Results -->
                                        <Link
                                            v-if="survey.response_count > 0"
                                            :href="`/hr/surveys/${survey.id}/results`"
                                            class="p-2 text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg"
                                            title="Natijalar"
                                        >
                                            <ChartBarIcon class="w-5 h-5" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="loading" class="p-8 text-center">
                    <ArrowPathIcon class="w-8 h-8 mx-auto animate-spin text-purple-600" />
                    <p class="mt-2 text-gray-500">Yuklanmoqda...</p>
                </div>

                <div v-else-if="surveys.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <ClipboardDocumentListIcon class="w-12 h-12 mx-auto opacity-50 mb-3" />
                    <p>So'rovnomalar topilmadi</p>
                    <button
                        @click="showCreateModal = true"
                        class="mt-4 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
                    >
                        Birinchi so'rovnomani yarating
                    </button>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Yangi so'rovnoma yaratish
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Tayyor shablondan tanlang:
                </p>
                <div class="space-y-3">
                    <label
                        v-for="tmpl in templates"
                        :key="tmpl.value"
                        :class="[
                            'block p-4 border rounded-lg cursor-pointer transition-colors',
                            selectedTemplate === tmpl.value
                                ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                : 'border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                        ]"
                    >
                        <input
                            type="radio"
                            v-model="selectedTemplate"
                            :value="tmpl.value"
                            class="sr-only"
                        />
                        <div class="font-medium text-gray-900 dark:text-white">{{ tmpl.label }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ tmpl.description }}</div>
                    </label>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button
                        @click="showCreateModal = false"
                        :disabled="creating"
                        class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg disabled:opacity-50"
                    >
                        Bekor qilish
                    </button>
                    <button
                        @click="createFromTemplate"
                        :disabled="creating"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 flex items-center gap-2"
                    >
                        <ArrowPathIcon v-if="creating" class="w-4 h-4 animate-spin" />
                        {{ creating ? 'Yaratilmoqda...' : 'Yaratish' }}
                    </button>
                </div>
            </div>
        </div>
    </HRLayout>
</template>
