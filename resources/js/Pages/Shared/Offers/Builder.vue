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
    LightBulbIcon,
    GiftIcon,
    ShieldCheckIcon,
    ClockIcon,
    FireIcon,
    UserGroupIcon,
    CurrencyDollarIcon,
    ChartBarIcon,
    DocumentTextIcon,
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

// Use centralized route helper
const { getRoute } = useOfferRoutes(props.panelType);

const currentStep = ref(1);
const totalSteps = 6;
const generatingAI = ref(false);
const valueScore = ref(props.offer?.value_score || null);

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

    // Value Equation
    dream_outcome_score: props.offer?.dream_outcome_score || 5,
    perceived_likelihood_score: props.offer?.perceived_likelihood_score || 5,
    time_delay_days: props.offer?.time_delay_days || 30,
    effort_score: props.offer?.effort_score || 5,

    // Guarantee
    guarantee_type: props.offer?.guarantee_type || '',
    guarantee_terms: props.offer?.guarantee_terms || '',
    guarantee_period_days: props.offer?.guarantee_period_days || 30,

    // Scarcity & Urgency
    scarcity: props.offer?.scarcity || '',
    urgency: props.offer?.urgency || '',

    total_value: props.offer?.total_value || 0,
    status: props.offer?.status || 'draft',

    generate_ai: false,
});

const steps = computed(() => [
    { number: 1, title: t('offers.builder.step1_title'), icon: DocumentTextIcon, description: t('offers.builder.step1_desc') },
    { number: 2, title: t('offers.builder.step2_title'), icon: UserGroupIcon, description: t('offers.builder.step2_desc') },
    { number: 3, title: t('offers.builder.step3_title'), icon: ChartBarIcon, description: t('offers.builder.step3_desc') },
    { number: 4, title: t('offers.builder.step4_title'), icon: ShieldCheckIcon, description: t('offers.builder.step4_desc') },
    { number: 5, title: t('offers.builder.step5_title'), icon: FireIcon, description: t('offers.builder.step5_desc') },
    { number: 6, title: t('offers.builder.step6_title'), icon: CheckIcon, description: t('offers.builder.step6_desc') },
]);

const canGoNext = computed(() => {
    if (currentStep.value === 1) {
        return form.name && form.value_proposition && form.pricing;
    }
    return true;
});

const progressPercentage = computed(() => {
    return Math.round((currentStep.value / totalSteps) * 100);
});

// Auto-calculate value score
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
    if (!price) return '0 ' + t('offers.currency');
    return new Intl.NumberFormat('uz-UZ').format(price) + ' ' + t('offers.currency');
};

const pricingModels = computed(() => [
    { value: 'one-time', label: t('offers.builder.pricing.one_time') },
    { value: 'monthly', label: t('offers.builder.pricing.monthly') },
    { value: 'yearly', label: t('offers.builder.pricing.yearly') },
    { value: 'payment-plan', label: t('offers.builder.pricing.payment_plan') },
]);
</script>

