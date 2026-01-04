<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';

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
        icon: '👤',
        color: 'indigo',
        quickTags: []
    },
    {
        number: 2,
        title: 'Vaqt va Joy',
        description: 'Qayerda vaqt o\'tkazadi?',
        field: 'where_spend_time',
        label: 'Ularni qayerda topishingiz mumkin?',
        placeholder: 'Instagram, Facebook, LinkedIn, offline tadbirlar, ofisda, uyda...',
        hint: 'Ideal mijozlaringiz ko\'p vaqt o\'tkazadigan joylar, platformalar va manzillar.',
        icon: '📍',
        color: 'emerald',
        quickTags: ['Instagram', 'Facebook', 'LinkedIn', 'YouTube', 'Telegram', 'TikTok', 'Offline tadbirlar', 'Ko\'cha reklama']
    },
    {
        number: 3,
        title: 'Ma\'lumot Manbalari',
        description: 'Ma\'lumot olish uchun qayerga murojaat qiladi?',
        field: 'info_sources',
        label: 'Qaror qabul qilishdan oldin qayerdan ma\'lumot olishadi?',
        placeholder: 'Google, YouTube, mutaxassis maslahat, do\'stlar tavsiyasi, bloglar...',
        hint: 'Ular o\'z muammolari yoki ehtiyojlari haqida ma\'lumot qidirish uchun qanday manbalardan foydalanadilar?',
        icon: '🔍',
        color: 'blue',
        quickTags: ['Google', 'YouTube', 'Telegram kanallar', 'Do\'stlar tavsiyasi', 'Ekspert maslahati', 'Bloglar', 'Podkastlar']
    },
    {
        number: 4,
        title: 'Frustratsiyalar',
        description: 'Eng katta frustratsiyalari va qiyinchiliklari?',
        field: 'frustrations',
        label: 'Qanday muammolar ularni bezovta qiladi?',
        placeholder: 'Vaqt yetishmasligi, natijalarga erisha olmaslik, pul isrof qilish...',
        hint: 'Ularning kundalik hayotidagi asosiy muammolar, qiyinchiliklar va frustatsiyalar.',
        icon: '😤',
        color: 'red',
        quickTags: ['Vaqt yetishmasligi', 'Pul yetishmasligi', 'Bilim yetishmasligi', 'Natija yo\'qligi', 'Stress', 'Ishonchsizlik']
    },
    {
        number: 5,
        title: 'Orzular',
        description: 'Orzulari va umidlari?',
        field: 'dreams',
        label: 'Ular nimaga erishishni xohlashadi?',
        placeholder: 'Moliyaviy erkinlik, ko\'proq vaqt, muvaffaqiyatli biznes, sog\'lom hayot...',
        hint: 'Ularning eng katta orzulari va maqsadlari. Nima uchun ular sizning mahsulotingizga muhtoj?',
        icon: '✨',
        color: 'amber',
        quickTags: ['Moliyaviy erkinlik', 'Ko\'proq vaqt', 'Muvaffaqiyatli biznes', 'Sog\'lom hayot', 'Oila baxti', 'Tan olinish']
    },
    {
        number: 6,
        title: 'Qo\'rquvlar',
        description: 'Eng katta qo\'rquvlari?',
        field: 'fears',
        label: 'Nima ularni tashvishga soladi?',
        placeholder: 'Muvaffaqiyatsizlik, pul yo\'qotish, noto\'g\'ri qaror qabul qilish, vaqt isrof qilish...',
        hint: 'Ularning xarid qilishdan oldingi qo\'rquvlari va e\'tirozlari.',
        icon: '😰',
        color: 'purple',
        quickTags: ['Muvaffaqiyatsizlik', 'Pul yo\'qotish', 'Vaqt isrof qilish', 'Aldanish', 'Noto\'g\'ri qaror', 'Tanqid qilinish']
    },
    {
        number: 7,
        title: 'Kommunikatsiya',
        description: 'Qaysi kommunikatsiya shaklini afzal ko\'radi?',
        field: 'communication_preferences',
        label: 'Ular qanday muloqotni yoqtirishadi?',
        placeholder: 'Video qo\'ng\'iroq, matn, email, ijtimoiy tarmoq, yuzma-yuz uchrashuv...',
        hint: 'Qaysi kanallar va uslublar orqali ular bilan bog\'lanish yaxshiroq?',
        icon: '💬',
        color: 'cyan',
        quickTags: ['Telefon qo\'ng\'iroq', 'Video uchrashuv', 'Telegram xabar', 'Email', 'Yuzma-yuz', 'Ijtimoiy tarmoq']
    },
    {
        number: 8,
        title: 'Til va Jargon',
        description: 'Qanday til va jargon ishlatadi?',
        field: 'language_style',
        label: 'Ular qanday gaplashadi?',
        placeholder: 'Rasmiy, do\'stona, hissiyotli, mantiqiy, texnik, oddiy...',
        hint: 'Ularning til uslubi, ishlatiladigan so\'zlar va iboralar.',
        icon: '🗣️',
        color: 'pink',
        quickTags: ['Rasmiy', 'Do\'stona', 'Hissiyotli', 'Mantiqiy', 'Texnik', 'Oddiy', 'Qisqa', 'Batafsil']
    },
    {
        number: 9,
        title: 'Kundalik Hayot',
        description: 'Kundalik hayoti qanday? Nima baxtli qiladi?',
        field: 'daily_routine',
        label: 'Tipik kunlari qanday o\'tadi?',
        placeholder: 'Erta turish, ish, oila, mashg\'ulotlar, dam olish...',
        hint: 'Ularning odatiy kuni va baxtli qiladigan narsalar.',
        icon: '📅',
        color: 'teal',
        quickTags: []
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

const progressColor = computed(() => {
    const progress = progressPercentage.value;
    if (progress < 33) return 'from-red-500 to-orange-500';
    if (progress < 66) return 'from-orange-500 to-yellow-500';
    if (progress < 90) return 'from-yellow-500 to-green-500';
    return 'from-green-500 to-emerald-500';
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

const addQuickTag = (tag, field) => {
    const currentValue = form[field] || '';
    if (currentValue.includes(tag)) return;
    form[field] = currentValue ? `${currentValue}, ${tag}` : tag;
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

        <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8 sm:py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('business.dream-buyer.index')"
                        class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors mb-4 group"
                    >
                        <svg class="w-4 h-4 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Orqaga qaytish
                    </Link>

                    <div class="flex items-center gap-4 mb-3">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                                {{ isEdit ? 'Ideal Mijozni Tahrirlash' : 'Yangi Ideal Mijoz Yaratish' }}
                            </h1>
                            <p class="text-gray-600 dark:text-gray-400">
                                "Sell Like Crazy" metodologiyasi asosida 9 ta savolga javob bering
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Progress Section -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">{{ currentStepData.icon }}</span>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white">{{ currentStepData.title }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Qadam {{ currentStep }} / {{ totalSteps }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                {{ progressPercentage }}%
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Tugallandi</p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="relative">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                            <div
                                class="h-3 rounded-full transition-all duration-500 ease-out bg-gradient-to-r"
                                :class="progressColor"
                                :style="{ width: progressPercentage + '%' }"
                            ></div>
                        </div>
                    </div>

                    <!-- Step Indicators -->
                    <div class="flex justify-between mt-6 px-1">
                        <button
                            v-for="step in steps"
                            :key="step.number"
                            @click="goToStep(step.number)"
                            class="flex flex-col items-center gap-1.5 group focus:outline-none"
                            :title="step.title"
                        >
                            <div
                                class="w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300 border-2"
                                :class="[
                                    step.number < currentStep
                                        ? 'bg-green-500 border-green-500 text-white shadow-lg shadow-green-500/30'
                                        : step.number === currentStep
                                            ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-500/30 scale-110'
                                            : 'bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500 group-hover:border-indigo-400 dark:group-hover:border-indigo-500'
                                ]"
                            >
                                <svg v-if="step.number < currentStep" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                                <span v-else>{{ step.number }}</span>
                            </div>
                            <span class="hidden sm:block text-xs text-gray-500 dark:text-gray-400 max-w-[60px] text-center truncate">
                                {{ step.title.split(' ')[0] }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Main Form Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
                    <!-- Step Header -->
                    <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 px-6 py-5">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center text-4xl">
                                {{ currentStepData.icon }}
                            </div>
                            <div class="text-white">
                                <h2 class="text-xl sm:text-2xl font-bold">{{ currentStepData.title }}</h2>
                                <p class="text-white/80">{{ currentStepData.description }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8">
                        <!-- Form Field - Step 1 -->
                        <div v-if="currentStep === 1" class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    {{ currentStepData.label }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    :placeholder="currentStepData.placeholder"
                                    class="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-400 dark:focus:border-indigo-400 transition-all"
                                    required
                                />
                                <p v-if="form.errors.name" class="mt-2 text-sm text-red-500 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Qisqa Tavsif <span class="text-gray-400">(ixtiyoriy)</span>
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Bu Ideal Mijoz haqida qisqacha..."
                                    class="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-400 dark:focus:border-indigo-400 transition-all resize-none"
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Prioritet
                                    </label>
                                    <select
                                        v-model="form.priority"
                                        class="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-400 dark:focus:border-indigo-400 transition-all"
                                    >
                                        <option value="low">🟢 Past</option>
                                        <option value="medium">🟡 O'rta</option>
                                        <option value="high">🔴 Yuqori</option>
                                    </select>
                                </div>
                                <div class="flex items-center">
                                    <label class="relative flex items-center gap-3 cursor-pointer p-3 bg-gray-50 dark:bg-gray-900 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-indigo-500 dark:hover:border-indigo-400 transition-all w-full">
                                        <input
                                            v-model="form.is_primary"
                                            type="checkbox"
                                            class="w-5 h-5 text-indigo-600 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500 dark:focus:ring-indigo-400"
                                        />
                                        <div>
                                            <span class="font-semibold text-gray-700 dark:text-gray-300">Primary</span>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Asosiy mijoz sifatida belgilash</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Form Field - Step 9 (Last Step) -->
                        <div v-else-if="currentStep === 9" class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Kundalik Hayoti <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    v-model="form.daily_routine"
                                    rows="5"
                                    :placeholder="currentStepData.placeholder"
                                    class="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-400 dark:focus:border-indigo-400 transition-all resize-none"
                                    required
                                ></textarea>
                                <p v-if="form.errors.daily_routine" class="mt-2 text-sm text-red-500 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    {{ form.errors.daily_routine }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Nima uni baxtli qiladi? <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    v-model="form.happiness_triggers"
                                    rows="5"
                                    placeholder="Oila bilan vaqt, muvaffaqiyat, e'tirof, dam olish..."
                                    class="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-400 dark:focus:border-indigo-400 transition-all resize-none"
                                    required
                                ></textarea>
                                <p v-if="form.errors.happiness_triggers" class="mt-2 text-sm text-red-500 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    {{ form.errors.happiness_triggers }}
                                </p>
                            </div>
                        </div>

                        <!-- Form Field - Other Steps -->
                        <div v-else class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    {{ currentStepData.label }} <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    v-model="form[currentStepData.field]"
                                    rows="6"
                                    :placeholder="currentStepData.placeholder"
                                    class="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-400 dark:focus:border-indigo-400 transition-all resize-none"
                                    required
                                ></textarea>
                                <p v-if="form.errors[currentStepData.field]" class="mt-2 text-sm text-red-500 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    {{ form.errors[currentStepData.field] }}
                                </p>
                            </div>

                            <!-- Quick Tags -->
                            <div v-if="currentStepData.quickTags && currentStepData.quickTags.length > 0">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Tezkor qo'shish:</p>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="tag in currentStepData.quickTags"
                                        :key="tag"
                                        @click="addQuickTag(tag, currentStepData.field)"
                                        type="button"
                                        class="px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-indigo-100 dark:hover:bg-indigo-900/50 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors border border-transparent hover:border-indigo-300 dark:hover:border-indigo-600"
                                    >
                                        + {{ tag }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Hint Box -->
                        <div class="mt-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-xl flex items-start gap-3">
                            <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-0.5">Maslahat</p>
                                <p class="text-sm text-blue-700 dark:text-blue-400">
                                    {{ currentStepData.hint }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <button
                        v-if="currentStep > 1"
                        @click="previousStep"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all hover:shadow-md"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Orqaga
                    </button>
                    <div v-else class="hidden sm:block"></div>

                    <!-- Next or Submit -->
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <button
                            v-if="currentStep < totalSteps"
                            @click="nextStep"
                            :disabled="!canGoNext"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl disabled:shadow-none"
                        >
                            Keyingisi
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>

                        <!-- Submit Buttons (Step 9) -->
                        <template v-else>
                            <button
                                @click="submitWithoutAI"
                                :disabled="form.processing || !canGoNext"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all shadow-md hover:shadow-lg"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                AI-siz Saqlash
                            </button>
                            <button
                                @click="submitWithAI"
                                :disabled="form.processing || generatingProfile || !canGoNext"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-700 hover:via-purple-700 hover:to-pink-700 disabled:from-gray-400 disabled:via-gray-500 disabled:to-gray-500 disabled:cursor-not-allowed text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all animate-pulse disabled:animate-none"
                            >
                                <svg v-if="!generatingProfile" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                                <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span v-if="generatingProfile">AI Profil Yaratmoqda...</span>
                                <span v-else>AI Profil Yaratish</span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Footer Info -->
                <div class="mt-8 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        AI Profil sizning javoblaringiz asosida batafsil mijoz avatarini yaratadi
                    </p>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
