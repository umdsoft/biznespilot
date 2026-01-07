<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    survey: Object,
    questions: Array,
    responseId: String,
});

const currentStep = ref(props.survey.collect_contact ? 0 : 1);
const totalSteps = computed(() => props.questions.length + (props.survey.collect_contact ? 1 : 0));
const startTime = ref(null);
const questionStartTime = ref(null);
const isLoading = ref(false);
const responseId = ref(props.responseId || null);
const isStarted = ref(false);
const slideDirection = ref('next');

const contactForm = ref({
    name: '',
    phone: '',
    region: '',
});

const regions = [
    'Toshkent shahri',
    'Toshkent viloyati',
    'Andijon viloyati',
    'Buxoro viloyati',
    'Farg\'ona viloyati',
    'Jizzax viloyati',
    'Xorazm viloyati',
    'Namangan viloyati',
    'Navoiy viloyati',
    'Qashqadaryo viloyati',
    'Qoraqalpog\'iston Respublikasi',
    'Samarqand viloyati',
    'Sirdaryo viloyati',
    'Surxondaryo viloyati',
];

const answers = ref({});

// Initialize answers
onMounted(() => {
    startTime.value = Date.now();
    questionStartTime.value = Date.now();
    props.questions.forEach(q => {
        if (q.type === 'multiselect') {
            answers.value[q.id] = [];
        } else if (q.type === 'rating') {
            answers.value[q.id] = 0;
        } else if (q.type === 'scale') {
            answers.value[q.id] = 5;
        } else {
            answers.value[q.id] = '';
        }
    });
});

const currentQuestion = computed(() => {
    if (props.survey.collect_contact && currentStep.value === 0) {
        return null;
    }
    const questionIndex = props.survey.collect_contact ? currentStep.value - 1 : currentStep.value;
    return props.questions[questionIndex] || null;
});

const progress = computed(() => {
    return Math.round((currentStep.value / totalSteps.value) * 100);
});

const canProceed = computed(() => {
    if (props.survey.collect_contact && currentStep.value === 0) {
        return contactForm.value.name.trim() !== '';
    }

    if (!currentQuestion.value) return true;

    if (!currentQuestion.value.is_required) return true;

    const answer = answers.value[currentQuestion.value.id];

    if (currentQuestion.value.type === 'multiselect') {
        return answer && answer.length > 0;
    }

    if (currentQuestion.value.type === 'rating') {
        return answer > 0;
    }

    if (currentQuestion.value.type === 'scale') {
        return true;
    }

    return answer && answer.toString().trim() !== '';
});

const getTimeSpent = () => {
    return Math.round((Date.now() - startTime.value) / 1000);
};

const getQuestionTimeSpent = () => {
    return Math.round((Date.now() - questionStartTime.value) / 1000);
};

const startSurvey = async () => {
    if (isLoading.value) return;
    isLoading.value = true;

    try {
        const response = await fetch(route('survey.start', props.survey.slug), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            body: JSON.stringify({
                respondent_name: contactForm.value.name,
                respondent_phone: contactForm.value.phone,
                respondent_region: contactForm.value.region,
            }),
        });

        if (!response.ok) {
            let errorMessage = 'Xatolik yuz berdi';
            try {
                const errorData = await response.json();
                console.error('Server error:', response.status, errorData);
                errorMessage = errorData.error || errorMessage;
            } catch {
                const errorText = await response.text();
                console.error('Server error:', response.status, errorText);
            }
            alert(errorMessage);
            return;
        }

        const data = await response.json();
        console.log('Start survey response:', data);

        if (data.response_id) {
            responseId.value = data.response_id;
            isStarted.value = true;
            slideDirection.value = 'next';
            currentStep.value++;
            questionStartTime.value = Date.now();
        } else if (data.error) {
            console.error('API error:', data.error);
            alert(data.error);
        }
    } catch (error) {
        console.error('Error starting survey:', error);
        alert('Tarmoq xatosi yuz berdi. Iltimos qayta urinib ko\'ring.');
    } finally {
        isLoading.value = false;
    }
};

