<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import {
    ArrowLeftIcon,
    ArrowRightIcon,
    CheckIcon,
    SparklesIcon,
    LightBulbIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    dreamBuyer: {
        type: Object,
        default: null,
    },
    isEdit: {
        type: Boolean,
        default: false,
    },
});

const currentStep = ref(1);
const totalSteps = 9;
const generatingProfile = ref(false);

const form = useForm({
    name: props.dreamBuyer?.name || '',
    description: props.dreamBuyer?.description || '',
    where_spend_time: props.dreamBuyer?.where_spend_time || '',
    info_sources: props.dreamBuyer?.info_sources || '',
    frustrations: props.dreamBuyer?.frustrations || '',
    dreams: props.dreamBuyer?.dreams || '',
    fears: props.dreamBuyer?.fears || '',
    communication_preferences: props.dreamBuyer?.communication_preferences || '',
    language_style: props.dreamBuyer?.language_style || '',
    daily_routine: props.dreamBuyer?.daily_routine || '',
    happiness_triggers: props.dreamBuyer?.happiness_triggers || '',
    priority: props.dreamBuyer?.priority || 'medium',
    is_primary: props.dreamBuyer?.is_primary || false,
    generate_profile: false,
});

const steps = [
    {
        number: 1,
        title: 'Asosiy Ma\'lumot',
        description: 'Ideal Mijoz profili nomi va tavsifi',
        field: 'name',
        label: 'Profil Nomi',
        placeholder: 'Masalan: Tashvishli Ona Sabina, Muvaffaqiyatga Intiluvchi Jasur',
        hint: 'Ikki-uch so\'zdan iborat aniq nom bering. Keyinchalik AI o\'zi ham nom taklif qiladi.',
        icon: '👤'
    },
    {
        number: 2,
        title: 'Vaqt va Joy',
        description: 'Qayerda vaqt o\'tkazadi?',
        field: 'where_spend_time',
        label: 'Ularni qayerda topishingiz mumkin?',
        placeholder: 'Instagram, Facebook, LinkedIn, offline tadbirlar, ofisda, uyda...',
        hint: 'Ideal mijozlaringiz ko\'p vaqt o\'tkazadigan joylar, platformalar va manzillar.',
        icon: '📍'
    },
    {
        number: 3,
        title: 'Ma\'lumot Manbalari',
        description: 'Ma\'lumot olish uchun qayerga murojaat qiladi?',
        field: 'info_sources',
        label: 'Qaror qabul qilishdan oldin qayerdan ma\'lumot olishadi?',
        placeholder: 'Google, YouTube, mutaxassis maslahat, do\'stlar tavsiyasi, bloglar...',
        hint: 'Ular o\'z muammolari yoki ehtiyojlari haqida ma\'lumot qidirish uchun qanday manbalardan foydalanadilar?',
        icon: '🔍'
    },
    {
        number: 4,
        title: 'Frustratsiyalar',
        description: 'Eng katta frustratsiyalari va qiyinchiliklari?',
        field: 'frustrations',
        label: 'Qanday muammolar ularni bezovta qiladi?',
        placeholder: 'Vaqt yetishmasligi, natijalarga erisha olmaslik, pul isrof qilish...',
        hint: 'Ularning kundalik hayotidagi asosiy muammolar, qiyinchiliklar va frustatsiyalar.',
        icon: '😤'
    },
    {
        number: 5,
        title: 'Orzular',
        description: 'Orzulari va umidlari?',
        field: 'dreams',
        label: 'Ular nimaga erishishni xohlashadi?',
        placeholder: 'Moliyaviy erkinlik, ko\'proq vaqt, muvaffaqiyatli biznes, sog\'lom hayot...',
        hint: 'Ularning eng katta orzulari va maqsadlari. Nima uchun ular sizning mahsulotingizga muhtoj?',
        icon: '✨'
    },
    {
        number: 6,
        title: 'Qo\'rquvlar',
        description: 'Eng katta qo\'rquvlari?',
        field: 'fears',
        label: 'Nima ularni tashvishga soladi?',
        placeholder: 'Muvaffaqiyatsizlik, pul yo\'qotish, noto\'g\'ri qaror qabul qilish, vaqt isrof qilish...',
        hint: 'Ularning xarid qilishdan oldingi qo\'rquvlari va e\'tirozlari.',
        icon: '😰'
    },
    {
        number: 7,
        title: 'Kommunikatsiya',
        description: 'Qaysi kommunikatsiya shaklini afzal ko\'radi?',
        field: 'communication_preferences',
        label: 'Ular qanday muloqotni yoqtirishadi?',
        placeholder: 'Video qo\'ng\'iroq, matn, email, ijtimoiy tarmoq, yuzma-yuz uchrashuv...',
        hint: 'Qaysi kanallar va uslublar orqali ular bilan bog\'lanish yaxshiroq?',
        icon: '💬'
    },
    {
        number: 8,
        title: 'Til va Jargon',
        description: 'Qanday til va jargon ishlatadi?',
        field: 'language_style',
        label: 'Ular qanday gaplashadi?',
        placeholder: 'Rasmiy, do\'stona, hissiyotli, mantiqiy, texnik, oddiy...',
        hint: 'Ularning til uslubi, ishlatiladigan so\'zlar va iboralar.',
        icon: '🗣️'
    },
    {
        number: 9,
        title: 'Kundalik Hayot',
        description: 'Kundalik hayoti qanday? Nima baxtli qiladi?',
        field: 'daily_routine',
        label: 'Tipik kunlari qanday o\'tadi?',
        placeholder: 'Erta turish, ish, oila, mashg\'ulotlar, dam olish...',
        hint: 'Ularning odatiy kuni va baxtli qiladigan narsalar.',
        icon: '📅'
    }
];