<template>
    <component :is="layoutComponent" :title="isEdit ? t('offers.builder.edit_title') : t('offers.builder.create_title')">
        <Head :title="isEdit ? t('offers.builder.edit_title') : t('offers.builder.create_title')" />

        <div class="py-6">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Sarlavha -->
                <div class="mb-8">
                    <Link
                        :href="getRoute('index')"
                        class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 mb-4 transition-colors"
                    >
                        <ArrowLeftIcon class="w-4 h-4 mr-2" />
                        {{ t('offers.builder.back_to_offers') }}
                    </Link>

                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <GiftIcon class="w-8 h-8 text-white" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                                {{ isEdit ? t('offers.builder.edit_title') : t('offers.builder.create_new') }}
                            </h1>
                            <p class="mt-1 text-gray-500 dark:text-gray-400">
                                {{ t('offers.builder.methodology_desc') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mb-8 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ t('offers.builder.step', { current: currentStep, total: totalSteps }) }}
                        </span>
                        <span class="text-sm font-bold text-purple-600 dark:text-purple-400">
                            {{ t('offers.builder.ready', { percent: progressPercentage }) }}
                        </span>
                    </div>

                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-6">
                        <div
                            class="bg-gradient-to-r from-purple-500 to-indigo-600 h-2 rounded-full transition-all duration-500"
                            :style="{ width: progressPercentage + '%' }"
                        ></div>
                    </div>

                    <!-- Step Indicators -->
                    <div class="grid grid-cols-6 gap-2">
                        <button
                            v-for="step in steps"
                            :key="step.number"
                            @click="goToStep(step.number)"
                            class="flex flex-col items-center gap-2 p-3 rounded-xl transition-all"
                            :class="[
                                step.number === currentStep
                                    ? 'bg-purple-50 dark:bg-purple-900/30 ring-2 ring-purple-500'
                                    : step.number < currentStep
                                        ? 'bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30'
                                        : 'bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700'
                            ]"
                        >
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center transition-all"
                                :class="[
                                    step.number === currentStep
                                        ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/30'
                                        : step.number < currentStep
                                            ? 'bg-green-500 text-white'
                                            : 'bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-400'
                                ]"
                            >
                                <CheckIcon v-if="step.number < currentStep" class="w-5 h-5" />
                                <component v-else :is="step.icon" class="w-5 h-5" />
                            </div>
                            <div class="text-center">
                                <span
                                    class="text-xs font-medium block"
                                    :class="step.number === currentStep ? 'text-purple-700 dark:text-purple-300' : 'text-gray-600 dark:text-gray-400'"
                                >
                                    {{ step.title }}
                                </span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 mb-6">
                    <!-- 1-Qadam: Asosiy Ma'lumot -->
                    <div v-if="currentStep === 1" class="space-y-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                                <DocumentTextIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('offers.builder.basic_info') }}</h2>
                                <p class="text-gray-500 dark:text-gray-400">{{ t('offers.builder.basic_info_desc') }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    {{ t('offers.builder.offer_name') }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    :placeholder="t('offers.builder.offer_name_placeholder')"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all"
                                    :class="{ 'border-red-500': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
                            </div>

                            <div class="lg:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Qisqacha Tavsif
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="2"
                                    placeholder="Taklif haqida qisqacha ma'lumot..."
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all resize-none"
                                ></textarea>
                            </div>

                            <div class="lg:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Qiymat Taklifi (Value Proposition) <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    v-model="form.value_proposition"
                                    rows="3"
                                    placeholder="Bu taklif mijozga qanday qiymat beradi? Qanday muammolarni hal qiladi?"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all resize-none"
                                ></textarea>
                            </div>

                            <div class="lg:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Asosiy Taklif (Core Offer)
                                </label>
                                <textarea
                                    v-model="form.core_offer"
                                    rows="2"
                                    placeholder="Mijoz aniq nimani oladi? Qanday natijaga erishadi?"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all resize-none"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Narx <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="form.pricing"
                                        type="number"
                                        min="0"
                                        placeholder="1 000 000"
                                        class="w-full px-4 py-3 pr-16 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all"
                                    />
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">
                                        so'm
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    To'lov Turi
                                </label>
                                <select
                                    v-model="form.pricing_model"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all"
                                >
                                    <option v-for="model in pricingModels" :key="model.value" :value="model.value">
                                        {{ model.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="lg:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Maqsadli Auditoriya
                                </label>
                                <input
                                    v-model="form.target_audience"
                                    type="text"
                                    placeholder="Masalan: O'zbekistondagi kichik biznes egalari, 25-45 yosh"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- 2-Qadam: Ideal Mijoz -->
                    <div v-if="currentStep === 2" class="space-y-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                                <UserGroupIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Ideal Mijoz Tanlash</h2>
                                <p class="text-gray-500 dark:text-gray-400">AI taklif yaratish uchun ideal mijoz profilini tanlang</p>
                            </div>
                        </div>

                        <div v-if="dreamBuyers?.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div
                                v-for="buyer in dreamBuyers"
                                :key="buyer.id"
                                @click="form.dream_buyer_id = buyer.id"
                                class="p-5 border-2 rounded-xl cursor-pointer transition-all hover:shadow-md"
                                :class="form.dream_buyer_id === buyer.id
                                    ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 shadow-md'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-purple-300 dark:hover:border-purple-600'"
                            >
                                <div class="flex items-start gap-4">
                                    <div
                                        class="w-6 h-6 rounded-full border-2 flex items-center justify-center flex-shrink-0 mt-0.5"
                                        :class="form.dream_buyer_id === buyer.id
                                            ? 'border-purple-500 bg-purple-500'
                                            : 'border-gray-300 dark:border-gray-500'"
                                    >
                                        <CheckIcon v-if="form.dream_buyer_id === buyer.id" class="w-4 h-4 text-white" />
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900 dark:text-white text-lg">{{ buyer.name }}</h3>
                                        <p v-if="buyer.description" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ buyer.description }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            @click="form.dream_buyer_id = null"
                            class="p-5 border-2 rounded-xl cursor-pointer transition-all hover:shadow-md"
                            :class="form.dream_buyer_id === null
                                ? 'border-gray-500 bg-gray-50 dark:bg-gray-700/50 shadow-md'
                                : 'border-gray-200 dark:border-gray-600 hover:border-gray-400'"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-6 h-6 rounded-full border-2 flex items-center justify-center"
                                    :class="form.dream_buyer_id === null
                                        ? 'border-gray-500 bg-gray-500'
                                        : 'border-gray-300 dark:border-gray-500'"
                                >
                                    <CheckIcon v-if="form.dream_buyer_id === null" class="w-4 h-4 text-white" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">Ideal Mijoz Tanlamaslik</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Umumiy auditoriya uchun taklif yaratish</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="!dreamBuyers?.length" class="text-center py-8 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <UserGroupIcon class="w-12 h-12 mx-auto text-gray-400 mb-3" />
                            <p class="text-gray-600 dark:text-gray-400">Hali ideal mijoz profillari yaratilmagan</p>
                            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Keyingi qadamga o'ting</p>
                        </div>
                    </div>

                    <!-- 3-Qadam: Qiymat Formulasi -->
                    <div v-if="currentStep === 3" class="space-y-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                                <ChartBarIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Qiymat Formulasi</h2>
                                <p class="text-gray-500 dark:text-gray-400">Alex Hormozi "$100M Offers" metodikasi</p>
                            </div>
                        </div>

                        <!-- Formula Info -->
                        <div class="p-4 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 border border-purple-200 dark:border-purple-700 rounded-xl">
                            <p class="text-sm text-purple-800 dark:text-purple-300 font-medium">
                                <strong>Formula:</strong> Qiymat = (Orzu Natijasi × Ehtimollik) ÷ (Vaqt × Mehnat)
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Dream Outcome -->
                            <div class="p-5 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl">
                                <label class="block text-sm font-semibold text-green-800 dark:text-green-300 mb-3">
                                    Orzu Natijasi (Dream Outcome)
                                </label>
                                <input
                                    v-model.number="form.dream_outcome_score"
                                    type="range"
                                    min="1"
                                    max="10"
                                    class="w-full h-2 bg-green-200 dark:bg-green-700 rounded-lg appearance-none cursor-pointer accent-green-600"
                                />
                                <div class="flex justify-between text-xs text-green-700 dark:text-green-400 mt-2">
                                    <span>Oddiy (1)</span>
                                    <span class="text-lg font-bold">{{ form.dream_outcome_score }}</span>
                                    <span>Hayot o'zgartiruvchi (10)</span>
                                </div>
                            </div>

                            <!-- Perceived Likelihood -->
                            <div class="p-5 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl">
                                <label class="block text-sm font-semibold text-blue-800 dark:text-blue-300 mb-3">
                                    Ishonch Darajasi (Perceived Likelihood)
                                </label>
                                <input
                                    v-model.number="form.perceived_likelihood_score"
                                    type="range"
                                    min="1"
                                    max="10"
                                    class="w-full h-2 bg-blue-200 dark:bg-blue-700 rounded-lg appearance-none cursor-pointer accent-blue-600"
                                />
                                <div class="flex justify-between text-xs text-blue-700 dark:text-blue-400 mt-2">
                                    <span>Shubhali (1)</span>
                                    <span class="text-lg font-bold">{{ form.perceived_likelihood_score }}</span>
                                    <span>100% ishonch (10)</span>
                                </div>
                            </div>

                            <!-- Time Delay -->
                            <div class="p-5 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700 rounded-xl">
                                <label class="block text-sm font-semibold text-orange-800 dark:text-orange-300 mb-3">
                                    Natija Kutish Vaqti (kunlarda)
                                </label>
                                <input
                                    v-model.number="form.time_delay_days"
                                    type="number"
                                    min="1"
                                    class="w-full px-4 py-3 border border-orange-300 dark:border-orange-600 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                />
                                <p class="text-xs text-orange-600 dark:text-orange-400 mt-2">Qanchalik tez natija, shunchalik yaxshi</p>
                            </div>

                            <!-- Effort Score -->
                            <div class="p-5 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-xl">
                                <label class="block text-sm font-semibold text-purple-800 dark:text-purple-300 mb-3">
                                    Talab Qilinadigan Mehnat
                                </label>
                                <input
                                    v-model.number="form.effort_score"
                                    type="range"
                                    min="1"
                                    max="10"
                                    class="w-full h-2 bg-purple-200 dark:bg-purple-700 rounded-lg appearance-none cursor-pointer accent-purple-600"
                                />
                                <div class="flex justify-between text-xs text-purple-700 dark:text-purple-400 mt-2">
                                    <span>Oson (1)</span>
                                    <span class="text-lg font-bold">{{ form.effort_score }}</span>
                                    <span>Juda qiyin (10)</span>
                                </div>
                            </div>
                        </div>

                        <!-- Value Score Result -->
                        <div class="p-6 bg-gradient-to-r from-yellow-100 to-orange-100 dark:from-yellow-900/30 dark:to-orange-900/30 border-2 border-yellow-400 dark:border-yellow-600 rounded-2xl text-center">
                            <p class="text-sm font-semibold text-yellow-700 dark:text-yellow-400 mb-2">QIYMAT BAHOSI</p>
                            <p class="text-5xl font-bold text-yellow-800 dark:text-yellow-300">{{ valueScore || '0.00' }}</p>
                            <p class="text-xs text-yellow-600 dark:text-yellow-500 mt-2">Yuqori = Yaxshiroq taklif</p>
                        </div>
                    </div>

                    <!-- 4-Qadam: Kafolat -->
                    <div v-if="currentStep === 4" class="space-y-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                                <ShieldCheckIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Kafolat (Risk Reversal)</h2>
                                <p class="text-gray-500 dark:text-gray-400">Mijozning xavfini kamaytiring</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Kafolat Turi
                                </label>
                                <input
                                    v-model="form.guarantee_type"
                                    type="text"
                                    placeholder="Masalan: 30 kunlik pulni qaytarish kafolati"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Kafolat Muddati (kunlarda)
                                </label>
                                <input
                                    v-model.number="form.guarantee_period_days"
                                    type="number"
                                    min="1"
                                    placeholder="30"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all"
                                />
                            </div>

                            <div class="lg:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Kafolat Shartlari
                                </label>
                                <textarea
                                    v-model="form.guarantee_terms"
                                    rows="4"
                                    placeholder="Masalan: Agar 30 kun ichida ko'zda tutilgan natijaga erisha olmasangiz, pulingizni 100% qaytaramiz. Hech qanday savol so'ramaymiz."
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all resize-none"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Kafolat misollari -->
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl">
                            <h4 class="font-semibold text-green-800 dark:text-green-300 mb-2">Maslahat:</h4>
                            <ul class="text-sm text-green-700 dark:text-green-400 space-y-1">
                                <li>• Kuchli kafolat = Ko'proq ishonch = Ko'proq sotuv</li>
                                <li>• "Natija kafolati" oddiy "pulni qaytarish"dan kuchliroq</li>
                                <li>• Aniq, tushunarli shartlar yozing</li>
                            </ul>
                        </div>
                    </div>

                    <!-- 5-Qadam: Cheklovlar -->
                    <div v-if="currentStep === 5" class="space-y-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                                <FireIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Kamlik va Shoshilinchlik</h2>
                                <p class="text-gray-500 dark:text-gray-400">Mijozni harakatga undang</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="p-5 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl">
                                <label class="flex items-center gap-2 text-sm font-semibold text-blue-800 dark:text-blue-300 mb-3">
                                    <ClockIcon class="w-5 h-5" />
                                    Kamlik (Scarcity)
                                </label>
                                <textarea
                                    v-model="form.scarcity"
                                    rows="4"
                                    placeholder="Masalan: Faqat 50 ta joy mavjud. Hozirda 43 ta band."
                                    class="w-full px-4 py-3 border border-blue-300 dark:border-blue-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all resize-none"
                                ></textarea>
                            </div>

                            <div class="p-5 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl">
                                <label class="flex items-center gap-2 text-sm font-semibold text-red-800 dark:text-red-300 mb-3">
                                    <FireIcon class="w-5 h-5" />
                                    Shoshilinchlik (Urgency)
                                </label>
                                <textarea
                                    v-model="form.urgency"
                                    rows="4"
                                    placeholder="Masalan: 3 kundan keyin narx 30% oshadi. Bugungi maxsus narx faqat shu hafta."
                                    class="w-full px-4 py-3 border border-red-300 dark:border-red-600 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all resize-none"
                                ></textarea>
                            </div>
                        </div>

                        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl">
                            <h4 class="font-semibold text-yellow-800 dark:text-yellow-300 mb-2">Eslatma:</h4>
                            <p class="text-sm text-yellow-700 dark:text-yellow-400">
                                Haqiqiy cheklovlar ishlatilishi kerak. Yolg'on kamlik yoki shoshilinchlik ishonchni yo'qotadi.
                            </p>
                        </div>
                    </div>

                    <!-- 6-Qadam: Yakunlash -->
                    <div v-if="currentStep === 6" class="space-y-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center">
                                <CheckIcon class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Ko'rib Chiqish</h2>
                                <p class="text-gray-500 dark:text-gray-400">Taklifingizni tekshiring va saqlang</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Asosiy ma'lumotlar -->
                            <div class="p-5 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Asosiy Ma'lumotlar</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">Nomi:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ form.name || '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">Narx:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ formatPrice(form.pricing) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">To'lov turi:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ pricingModels.find(m => m.value === form.pricing_model)?.label || '-' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Qiymat bahosi -->
                            <div class="p-5 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl">
                                <h4 class="font-semibold text-yellow-700 dark:text-yellow-300 mb-3">Qiymat Bahosi</h4>
                                <p class="text-4xl font-bold text-yellow-800 dark:text-yellow-300 text-center">{{ valueScore || '0.00' }}</p>
                            </div>

                            <!-- Kafolat -->
                            <div v-if="form.guarantee_type" class="p-5 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl">
                                <h4 class="font-semibold text-green-700 dark:text-green-300 mb-2">Kafolat</h4>
                                <p class="text-gray-800 dark:text-gray-200">{{ form.guarantee_type }}</p>
                                <p class="text-sm text-green-600 dark:text-green-400 mt-1">{{ form.guarantee_period_days }} kun</p>
                            </div>

                            <!-- Status -->
                            <div class="p-5 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Holat
                                </label>
                                <select
                                    v-model="form.status"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
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
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                        {{ t('offers.builder.back') }}
                    </button>
                    <div v-else></div>

                    <div class="flex gap-3">
                        <button
                            v-if="currentStep < totalSteps"
                            @click="nextStep"
                            :disabled="!canGoNext"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all shadow-lg shadow-purple-500/30"
                        >
                            {{ t('offers.builder.next') }}
                            <ArrowRightIcon class="w-5 h-5" />
                        </button>

                        <template v-else>
                            <button
                                @click="submitWithoutAI"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-300 text-white font-semibold rounded-xl transition-all"
                            >
                                <CheckIcon class="w-5 h-5" />
                                {{ t('offers.builder.save') }}
                            </button>
                            <button
                                @click="submitWithAI"
                                :disabled="form.processing || generatingAI"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 disabled:from-gray-400 disabled:to-gray-500 text-white font-semibold rounded-xl shadow-lg shadow-purple-500/30 transition-all"
                            >
                                <SparklesIcon class="w-5 h-5" />
                                <span v-if="generatingAI">{{ t('offers.builder.ai_creating') }}</span>
                                <span v-else>{{ t('offers.builder.save_with_ai') }}</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
