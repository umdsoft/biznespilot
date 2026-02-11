<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { useOfferRoutes } from '@/composables/useOfferRoutes.js';
import { useI18n } from '@/i18n';

const { t } = useI18n();
import {
    ArrowLeftIcon,
    ArrowRightIcon,
    CheckIcon,
    SparklesIcon,
    ChevronDownIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    dreamBuyers: Array,
    offer: {
        type: Object,
        default: null,
    },
    isEdit: {
        type: Boolean,
        default: false,
    },
    panelType: {
        type: String,
        required: true,
        validator: (v) => ['business', 'marketing', 'finance', 'operator', 'saleshead'].includes(v),
    },
});

const layoutComponent = computed(() => {
    const layouts = {
        business: BusinessLayout,
        marketing: MarketingLayout,
        finance: FinanceLayout,
        operator: OperatorLayout,
        saleshead: SalesHeadLayout,
    };
    return layouts[props.panelType] || BaseLayout;
});

const { getRoute } = useOfferRoutes(props.panelType);

const currentStep = ref(1);
const totalSteps = 3;
const generatingAI = ref(false);
const valueScore = ref(props.offer?.value_score || null);
const showAdvanced = ref(false);

const form = useForm({
    name: props.offer?.name || '',
    description: props.offer?.description || '',
    product_name: '',
    product_description: '',
    main_benefit: '',
    value_proposition: props.offer?.value_proposition || '',
    target_audience: props.offer?.target_audience || '',
    pricing: props.offer?.pricing || '',
    pricing_model: props.offer?.pricing_model || 'one-time',
    core_offer: props.offer?.core_offer || '',
    dream_buyer_id: null,
    dream_outcome_score: props.offer?.dream_outcome_score || 5,
    perceived_likelihood_score: props.offer?.perceived_likelihood_score || 5,
    time_delay_days: props.offer?.time_delay_days || 30,
    effort_score: props.offer?.effort_score || 5,
    guarantee_type: props.offer?.guarantee_type || '',
    guarantee_terms: props.offer?.guarantee_terms || '',
    guarantee_period_days: props.offer?.guarantee_period_days || 30,
    scarcity: props.offer?.scarcity || '',
    urgency: props.offer?.urgency || '',
    total_value: props.offer?.total_value || 0,
    status: props.offer?.status || 'draft',
    generate_ai: false,
});

const steps = [
    { number: 1, title: 'Asosiy ma\'lumot' },
    { number: 2, title: 'Kuchaytirish' },
    { number: 3, title: 'Saqlash' },
];

const canGoNext = computed(() => {
    if (currentStep.value === 1) {
        return form.name && form.value_proposition && form.pricing;
    }
    return true;
});

const progressPercentage = computed(() => {
    return Math.round((currentStep.value / totalSteps) * 100);
});

watch(
    () => [form.dream_outcome_score, form.perceived_likelihood_score, form.time_delay_days, form.effort_score],
    () => {
        const dreamOutcome = form.dream_outcome_score || 5;
        const perceivedLikelihood = form.perceived_likelihood_score || 5;
        const timeDelay = form.time_delay_days || 30;
        const effort = form.effort_score || 5;

        const denominator = timeDelay * effort;
        if (denominator > 0) {
            valueScore.value = ((dreamOutcome * perceivedLikelihood) / denominator * 100).toFixed(2);
        }
    },
    { immediate: true }
);

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
    if (step <= currentStep.value || canGoNext.value) {
        currentStep.value = step;
    }
};

const submit = (withAI = false) => {
    form.generate_ai = withAI;
    if (props.isEdit) {
        form.put(getRoute('update', props.offer.id));
    } else {
        form.post(getRoute('store'));
    }
};

const submitWithAI = () => {
    generatingAI.value = true;
    submit(true);
};

const submitWithoutAI = () => {
    submit(false);
};