const currentStepData = computed(() => steps[currentStep.value - 1]);

const canGoNext = computed(() => {
    if (currentStep.value === 1) {
        return form.name.trim().length > 0;
    }
    const field = currentStepData.value.field;
    return form[field] && form[field].trim().length > 0;
});

const progressPercentage = computed(() => {
    return Math.round((currentStep.value / totalSteps) * 100);
});

const nextStep = () => {
    if (currentStep.value < totalSteps && canGoNext.value) {
        currentStep.value++;
    }
};

const previousStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const goToStep = (step) => {
    currentStep.value = step;
};

const submit = (withAI = false) => {
    form.generate_profile = withAI;

    if (props.isEdit) {
        form.put(route('business.dream-buyer.update', props.dreamBuyer.id), {
            onSuccess: () => {
                router.visit(route('business.dream-buyer.show', props.dreamBuyer.id));
            }
        });
    } else {
        form.post(route('business.dream-buyer.store'), {
            onSuccess: (page) => {
                // Redirect handled by controller
            }
        });
    }
};

const submitWithAI = () => {
    generatingProfile.value = true;
    submit(true);
};

const submitWithoutAI = () => {
    submit(false);
};
</script>

<template>
    <BusinessLayout :title="isEdit ? 'Ideal Mijoz Tahrirlash' : 'Yangi Ideal Mijoz'">
        <Head :title="isEdit ? 'Ideal Mijoz Tahrirlash' : 'Yangi Ideal Mijoz'" />

        <div class="py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('business.dream-buyer.index')"
                        class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4"
                    >
                        <ArrowLeftIcon class="w-4 h-4 mr-1" />
                        Orqaga
                    </Link>
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ isEdit ? 'Ideal Mijoz Tahrirlash' : 'Yangi Ideal Mijoz Yaratish' }}
                    </h1>
                    <p class="mt-2 text-gray-600">
                        "Sell Like Crazy" metodologiyasi asosida 9 ta savolga javob bering
                    </p>
                </div>

                <!-- Progress Bar -->
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">
                            Qadam {{ currentStep }} / {{ totalSteps }}
                        </span>
                        <span class="text-sm font-medium text-indigo-600">
                            {{ progressPercentage }}%
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div
                            class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                            :style="{ width: progressPercentage + '%' }"
                        ></div>
                    </div>

                    <!-- Step Indicators -->
                    <div class="flex justify-between mt-4">
                        <button
                            v-for="step in steps"
                            :key="step.number"
                            @click="goToStep(step.number)"
                            class="flex flex-col items-center gap-1 group"
                            :class="{ 'opacity-50': step.number > currentStep }"
                        >
                            <div
                                class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                                :class="step.number <= currentStep
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-200 text-gray-600'"
                            >
                                <CheckIcon v-if="step.number < currentStep" class="w-4 h-4" />
                                <span v-else>{{ step.number }}</span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Main Form Card -->
                <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                    <!-- Step Icon & Title -->
                    <div class="text-center mb-8">
                        <div class="text-6xl mb-4">{{ currentStepData.icon }}</div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">
                            {{ currentStepData.title }}
                        </h2>
                        <p class="text-gray-600">
                            {{ currentStepData.description }}
                        </p>
                    </div>

                    <!-- Form Field -->
                    <div v-if="currentStep === 1">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ currentStepData.label }} *
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    :placeholder="currentStepData.placeholder"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    required
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.name }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Qisqa Tavsif (ixtiyoriy)
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Bu Ideal Mijoz haqida qisqacha..."
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                ></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Prioritet
                                    </label>
                                    <select
                                        v-model="form.priority"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    >
                                        <option value="low">Past</option>
                                        <option value="medium">O'rta</option>
                                        <option value="high">Yuqori</option>
                                    </select>
                                </div>
                                <div class="flex items-center">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input
                                            v-model="form.is_primary"
                                            type="checkbox"
                                            class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                        />
                                        <span class="text-sm font-medium text-gray-700">Primary qilish</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="currentStep === 9">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Kundalik Hayoti *
                                </label>
                                <textarea
                                    v-model="form.daily_routine"
                                    rows="5"
                                    :placeholder="currentStepData.placeholder"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    required
                                ></textarea>
                                <p v-if="form.errors.daily_routine" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.daily_routine }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Nima uni baxtli qiladi? *
                                </label>
                                <textarea
                                    v-model="form.happiness_triggers"
                                    rows="5"
                                    placeholder="Oila bilan vaqt, muvaffaqiyat, e'tirof, dam olish..."
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    required
                                ></textarea>
                                <p v-if="form.errors.happiness_triggers" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.happiness_triggers }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ currentStepData.label }} *
                            </label>
                            <textarea
                                v-model="form[currentStepData.field]"
                                rows="8"
                                :placeholder="currentStepData.placeholder"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                required
                            ></textarea>
                            <p v-if="form.errors[currentStepData.field]" class="mt-1 text-sm text-red-600">
                                {{ form.errors[currentStepData.field] }}
                            </p>
                        </div>
                    </div>

                    <!-- Hint -->
                    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg flex items-start gap-3">
                        <LightBulbIcon class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                        <p class="text-sm text-blue-800">
                            <strong>Maslahat:</strong> {{ currentStepData.hint }}
                        </p>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex items-center justify-between">
                    <button
                        v-if="currentStep > 1"
                        @click="previousStep"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white border-2 border-gray-300 hover:border-gray-400 text-gray-700 font-semibold rounded-lg transition-all"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                        Orqaga
                    </button>
                    <div v-else></div>

                    <!-- Next or Submit -->
                    <div class="flex gap-3">
                        <button
                            v-if="currentStep < totalSteps"
                            @click="nextStep"
                            :disabled="!canGoNext"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-all"
                        >
                            Keyingisi
                            <ArrowRightIcon class="w-5 h-5" />
                        </button>

                        <!-- Submit Buttons (Step 9) -->
                        <template v-else>
                            <button
                                @click="submitWithoutAI"
                                :disabled="form.processing || !canGoNext"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-all"
                            >
                                <CheckIcon class="w-5 h-5" />
                                AI-siz Saqlash
                            </button>
                            <button
                                @click="submitWithAI"
                                :disabled="form.processing || generatingProfile || !canGoNext"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 disabled:from-gray-300 disabled:to-gray-400 disabled:cursor-not-allowed text-white font-semibold rounded-lg shadow-lg transition-all"
                            >
                                <SparklesIcon class="w-5 h-5" />
                                <span v-if="generatingProfile">AI Profil Yaratmoqda...</span>
                                <span v-else>AI Profil Yaratish</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
