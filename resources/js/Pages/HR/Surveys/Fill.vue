<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import {
    ArrowPathIcon,
    CheckCircleIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ClipboardDocumentCheckIcon,
    ExclamationCircleIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    currentBusiness: Object,
    surveyId: String,
});

const loading = ref(true);
const submitting = ref(false);
const submitted = ref(false);
const survey = ref(null);
const answers = ref({});
const currentQuestionIndex = ref(0);
const errorMessage = ref('');
const startTime = ref(null);

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
        startTime.value = Date.now();

        // Initialize answers
        if (survey.value?.questions) {
            survey.value.questions.forEach((_, index) => {
                answers.value[`q_${index}`] = null;
            });
        }
    } catch (error) {
        console.error('Error fetching survey:', error);
        errorMessage.value = error.response?.data?.message || "So'rovnomani yuklashda xatolik";
    } finally {
        loading.value = false;
    }
};

const currentQuestion = computed(() => {
    return survey.value?.questions?.[currentQuestionIndex.value] || null;
});

const totalQuestions = computed(() => {
    return survey.value?.questions?.length || 0;
});

const progress = computed(() => {
    if (totalQuestions.value === 0) return 0;
    return Math.round(((currentQuestionIndex.value + 1) / totalQuestions.value) * 100);
});

const currentAnswer = computed({
    get() {
        return answers.value[`q_${currentQuestionIndex.value}`];
    },
    set(value) {
        answers.value[`q_${currentQuestionIndex.value}`] = value;
    }
});

const isValidAnswer = (answer) => {
    if (answer === null || answer === '' || answer === undefined) return false;
    // Empty array means not answered for multiple choice
    if (Array.isArray(answer) && answer.length === 0) return false;
    return true;
};

const canGoNext = computed(() => {
    const allowSkip = survey.value?.settings?.allow_skip;
    if (allowSkip) return true;
    return isValidAnswer(currentAnswer.value);
});

const canSubmit = computed(() => {
    const allowSkip = survey.value?.settings?.allow_skip;
    if (allowSkip) return true;
    return Object.values(answers.value).every(isValidAnswer);
});

const nextQuestion = () => {
    if (currentQuestionIndex.value < totalQuestions.value - 1) {
        currentQuestionIndex.value++;
    }
};

const prevQuestion = () => {
    if (currentQuestionIndex.value > 0) {
        currentQuestionIndex.value--;
    }
};

const selectScaleAnswer = (value) => {
    currentAnswer.value = value;
};

const selectChoiceAnswer = (option) => {
    currentAnswer.value = option;
};

const toggleMultipleChoice = (option) => {
    // Create a new array to properly trigger Vue reactivity
    const current = Array.isArray(currentAnswer.value) ? [...currentAnswer.value] : [];
    const index = current.indexOf(option);
    if (index > -1) {
        current.splice(index, 1);
    } else {
        current.push(option);
    }
    // Set the new array through computed setter for proper reactivity
    currentAnswer.value = current;
};

const submitSurvey = async () => {
    if (!props.currentBusiness?.id || !props.surveyId) return;

    submitting.value = true;
    errorMessage.value = '';

    const timeSpent = Math.round((Date.now() - startTime.value) / 1000);

    try {
        await axios.post(
            `/api/v1/businesses/${props.currentBusiness.id}/hr/surveys/${props.surveyId}/respond`,
            {
                answers: answers.value,
                time_spent_seconds: timeSpent,
            }
        );
        submitted.value = true;
    } catch (error) {
        console.error('Error submitting survey:', error);
        errorMessage.value = error.response?.data?.message || "Javoblarni yuborishda xatolik";
    } finally {
        submitting.value = false;
    }
};

onMounted(fetchSurvey);
</script>