const nextQuestion = async () => {
    if (isLoading.value || !canProceed.value) return;
    isLoading.value = true;

    try {
        if (currentQuestion.value && responseId.value) {
            const answer = answers.value[currentQuestion.value.id];

            await fetch(route('survey.answer', props.survey.slug), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
                body: JSON.stringify({
                    response_id: responseId.value,
                    question_id: currentQuestion.value.id,
                    answer: currentQuestion.value.type === 'text' || currentQuestion.value.type === 'textarea' ? answer : null,
                    selected_options: currentQuestion.value.type === 'select' ? [answer] : currentQuestion.value.type === 'multiselect' ? answer : null,
                    rating_value: currentQuestion.value.type === 'rating' || currentQuestion.value.type === 'scale' ? answer : null,
                    time_spent: getQuestionTimeSpent(),
                }),
            });
        }

        if (currentStep.value < totalSteps.value - 1) {
            slideDirection.value = 'next';
            currentStep.value++;
            questionStartTime.value = Date.now();
        } else {
            completeSurvey();
        }
    } catch (error) {
        console.error('Error saving answer:', error);
    } finally {
        isLoading.value = false;
    }
};

const completeSurvey = async () => {
    if (!responseId.value) return;

    try {
        await fetch(route('survey.complete', props.survey.slug), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            body: JSON.stringify({
                response_id: responseId.value,
                respondent_name: contactForm.value.name,
                respondent_phone: contactForm.value.phone,
                respondent_region: contactForm.value.region,
                total_time: getTimeSpent(),
            }),
        });

        window.location.href = route('survey.thank-you', props.survey.slug);
    } catch (error) {
        console.error('Error completing survey:', error);
    }
};

const prevQuestion = () => {
    if (currentStep.value > (props.survey.collect_contact ? 1 : 0)) {
        slideDirection.value = 'prev';
        currentStep.value--;
        questionStartTime.value = Date.now();
    }
};

const toggleMultiSelect = (option) => {
    if (!currentQuestion.value) return;
    const arr = answers.value[currentQuestion.value.id];
    const index = arr.indexOf(option);
    if (index === -1) {
        arr.push(option);
    } else {
        arr.splice(index, 1);
    }
};

const setRating = (value) => {
    if (currentQuestion.value) {
        answers.value[currentQuestion.value.id] = value;
    }
};

const getCategoryEmoji = (category) => {
    const emojis = {
        where_spend_time: '📍',
        info_sources: '📚',
        frustrations: '😤',
        dreams: '✨',
        fears: '😰',
        communication_preferences: '💬',
        daily_routine: '🌅',
        happiness_triggers: '😊',
        satisfaction: '⭐',
        custom: '💭',
    };
    return emojis[category] || emojis.custom;
};

const themeColor = computed(() => props.survey.theme_color || '#10B981');
</script>

