<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import {
    ClipboardDocumentListIcon,
    ArrowPathIcon,
    ClockIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    currentBusiness: Object,
});

const loading = ref(true);
const surveys = ref([]);
const errorMessage = ref('');

const fetchMySurveys = async () => {
    if (!props.currentBusiness?.id) {
        errorMessage.value = 'Biznes tanlanmagan';
        loading.value = false;
        return;
    }

    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await axios.get(
            `/api/v1/businesses/${props.currentBusiness.id}/hr/surveys/my-available`
        );
        surveys.value = response.data.data || [];
    } catch (error) {
        console.error('Error fetching my surveys:', error);
        errorMessage.value = error.response?.data?.message || "So'rovnomalarni yuklashda xatolik";
    } finally {
        loading.value = false;
    }
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

onMounted(fetchMySurveys);
</script>

<template>
    <HRLayout title="Mening so'rovnomalarim">
        <Head title="Mening so'rovnomalarim" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Mening so'rovnomalarim
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Sizga tayinlangan so'rovnomalar
                    </p>
                </div>
                <button
                    @click="fetchMySurveys"
                    :disabled="loading"
                    class="flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    <ArrowPathIcon :class="['w-5 h-5', loading ? 'animate-spin' : '']" />
                    Yangilash
                </button>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex items-center justify-center py-12">
                <ArrowPathIcon class="w-8 h-8 animate-spin text-purple-600" />
            </div>

            <!-- Error -->
            <div v-else-if="errorMessage" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <p class="text-red-800 dark:text-red-400">{{ errorMessage }}</p>
            </div>

            <!-- Surveys Grid -->
            <div v-else-if="surveys.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="survey in surveys"
                    :key="survey.id"
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow"
                >
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <span :class="['px-3 py-1 text-xs font-medium rounded-full', getTypeColor(survey.type)]">
                                {{ survey.type_label }}
                            </span>
                            <div v-if="survey.is_anonymous" class="flex items-center gap-1 text-xs text-purple-600 dark:text-purple-400">
                                <CheckCircleIcon class="w-4 h-4" />
                                Anonim
                            </div>
                        </div>

                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">
                            {{ survey.title }}
                        </h3>

                        <p v-if="survey.description" class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                            {{ survey.description }}
                        </p>

                        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-4">
                            <div class="flex items-center gap-1">
                                <ClipboardDocumentListIcon class="w-4 h-4" />
                                {{ survey.questions_count }} savol
                            </div>
                            <div v-if="survey.end_date" class="flex items-center gap-1">
                                <ClockIcon class="w-4 h-4" />
                                {{ survey.end_date }}gacha
                            </div>
                        </div>

                        <Link
                            :href="`/hr/surveys/${survey.id}/fill`"
                            class="block w-full text-center px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium transition-colors"
                        >
                            So'rovnomani boshlash
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-sm">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <CheckCircleIcon class="w-10 h-10 text-green-600" />
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    Hammasi bajarilgan!
                </h3>
                <p class="text-gray-500 dark:text-gray-400">
                    Hozircha sizga tayinlangan so'rovnomalar yo'q.
                    Yangi so'rovnomalar paydo bo'lganda bu yerda ko'rinadi.
                </p>
            </div>
        </div>
    </HRLayout>
</template>