const formatPrice = (price) => {
    if (!price) return '0 so\'m';
    return new Intl.NumberFormat('uz-UZ').format(price) + ' so\'m';
};

const pricingModels = [
    { value: 'one-time', label: 'Bir martalik' },
    { value: 'monthly', label: 'Oylik' },
    { value: 'yearly', label: 'Yillik' },
    { value: 'payment-plan', label: 'Bo\'lib to\'lash' },
];
</script>

<template>
    <component :is="layoutComponent" :title="isEdit ? 'Taklifni tahrirlash' : 'Yangi taklif'">
        <Head :title="isEdit ? 'Taklifni tahrirlash' : 'Yangi taklif'" />

        <div class="py-6">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <Link
                        :href="getRoute('index')"
                        class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 mb-3"
                    >
                        <ArrowLeftIcon class="w-4 h-4 mr-1" />
                        Takliflar
                    </Link>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        {{ isEdit ? 'Taklifni tahrirlash' : 'Yangi taklif yaratish' }}
                    </h1>
                </div>

                <!-- Steps indicator -->
                <div class="flex items-center gap-2 mb-6">
                    <button
                        v-for="step in steps"
                        :key="step.number"
                        @click="goToStep(step.number)"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors flex-1 justify-center"
                        :class="[
                            step.number === currentStep
                                ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 ring-1 ring-blue-200 dark:ring-blue-800'
                                : step.number < currentStep
                                    ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400'
                                    : 'bg-gray-50 dark:bg-gray-800 text-gray-400 dark:text-gray-500'
                        ]"
                    >
                        <span
                            class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                            :class="[
                                step.number === currentStep ? 'bg-blue-600' :
                                step.number < currentStep ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'
                            ]"
                        >
                            <CheckIcon v-if="step.number < currentStep" class="w-3 h-3" />
                            <span v-else>{{ step.number }}</span>
                        </span>
                        <span class="hidden sm:inline">{{ step.title }}</span>
                    </button>
                </div>

                <!-- Form Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">

                    <!-- ============ STEP 1: Asosiy ma'lumot ============ -->
                    <div v-if="currentStep === 1" class="space-y-5">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Taklif haqida</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Mijozingizga nima taklif qilasiz?</p>
                        </div>

                        <!-- Nomi -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Taklif nomi <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="Masalan: Premium Marketing Kurs"
                                class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                                :class="{ 'border-red-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <!-- Qiymat taklifi -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Mijoz nimaga erishadi? <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="form.value_proposition"
                                rows="2"
                                placeholder="Masalan: 90 kun ichida Instagram orqali 1000+ mijoz topish tizimi"
                                class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm resize-none"
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Mijoz uchun eng muhim natijani yozing</p>
                        </div>

                        <!-- Narx + To'lov turi -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Narx <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="form.pricing"
                                        type="number"
                                        min="0"
                                        placeholder="1 000 000"
                                        class="w-full px-3 py-2.5 pr-14 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                                    />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">so'm</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    To'lov turi
                                </label>
                                <select
                                    v-model="form.pricing_model"
                                    class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                                >
                                    <option v-for="model in pricingModels" :key="model.value" :value="model.value">
                                        {{ model.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Maqsadli auditoriya -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Kimlar uchun?
                            </label>
                            <input
                                v-model="form.target_audience"
                                type="text"
                                placeholder="Masalan: Kichik biznes egalari, 25-45 yosh"
                                class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                            />
                        </div>

                        <!-- Nimani oladi (core_offer) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Mijoz aniq nimani oladi?
                            </label>
                            <textarea
                                v-model="form.core_offer"
                                rows="2"
                                placeholder="Masalan: 12 ta video dars + shaxsiy mentor + tayyor shablonlar"
                                class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm resize-none"
                            ></textarea>
                        </div>

                        <!-- Ideal mijoz (agar bor bo'lsa) -->
                        <div v-if="dreamBuyers?.length > 0">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Ideal mijoz profili
                            </label>
                            <select
                                v-model="form.dream_buyer_id"
                                class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                            >
                                <option :value="null">Tanlanmagan</option>
                                <option v-for="buyer in dreamBuyers" :key="buyer.id" :value="buyer.id">
                                    {{ buyer.name }}
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Ixtiyoriy — AI taklif tuzishda yordam beradi</p>
                        </div>
                    </div>

                    <!-- ============ STEP 2: Kuchaytirish ============ -->
                    <div v-if="currentStep === 2" class="space-y-5">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Taklifni kuchaytiring</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Ixtiyoriy, lekin sotuvni oshiradi</p>
                        </div>

                        <!-- Kafolat -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Kafolat</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 -mt-2">Mijozning xavfini kamaytiring — ko'proq sotasiz</p>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Kafolat turi</label>
                                    <input
                                        v-model="form.guarantee_type"
                                        type="text"
                                        placeholder="Masalan: Pulni qaytarish"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Muddat (kun)</label>
                                    <input
                                        v-model.number="form.guarantee_period_days"
                                        type="number"
                                        min="1"
                                        placeholder="30"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Shartlar</label>
                                <textarea
                                    v-model="form.guarantee_terms"
                                    rows="2"
                                    placeholder="Masalan: 30 kun ichida natija bo'lmasa, pulingizni 100% qaytaramiz"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Kamlik va Shoshilinchlik -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg space-y-2">
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Cheklangan miqdor</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Nechta mavjud?</p>
                                <textarea
                                    v-model="form.scarcity"
                                    rows="2"
                                    placeholder="Masalan: Faqat 50 ta joy. 43 tasi band."
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                ></textarea>
                            </div>

                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg space-y-2">
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Vaqt cheklovi</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Qachongacha amal qiladi?</p>
                                <textarea
                                    v-model="form.urgency"
                                    rows="2"
                                    placeholder="Masalan: 3 kundan keyin narx 30% oshadi"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Qisqacha tavsif (optional) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Qo'shimcha izoh
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                placeholder="O'zingiz uchun eslatma yoki taklif haqida qo'shimcha ma'lumot..."
                                class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm resize-none"
                            ></textarea>
                        </div>

                        <!-- Kengaytirilgan — Value Formula -->
                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg">
                            <button
                                @click="showAdvanced = !showAdvanced"
                                type="button"
                                class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg transition-colors"
                            >
                                <span>Kengaytirilgan sozlamalar</span>
                                <ChevronDownIcon
                                    class="w-4 h-4 transition-transform"
                                    :class="{ 'rotate-180': showAdvanced }"
                                />
                            </button>

                            <div v-if="showAdvanced" class="px-4 pb-4 space-y-4 border-t border-gray-200 dark:border-gray-600 pt-4">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Taklifning qiymatini baholash — yuqori ball = kuchliroq taklif
                                </p>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">
                                            Natija qanchalik katta? <span class="text-xs font-normal">({{ form.dream_outcome_score }}/10)</span>
                                        </label>
                                        <input
                                            v-model.number="form.dream_outcome_score"
                                            type="range" min="1" max="10"
                                            class="w-full h-1.5 bg-gray-200 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer accent-blue-600"
                                        />
                                        <div class="flex justify-between text-[10px] text-gray-400">
                                            <span>Oddiy</span>
                                            <span>Hayot o'zgartiradi</span>
                                        </div>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">
                                            Mijoz ishonadi? <span class="text-xs font-normal">({{ form.perceived_likelihood_score }}/10)</span>
                                        </label>
                                        <input
                                            v-model.number="form.perceived_likelihood_score"
                                            type="range" min="1" max="10"
                                            class="w-full h-1.5 bg-gray-200 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer accent-blue-600"
                                        />
                                        <div class="flex justify-between text-[10px] text-gray-400">
                                            <span>Shubhali</span>
                                            <span>100% ishonch</span>
                                        </div>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">
                                            Natija qancha kunda? <span class="text-xs font-normal">({{ form.time_delay_days }} kun)</span>
                                        </label>
                                        <input
                                            v-model.number="form.time_delay_days"
                                            type="number" min="1"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">
                                            Qanchalik qiyin? <span class="text-xs font-normal">({{ form.effort_score }}/10)</span>
                                        </label>
                                        <input
                                            v-model.number="form.effort_score"
                                            type="range" min="1" max="10"
                                            class="w-full h-1.5 bg-gray-200 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer accent-blue-600"
                                        />
                                        <div class="flex justify-between text-[10px] text-gray-400">
                                            <span>Oson</span>
                                            <span>Juda qiyin</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Qiymat bahosi</span>
                                    <span class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ valueScore || '0.00' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============ STEP 3: Ko'rib chiqish ============ -->
                    <div v-if="currentStep === 3" class="space-y-5">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Ko'rib chiqing va saqlang</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Hamma narsa to'g'rimi?</p>
                        </div>

                        <!-- Summary -->
                        <div class="space-y-3">
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg space-y-2">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ form.name || '—' }}</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ form.value_proposition || '—' }}</p>
                                    </div>
                                    <span class="text-lg font-bold text-gray-900 dark:text-gray-100 whitespace-nowrap ml-4">
                                        {{ formatPrice(form.pricing) }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap gap-2 pt-1">
                                    <span class="text-xs px-2 py-0.5 bg-gray-200 dark:bg-gray-600 rounded text-gray-600 dark:text-gray-300">
                                        {{ pricingModels.find(m => m.value === form.pricing_model)?.label }}
                                    </span>
                                    <span v-if="form.target_audience" class="text-xs px-2 py-0.5 bg-gray-200 dark:bg-gray-600 rounded text-gray-600 dark:text-gray-300">
                                        {{ form.target_audience }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="form.core_offer" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Mijoz oladi:</span>
                                <p class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">{{ form.core_offer }}</p>
                            </div>

                            <div v-if="form.guarantee_type || form.scarcity || form.urgency" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div v-if="form.guarantee_type" class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Kafolat</span>
                                    <p class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">{{ form.guarantee_type }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ form.guarantee_period_days }} kun</p>
                                </div>
                                <div v-if="form.scarcity" class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Cheklangan</span>
                                    <p class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">{{ form.scarcity }}</p>
                                </div>
                                <div v-if="form.urgency" class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Vaqt cheklovi</span>
                                    <p class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">{{ form.urgency }}</p>
                                </div>
                            </div>

                            <!-- Holat -->
                            <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">Holat:</label>
                                <select
                                    v-model="form.status"
                                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="draft">Qoralama</option>
                                    <option value="active">Faol</option>
                                    <option value="paused">Pauza</option>
                                    <option value="archived">Arxiv</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="flex items-center justify-between">
                    <button
                        v-if="currentStep > 1"
                        @click="previousStep"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                        Orqaga
                    </button>
                    <div v-else></div>

                    <div class="flex gap-2">
                        <button
                            v-if="currentStep < totalSteps"
                            @click="nextStep"
                            :disabled="!canGoNext"
                            class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors"
                        >
                            Keyingi
                            <ArrowRightIcon class="w-4 h-4" />
                        </button>

                        <template v-else>
                            <button
                                @click="submitWithoutAI"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-300 text-white text-sm font-medium rounded-lg transition-colors"
                            >
                                <CheckIcon class="w-4 h-4" />
                                Saqlash
                            </button>
                            <button
                                @click="submitWithAI"
                                :disabled="form.processing || generatingAI"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 text-white text-sm font-medium rounded-lg transition-colors"
                            >
                                <SparklesIcon class="w-4 h-4" />
                                <span v-if="generatingAI">AI yaratmoqda...</span>
                                <span v-else>AI bilan saqlash</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