<template>
    <Head :title="survey.title" />

    <div class="min-h-screen relative overflow-hidden">
        <!-- Animated Background -->
        <div class="fixed inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900"></div>
            <div class="absolute top-0 left-0 w-full h-full">
                <div class="absolute top-1/4 left-1/4 w-96 h-96 rounded-full blur-3xl opacity-20 animate-pulse" :style="{ backgroundColor: themeColor }"></div>
                <div class="absolute bottom-1/4 right-1/4 w-96 h-96 rounded-full blur-3xl opacity-10 animate-pulse delay-1000" :style="{ backgroundColor: themeColor }"></div>
            </div>
            <!-- Grid Pattern -->
            <div class="absolute inset-0 opacity-5" style="background-image: linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px); background-size: 50px 50px;"></div>
        </div>

        <!-- Header -->
        <header class="relative z-10 pt-6 pb-4 px-4">
            <div class="max-w-2xl mx-auto">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-lg" :style="{ backgroundColor: themeColor }">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-white font-semibold">{{ survey.title }}</h1>
                            <p v-if="survey.business_name" class="text-slate-400 text-sm">{{ survey.business_name }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-slate-400 text-sm font-medium">
                            {{ Math.min(currentStep + 1, totalSteps) }} / {{ totalSteps }}
                        </span>
                        <div class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-800/50 backdrop-blur-sm border border-slate-700">
                            <svg class="w-5 h-5" :style="{ color: themeColor }" viewBox="0 0 36 36">
                                <circle cx="18" cy="18" r="16" fill="none" stroke="currentColor" stroke-opacity="0.2" stroke-width="3"/>
                                <circle cx="18" cy="18" r="16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                    :stroke-dasharray="100"
                                    :stroke-dashoffset="100 - progress"
                                    transform="rotate(-90 18 18)"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="h-1.5 bg-slate-800/50 backdrop-blur-sm rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500 ease-out"
                        :style="{ width: `${progress}%`, backgroundColor: themeColor }"
                    ></div>
                </div>
            </div>
        </header>

        <main class="relative z-10 px-4 py-8">
            <div class="max-w-2xl mx-auto">
                <!-- Welcome Step -->
                <Transition
                    :name="slideDirection === 'next' ? 'slide-left' : 'slide-right'"
                    mode="out-in"
                >
                    <div v-if="currentStep === 0 && survey.collect_contact" key="welcome" class="relative">
                        <div class="bg-slate-800/40 backdrop-blur-xl rounded-3xl border border-slate-700/50 shadow-2xl overflow-hidden">
                            <!-- Card Header -->
                            <div class="relative px-8 pt-10 pb-8 text-center">
                                <div class="absolute inset-0 opacity-50" :style="{ background: `linear-gradient(135deg, ${themeColor}15 0%, transparent 50%)` }"></div>
                                <div class="relative">
                                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-xl" :style="{ backgroundColor: themeColor }">
                                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                    <h2 class="text-3xl font-bold text-white mb-3">{{ survey.title }}</h2>
                                    <p class="text-slate-300 text-lg max-w-md mx-auto">
                                        {{ survey.welcome_message || 'Sizning fikringiz biz uchun juda muhim!' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Form -->
                            <div class="px-8 pb-8 space-y-5">
                                <div class="group">
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-300 mb-2">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        Ismingiz
                                        <span class="text-red-400">*</span>
                                    </label>
                                    <input
                                        v-model="contactForm.name"
                                        type="text"
                                        class="w-full px-5 py-4 bg-slate-900/50 border-2 border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-opacity-100 transition-all text-lg"
                                        :style="{ '--focus-color': themeColor }"
                                        :class="{ 'border-opacity-100': contactForm.name }"
                                        placeholder="Ismingizni kiriting"
                                        @focus="$event.target.style.borderColor = themeColor"
                                        @blur="$event.target.style.borderColor = contactForm.name ? themeColor : ''"
                                    />
                                </div>

                                <div class="group">
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-300 mb-2">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        Telefon raqamingiz
                                    </label>
                                    <input
                                        v-model="contactForm.phone"
                                        type="tel"
                                        class="w-full px-5 py-4 bg-slate-900/50 border-2 border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none transition-all text-lg"
                                        placeholder="+998 90 123 45 67"
                                        @focus="$event.target.style.borderColor = themeColor"
                                        @blur="$event.target.style.borderColor = ''"
                                    />
                                </div>

                                <div class="group">
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-300 mb-2">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Hududingiz
                                    </label>
                                    <select
                                        v-model="contactForm.region"
                                        class="w-full px-5 py-4 bg-slate-900/50 border-2 border-slate-700 rounded-xl text-white focus:outline-none transition-all text-lg appearance-none cursor-pointer select-arrow"
                                        @focus="$event.target.style.borderColor = themeColor"
                                        @blur="$event.target.style.borderColor = ''"
                                    >
                                        <option value="" disabled class="bg-slate-800 text-slate-400">Hududingizni tanlang</option>
                                        <option v-for="region in regions" :key="region" :value="region" class="bg-slate-800 text-white">
                                            {{ region }}
                                        </option>
                                    </select>
                                </div>

                                <button
                                    @click="startSurvey"
                                    :disabled="!canProceed || isLoading"
                                    class="w-full mt-4 py-5 text-white font-bold text-lg rounded-xl shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3 hover:scale-[1.02] active:scale-[0.98]"
                                    :style="{ backgroundColor: themeColor, boxShadow: `0 10px 40px -10px ${themeColor}80` }"
                                >
                                    <span v-if="isLoading" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                    <span>{{ isLoading ? 'Yuklanmoqda...' : 'Boshlash' }}</span>
                                    <svg v-if="!isLoading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Question Steps -->
                    <div v-else-if="currentQuestion" :key="currentStep" class="relative">
                        <div class="bg-slate-800/40 backdrop-blur-xl rounded-3xl border border-slate-700/50 shadow-2xl overflow-hidden">
                            <!-- Question Header -->
                            <div class="relative px-8 pt-8 pb-6">
                                <div class="absolute inset-0 opacity-30" :style="{ background: `linear-gradient(135deg, ${themeColor}15 0%, transparent 50%)` }"></div>
                                <div class="relative">
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-3xl shadow-lg" :style="{ backgroundColor: themeColor + '20' }">
                                            {{ getCategoryEmoji(currentQuestion.category) }}
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-slate-400 text-sm font-medium">
                                                    Savol {{ props.survey.collect_contact ? currentStep : currentStep + 1 }}
                                                </span>
                                                <span v-if="currentQuestion.is_required" class="px-2 py-0.5 bg-red-500/20 text-red-400 text-xs font-medium rounded-full">Majburiy</span>
                                            </div>
                                        </div>
                                    </div>
                                    <h2 class="text-2xl font-bold text-white leading-relaxed">
                                        {{ currentQuestion.question }}
                                    </h2>
                                </div>
                            </div>

                            <!-- Answer Section -->
                            <div class="px-8 pb-8">
                                <!-- Text Input -->
                                <div v-if="currentQuestion.type === 'text'" class="space-y-4">
                                    <input
                                        v-model="answers[currentQuestion.id]"
                                        type="text"
                                        class="w-full px-5 py-4 bg-slate-900/50 border-2 border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none transition-all text-lg"
                                        placeholder="Javobingizni yozing..."
                                        @focus="$event.target.style.borderColor = themeColor"
                                        @blur="$event.target.style.borderColor = answers[currentQuestion.id] ? themeColor : ''"
                                    />
                                </div>

                                <!-- Textarea -->
                                <div v-else-if="currentQuestion.type === 'textarea'" class="space-y-4">
                                    <textarea
                                        v-model="answers[currentQuestion.id]"
                                        rows="5"
                                        class="w-full px-5 py-4 bg-slate-900/50 border-2 border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none transition-all text-lg resize-none"
                                        placeholder="Javobingizni batafsil yozing..."
                                        @focus="$event.target.style.borderColor = themeColor"
                                        @blur="$event.target.style.borderColor = answers[currentQuestion.id] ? themeColor : ''"
                                    ></textarea>
                                </div>

                                <!-- Select (Single Choice) -->
                                <div v-else-if="currentQuestion.type === 'select'" class="space-y-3">
                                    <button
                                        v-for="(option, index) in currentQuestion.options"
                                        :key="option"
                                        @click="answers[currentQuestion.id] = option"
                                        class="w-full p-4 rounded-xl text-left transition-all flex items-center gap-4 group"
                                        :class="answers[currentQuestion.id] === option
                                            ? 'bg-opacity-20 border-2'
                                            : 'bg-slate-900/30 border-2 border-slate-700 hover:border-slate-600'"
                                        :style="answers[currentQuestion.id] === option
                                            ? { backgroundColor: themeColor + '20', borderColor: themeColor }
                                            : {}"
                                    >
                                        <div
                                            class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all"
                                            :style="answers[currentQuestion.id] === option
                                                ? { borderColor: themeColor, backgroundColor: themeColor }
                                                : { borderColor: '#475569' }"
                                        >
                                            <div v-if="answers[currentQuestion.id] === option" class="w-2 h-2 rounded-full bg-white"></div>
                                        </div>
                                        <span class="text-white font-medium">{{ option }}</span>
                                    </button>
                                </div>

                                <!-- Multiselect -->
                                <div v-else-if="currentQuestion.type === 'multiselect'" class="space-y-3">
                                    <button
                                        v-for="option in currentQuestion.options"
                                        :key="option"
                                        @click="toggleMultiSelect(option)"
                                        class="w-full p-4 rounded-xl text-left transition-all flex items-center gap-4"
                                        :class="answers[currentQuestion.id]?.includes(option)
                                            ? 'bg-opacity-20 border-2'
                                            : 'bg-slate-900/30 border-2 border-slate-700 hover:border-slate-600'"
                                        :style="answers[currentQuestion.id]?.includes(option)
                                            ? { backgroundColor: themeColor + '20', borderColor: themeColor }
                                            : {}"
                                    >
                                        <div
                                            class="w-6 h-6 rounded-lg border-2 flex items-center justify-center transition-all"
                                            :style="answers[currentQuestion.id]?.includes(option)
                                                ? { borderColor: themeColor, backgroundColor: themeColor }
                                                : { borderColor: '#475569' }"
                                        >
                                            <svg v-if="answers[currentQuestion.id]?.includes(option)" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <span class="text-white font-medium">{{ option }}</span>
                                    </button>
                                    <p class="text-center text-slate-400 text-sm mt-4">
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Bir nechta variant tanlashingiz mumkin
                                        </span>
                                    </p>
                                </div>

                                <!-- Rating (1-5 stars) -->
                                <div v-else-if="currentQuestion.type === 'rating'" class="py-6">
                                    <div class="flex justify-center gap-4">
                                        <button
                                            v-for="n in 5"
                                            :key="n"
                                            @click="setRating(n)"
                                            class="p-2 transition-all duration-200 hover:scale-110 active:scale-95"
                                        >
                                            <svg
                                                class="w-14 h-14 transition-all duration-200"
                                                :style="{ color: n <= answers[currentQuestion.id] ? themeColor : '#475569', filter: n <= answers[currentQuestion.id] ? `drop-shadow(0 0 8px ${themeColor}60)` : 'none' }"
                                                :fill="n <= answers[currentQuestion.id] ? 'currentColor' : 'none'"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="text-center mt-6">
                                        <span class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900/50 rounded-full">
                                            <span class="text-2xl font-bold" :style="{ color: themeColor }">{{ answers[currentQuestion.id] || 0 }}</span>
                                            <span class="text-slate-400">/ 5</span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Scale (1-10) -->
                                <div v-else-if="currentQuestion.type === 'scale'" class="py-6">
                                    <div class="grid grid-cols-10 gap-2">
                                        <button
                                            v-for="n in 10"
                                            :key="n"
                                            @click="setRating(n)"
                                            class="aspect-square rounded-xl font-bold text-lg transition-all duration-200 hover:scale-105 active:scale-95"
                                            :class="n === answers[currentQuestion.id] ? 'text-white shadow-lg' : 'bg-slate-900/50 text-slate-400 hover:text-white'"
                                            :style="n === answers[currentQuestion.id]
                                                ? { backgroundColor: themeColor, boxShadow: `0 4px 20px -4px ${themeColor}80` }
                                                : {}"
                                        >
                                            {{ n }}
                                        </button>
                                    </div>
                                    <div class="flex justify-between mt-4 text-sm text-slate-500">
                                        <span>Umuman yo'q</span>
                                        <span>Juda ko'p</span>
                                    </div>
                                </div>

                                <!-- Navigation -->
                                <div class="flex items-center justify-between mt-8 pt-6 border-t border-slate-700/50">
                                    <button
                                        @click="prevQuestion"
                                        :class="currentStep > (survey.collect_contact ? 1 : 0)
                                            ? 'text-slate-400 hover:text-white'
                                            : 'invisible'"
                                        class="inline-flex items-center gap-2 px-4 py-3 font-medium transition-colors rounded-xl hover:bg-slate-800/50"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                        </svg>
                                        Orqaga
                                    </button>

                                    <button
                                        @click="nextQuestion"
                                        :disabled="!canProceed || isLoading"
                                        class="inline-flex items-center gap-3 px-8 py-4 text-white font-bold rounded-xl shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed hover:scale-[1.02] active:scale-[0.98]"
                                        :style="{ backgroundColor: themeColor, boxShadow: `0 10px 40px -10px ${themeColor}80` }"
                                    >
                                        <span v-if="isLoading" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                        <span>{{ currentStep === totalSteps - 1 ? 'Tugatish' : 'Keyingi' }}</span>
                                        <svg v-if="!isLoading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </main>

        <!-- Footer -->
        <footer class="relative z-10 py-8 text-center">
            <p class="text-slate-500 text-sm flex items-center justify-center gap-2">
                <span class="w-5 h-5 rounded flex items-center justify-center" :style="{ backgroundColor: themeColor + '30' }">
                    <svg class="w-3 h-3" :style="{ color: themeColor }" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </span>
                BiznesPilot AI bilan yaratilgan
            </p>
        </footer>
    </div>
</template>

<style scoped>
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
    transition: all 0.3s ease-out;
}

.slide-left-enter-from {
    opacity: 0;
    transform: translateX(30px);
}

.slide-left-leave-to {
    opacity: 0;
    transform: translateX(-30px);
}

.slide-right-enter-from {
    opacity: 0;
    transform: translateX(-30px);
}

.slide-right-leave-to {
    opacity: 0;
    transform: translateX(30px);
}

@keyframes pulse {
    0%, 100% { opacity: 0.1; }
    50% { opacity: 0.2; }
}

.animate-pulse {
    animation: pulse 4s ease-in-out infinite;
}

.delay-1000 {
    animation-delay: 1s;
}

.select-arrow {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 1rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
}
</style>