<template>
    <HRLayout :title="survey?.title || 'So\'rovnoma'">
        <Head :title="survey?.title || 'So\'rovnoma to\'ldirish'" />

        <div class="max-w-3xl mx-auto">
            <!-- Loading -->
            <div v-if="loading" class="flex items-center justify-center py-24">
                <ArrowPathIcon class="w-10 h-10 animate-spin text-purple-600" />
            </div>

            <!-- Error -->
            <div v-else-if="errorMessage && !survey" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-8 text-center">
                <ExclamationCircleIcon class="w-16 h-16 mx-auto text-red-400 mb-4" />
                <h2 class="text-xl font-semibold text-red-800 dark:text-red-400 mb-2">Xatolik</h2>
                <p class="text-red-600 dark:text-red-300">{{ errorMessage }}</p>
            </div>

            <!-- Submitted Success -->
            <div v-else-if="submitted" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-12 text-center">
                <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                    <CheckCircleIcon class="w-12 h-12 text-green-600" />
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                    Rahmat!
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-8">
                    Javoblaringiz muvaffaqiyatli qabul qilindi.
                    {{ survey?.is_anonymous ? 'Javoblaringiz anonim saqlanadi.' : '' }}
                </p>
                <a
                    href="/hr/surveys"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
                >
                    <ClipboardDocumentCheckIcon class="w-5 h-5" />
                    So'rovnomalarga qaytish
                </a>
            </div>

            <!-- Survey Form -->
            <div v-else-if="survey" class="space-y-6">
                <!-- Header -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ survey.title }}
                    </h1>
                    <p v-if="survey.description" class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                        {{ survey.description }}
                    </p>
                    <div v-if="survey.is_anonymous" class="flex items-center gap-2 text-sm text-purple-600 dark:text-purple-400">
                        <CheckCircleIcon class="w-4 h-4" />
                        Bu so'rovnoma anonim - javoblaringiz maxfiy saqlanadi
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <span>Savol {{ currentQuestionIndex + 1 }} / {{ totalQuestions }}</span>
                        <span>{{ progress }}%</span>
                    </div>
                    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div
                            class="h-full bg-purple-600 transition-all duration-300"
                            :style="{ width: `${progress}%` }"
                        ></div>
                    </div>
                </div>

                <!-- Question Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <div class="p-6">
                        <!-- Question Text -->
                        <div class="mb-6">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center shrink-0">
                                    <span class="text-lg font-bold text-purple-600 dark:text-purple-400">
                                        {{ currentQuestionIndex + 1 }}
                                    </span>
                                </div>
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white pt-2">
                                    {{ currentQuestion?.text }}
                                </h2>
                            </div>
                        </div>

                        <!-- Answer Input - Scale -->
                        <div v-if="currentQuestion?.type === 'scale' || currentQuestion?.type === 'rating'" class="mt-6">
                            <div class="flex items-center justify-center gap-3">
                                <button
                                    v-for="n in (currentQuestion.scale_max || 5) - (currentQuestion.scale_min || 1) + 1"
                                    :key="n"
                                    @click="selectScaleAnswer((currentQuestion.scale_min || 1) + n - 1)"
                                    :class="[
                                        'w-14 h-14 rounded-xl font-bold text-lg transition-all',
                                        currentAnswer === ((currentQuestion.scale_min || 1) + n - 1)
                                            ? 'bg-purple-600 text-white scale-110 shadow-lg'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-purple-100 dark:hover:bg-purple-900/30'
                                    ]"
                                >
                                    {{ (currentQuestion.scale_min || 1) + n - 1 }}
                                </button>
                            </div>
                            <div class="flex justify-between text-sm text-gray-500 mt-4 px-2">
                                <span>Kam</span>
                                <span>Ko'p</span>
                            </div>
                        </div>

                        <!-- Answer Input - Choice -->
                        <div v-else-if="currentQuestion?.type === 'choice'" class="mt-6 space-y-3">
                            <button
                                v-for="(option, index) in currentQuestion.options"
                                :key="index"
                                @click="selectChoiceAnswer(option)"
                                :class="[
                                    'w-full p-4 text-left rounded-xl border-2 transition-all',
                                    currentAnswer === option
                                        ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-700'
                                ]"
                            >
                                <span :class="[
                                    'font-medium',
                                    currentAnswer === option ? 'text-purple-700 dark:text-purple-300' : 'text-gray-700 dark:text-gray-300'
                                ]">
                                    {{ option }}
                                </span>
                            </button>
                        </div>

                        <!-- Answer Input - Multiple Choice -->
                        <div v-else-if="currentQuestion?.type === 'multiple_choice'" class="mt-6 space-y-3">
                            <button
                                v-for="(option, index) in currentQuestion.options"
                                :key="index"
                                @click="toggleMultipleChoice(option)"
                                :class="[
                                    'w-full p-4 text-left rounded-xl border-2 transition-all flex items-center gap-3',
                                    Array.isArray(currentAnswer) && currentAnswer.includes(option)
                                        ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-700'
                                ]"
                            >
                                <div :class="[
                                    'w-5 h-5 rounded border-2 flex items-center justify-center',
                                    Array.isArray(currentAnswer) && currentAnswer.includes(option)
                                        ? 'border-purple-500 bg-purple-500'
                                        : 'border-gray-300 dark:border-gray-600'
                                ]">
                                    <CheckCircleIcon v-if="Array.isArray(currentAnswer) && currentAnswer.includes(option)" class="w-3 h-3 text-white" />
                                </div>
                                <span :class="[
                                    'font-medium',
                                    Array.isArray(currentAnswer) && currentAnswer.includes(option)
                                        ? 'text-purple-700 dark:text-purple-300'
                                        : 'text-gray-700 dark:text-gray-300'
                                ]">
                                    {{ option }}
                                </span>
                            </button>
                        </div>

                        <!-- Answer Input - Yes/No -->
                        <div v-else-if="currentQuestion?.type === 'yes_no'" class="mt-6 flex items-center justify-center gap-4">
                            <button
                                @click="selectChoiceAnswer('yes')"
                                :class="[
                                    'px-12 py-4 rounded-xl font-bold text-lg transition-all',
                                    currentAnswer === 'yes'
                                        ? 'bg-green-600 text-white scale-105 shadow-lg'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-green-100 dark:hover:bg-green-900/30'
                                ]"
                            >
                                Ha
                            </button>
                            <button
                                @click="selectChoiceAnswer('no')"
                                :class="[
                                    'px-12 py-4 rounded-xl font-bold text-lg transition-all',
                                    currentAnswer === 'no'
                                        ? 'bg-red-600 text-white scale-105 shadow-lg'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-red-100 dark:hover:bg-red-900/30'
                                ]"
                            >
                                Yo'q
                            </button>
                        </div>

                        <!-- Answer Input - Text -->
                        <div v-else-if="currentQuestion?.type === 'text'" class="mt-6">
                            <textarea
                                v-model="currentAnswer"
                                rows="4"
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none"
                                placeholder="Javobingizni yozing..."
                            ></textarea>
                        </div>

                        <!-- Error Message -->
                        <div v-if="errorMessage" class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-red-600 dark:text-red-400 text-sm">{{ errorMessage }}</p>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between">
                        <button
                            @click="prevQuestion"
                            :disabled="currentQuestionIndex === 0"
                            :class="[
                                'flex items-center gap-2 px-4 py-2 rounded-lg transition-colors',
                                currentQuestionIndex === 0
                                    ? 'text-gray-400 cursor-not-allowed'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                            ]"
                        >
                            <ChevronLeftIcon class="w-5 h-5" />
                            Oldingi
                        </button>

                        <button
                            v-if="currentQuestionIndex < totalQuestions - 1"
                            @click="nextQuestion"
                            :disabled="!canGoNext"
                            :class="[
                                'flex items-center gap-2 px-6 py-2 rounded-lg font-medium transition-colors',
                                canGoNext
                                    ? 'bg-purple-600 text-white hover:bg-purple-700'
                                    : 'bg-gray-300 dark:bg-gray-600 text-gray-500 cursor-not-allowed'
                            ]"
                        >
                            Keyingi
                            <ChevronRightIcon class="w-5 h-5" />
                        </button>

                        <button
                            v-else
                            @click="submitSurvey"
                            :disabled="!canSubmit || submitting"
                            :class="[
                                'flex items-center gap-2 px-6 py-2 rounded-lg font-medium transition-colors',
                                canSubmit && !submitting
                                    ? 'bg-green-600 text-white hover:bg-green-700'
                                    : 'bg-gray-300 dark:bg-gray-600 text-gray-500 cursor-not-allowed'
                            ]"
                        >
                            <ArrowPathIcon v-if="submitting" class="w-5 h-5 animate-spin" />
                            <CheckCircleIcon v-else class="w-5 h-5" />
                            {{ submitting ? 'Yuborilmoqda...' : 'Yuborish' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </HRLayout>
</template>
