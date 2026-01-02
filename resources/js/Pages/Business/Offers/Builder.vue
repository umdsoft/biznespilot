<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
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
    DocumentTextIcon,
    UsersIcon,
    ChartBarIcon,
    CheckCircleIcon,
    BoltIcon
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
});

const currentStep = ref(1);
const totalSteps = 6;
const generatingAI = ref(false);
const valueScore = ref(null);

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

const steps = [
    { number: 1, title: 'Asosiy Ma\'lumot', icon: DocumentTextIcon },
    { number: 2, title: 'Ideal Mijoz', icon: UsersIcon },
    { number: 3, title: 'Value Equation', icon: ChartBarIcon },
    { number: 4, title: 'Guarantee', icon: ShieldCheckIcon },
    { number: 5, title: 'Scarcity & Urgency', icon: BoltIcon },
    { number: 6, title: 'Review', icon: CheckCircleIcon },
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

const calculateValue = async () => {
    try {
        const response = await fetch(route('business.offers.calculate-value-score'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                dream_outcome_score: form.dream_outcome_score,
                perceived_likelihood_score: form.perceived_likelihood_score,
                time_delay_days: form.time_delay_days,
                effort_score: form.effort_score,
            }),
        });

        const data = await response.json();
        valueScore.value = data.value_score;
    } catch (error) {
        console.error('Value score calculation error:', error);
    }
};

const submit = (withAI = false) => {
    form.generate_ai = withAI;

    if (props.isEdit) {
        form.put(route('business.offers.update', props.offer.id));
    } else {
        form.post(route('business.offers.store'));
    }
};

const submitWithAI = () => {
    generatingAI.value = true;
    submit(true);
};

const submitWithoutAI = () => {
    submit(false);
};
</script>

<template>
    <BusinessLayout :title="isEdit ? 'Offer Tahrirlash' : 'Yangi Offer'">
        <Head :title="isEdit ? 'Offer Tahrirlash' : 'Yangi Offer'" />

        <div class="p-6">
            <div class="max-w-4xl mx-auto">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('business.offers.index')"
                        class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 mb-4 transition-colors"
                    >
                        <ArrowLeftIcon class="w-4 h-4 mr-1" />
                        Orqaga
                    </Link>
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                            <GiftIcon class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ isEdit ? 'Offer Tahrirlash' : 'Irresistible Offer Yaratish' }}
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                "$100M Offers" metodologiyasi asosida
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mb-8 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Qadam {{ currentStep }} / {{ totalSteps }}
                        </span>
                        <span class="text-sm font-bold text-purple-600 dark:text-purple-400">
                            {{ progressPercentage }}%
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                        <div
                            class="bg-gradient-to-r from-purple-600 to-indigo-600 h-2.5 rounded-full transition-all duration-500"
                            :style="{ width: progressPercentage + '%' }"
                        ></div>
                    </div>

                    <!-- Step Indicators -->
                    <div class="flex justify-between mt-6">
                        <button
                            v-for="step in steps"
                            :key="step.number"
                            @click="goToStep(step.number)"
                            class="flex flex-col items-center gap-2 group"
                        >
                            <div
                                class="w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-300"
                                :class="step.number <= currentStep
                                    ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/30'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 group-hover:bg-gray-200 dark:group-hover:bg-gray-600'"
                            >
                                <CheckIcon v-if="step.number < currentStep" class="w-6 h-6" />
                                <component v-else :is="step.icon" class="w-6 h-6" />
                            </div>
                            <span
                                class="text-xs font-medium hidden md:block transition-colors"
                                :class="step.number <= currentStep
                                    ? 'text-purple-600 dark:text-purple-400'
                                    : 'text-gray-500 dark:text-gray-400'"
                            >
                                {{ step.title }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 mb-6">
                    <!-- Step 1: Basic Info -->
                    <div v-if="currentStep === 1">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl">
                                <DocumentTextIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Asosiy Ma'lumot</h2>
                        </div>
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Offer Nomi <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Masalan: Premium Marketing Paketi"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
                                    required
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Qisqa Tavsif
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Offer haqida qisqacha..."
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Value Proposition (Qiymat Taklifi) <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    v-model="form.value_proposition"
                                    rows="4"
                                    placeholder="Bu offer mijozga qanday qiymat beradi? Nima uchun bu yaxshi tanlov?"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
                                    required
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Core Offer (Asosiy Taklif)
                                </label>
                                <textarea
                                    v-model="form.core_offer"
                                    rows="3"
                                    placeholder="Mijoz nimani oladi? (AI yaratishi mumkin)"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Narx <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.pricing"
                                        type="number"
                                        min="0"
                                        placeholder="1000000"
                                        class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Pricing Model
                                    </label>
                                    <select
                                        v-model="form.pricing_model"
                                        class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-gray-100 transition-colors"
                                    >
                                        <option value="one-time">Bir martalik to'lov</option>
                                        <option value="monthly">Oylik obuna</option>
                                        <option value="yearly">Yillik obuna</option>
                                        <option value="payment-plan">To'lov rejasi</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Target Audience
                                </label>
                                <input
                                    v-model="form.target_audience"
                                    type="text"
                                    placeholder="Kichik biznes egalari, Tadbirkorlar..."
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Ideal Mijoz -->
                    <div v-if="currentStep === 2">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
                                <UsersIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Ideal Mijoz Tanlash</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    AI uchun Ideal Mijoz profilini tanlang
                                </p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="buyer in dreamBuyers"
                                :key="buyer.id"
                                @click="form.dream_buyer_id = buyer.id"
                                class="p-4 border-2 rounded-xl cursor-pointer transition-all"
                                :class="form.dream_buyer_id === buyer.id
                                    ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600 bg-white dark:bg-gray-900'"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all"
                                        :class="form.dream_buyer_id === buyer.id
                                            ? 'border-purple-500 bg-purple-500'
                                            : 'border-gray-300 dark:border-gray-600'"
                                    >
                                        <CheckIcon v-if="form.dream_buyer_id === buyer.id" class="w-4 h-4 text-white" />
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ buyer.name }}</h3>
                                        <p v-if="buyer.description" class="text-sm text-gray-600 dark:text-gray-400">{{ buyer.description }}</p>
                                    </div>
                                </div>
                            </div>

                            <div
                                @click="form.dream_buyer_id = null"
                                class="p-4 border-2 rounded-xl cursor-pointer transition-all"
                                :class="form.dream_buyer_id === null
                                    ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600 bg-white dark:bg-gray-900'"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all"
                                        :class="form.dream_buyer_id === null
                                            ? 'border-purple-500 bg-purple-500'
                                            : 'border-gray-300 dark:border-gray-600'"
                                    >
                                        <CheckIcon v-if="form.dream_buyer_id === null" class="w-4 h-4 text-white" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Ideal Mijoz tanlamaslik</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Umumiy auditoriya uchun</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Value Equation -->
                    <div v-if="currentStep === 3">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl">
                                <ChartBarIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Value Equation</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    "$100M Offers" formulasi: (Dream Outcome × Likelihood) / (Time × Effort)
                                </p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="p-5 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Dream Outcome Score (1-10) - Qanchalik katta orzuga erishadi?
                                </label>
                                <input
                                    v-model.number="form.dream_outcome_score"
                                    @input="calculateValue"
                                    type="range"
                                    min="1"
                                    max="10"
                                    class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-purple-600"
                                />
                                <div class="flex justify-between text-sm mt-2">
                                    <span class="text-gray-500 dark:text-gray-400">Kichik (1)</span>
                                    <span class="font-bold text-purple-600 dark:text-purple-400 text-lg">{{ form.dream_outcome_score }}</span>
                                    <span class="text-gray-500 dark:text-gray-400">Hayotni o'zgartiradi (10)</span>
                                </div>
                            </div>

                            <div class="p-5 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Perceived Likelihood (1-10) - Natijaga ishonch darajasi
                                </label>
                                <input
                                    v-model.number="form.perceived_likelihood_score"
                                    @input="calculateValue"
                                    type="range"
                                    min="1"
                                    max="10"
                                    class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-purple-600"
                                />
                                <div class="flex justify-between text-sm mt-2">
                                    <span class="text-gray-500 dark:text-gray-400">Shubhali (1)</span>
                                    <span class="font-bold text-purple-600 dark:text-purple-400 text-lg">{{ form.perceived_likelihood_score }}</span>
                                    <span class="text-gray-500 dark:text-gray-400">100% ishonch (10)</span>
                                </div>
                            </div>

                            <div class="p-5 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Time Delay (kunlarda) - Natija qancha vaqtda?
                                </label>
                                <input
                                    v-model.number="form.time_delay_days"
                                    @input="calculateValue"
                                    type="number"
                                    min="1"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-gray-100 transition-colors"
                                />
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Kam = Yaxshi (tezroq natija)</p>
                            </div>

                            <div class="p-5 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Effort Score (1-10) - Qanchalik qiyin/oson?
                                </label>
                                <input
                                    v-model.number="form.effort_score"
                                    @input="calculateValue"
                                    type="range"
                                    min="1"
                                    max="10"
                                    class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-purple-600"
                                />
                                <div class="flex justify-between text-sm mt-2">
                                    <span class="text-gray-500 dark:text-gray-400">Oson (1)</span>
                                    <span class="font-bold text-purple-600 dark:text-purple-400 text-lg">{{ form.effort_score }}</span>
                                    <span class="text-gray-500 dark:text-gray-400">Juda qiyin (10)</span>
                                </div>
                            </div>

                            <!-- Value Score Display -->
                            <div v-if="valueScore !== null" class="p-6 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border-2 border-yellow-300 dark:border-yellow-600 rounded-xl">
                                <div class="text-center">
                                    <p class="text-sm font-semibold text-yellow-700 dark:text-yellow-400 mb-2">VALUE SCORE</p>
                                    <p class="text-5xl font-bold text-yellow-800 dark:text-yellow-300">{{ valueScore }}</p>
                                    <p class="text-sm text-yellow-600 dark:text-yellow-500 mt-2">Yuqori = Yaxshiroq Offer</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Guarantee -->
                    <div v-if="currentStep === 4">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
                                <ShieldCheckIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Kafolat (Risk Reversal)</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Kuchli kafolat xavfni kamaytirib, ishonchni oshiradi
                                </p>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Kafolat Turi
                                </label>
                                <input
                                    v-model="form.guarantee_type"
                                    type="text"
                                    placeholder="30 kunlik pul qaytarish kafolati, Natija kafolati..."
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Kafolat Shartlari
                                </label>
                                <textarea
                                    v-model="form.guarantee_terms"
                                    rows="4"
                                    placeholder="Agar 30 kun ichida natija ko'rmasa, to'liq pul qaytariladi..."
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Kafolat Muddati (kunlarda)
                                </label>
                                <input
                                    v-model.number="form.guarantee_period_days"
                                    type="number"
                                    min="1"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-gray-100 transition-colors"
                                />
                            </div>

                            <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl flex items-start gap-3">
                                <LightBulbIcon class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5 flex-shrink-0" />
                                <span class="text-sm text-green-800 dark:text-green-300">
                                    <strong>Maslahat:</strong> Uzunroq kafolat muddati (60-90 kun) ishonchni oshiradi va qaytarishlar kamayadi.
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Scarcity & Urgency -->
                    <div v-if="currentStep === 5">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-xl">
                                <BoltIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Scarcity & Urgency</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Cheklangan miqdor va vaqt mijozlarni tezroq qaror qabul qilishga undaydi
                                </p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="p-5 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-700">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                    <ClockIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                    Scarcity (Kamlik)
                                </label>
                                <textarea
                                    v-model="form.scarcity"
                                    rows="3"
                                    placeholder="Faqat 50 ta joy mavjud, Cheklangan nusxa..."
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
                                ></textarea>
                                <p class="text-sm text-blue-600 dark:text-blue-400 mt-2">
                                    Miqdor cheklovlari (faqat 50 ta), maxsus huquqlar, eksklyuzivlik
                                </p>
                            </div>

                            <div class="p-5 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-700">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                    <FireIcon class="w-5 h-5 text-red-600 dark:text-red-400" />
                                    Urgency (Shoshilinchlik)
                                </label>
                                <textarea
                                    v-model="form.urgency"
                                    rows="3"
                                    placeholder="3 kundan keyin narx 30% oshadi, Bugun tugaydi..."
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
                                ></textarea>
                                <p class="text-sm text-red-600 dark:text-red-400 mt-2">
                                    Vaqt cheklovlari, narx o'zgarishi, bonuslar tugashi
                                </p>
                            </div>

                            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl flex items-start gap-3">
                                <LightBulbIcon class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0" />
                                <span class="text-sm text-yellow-800 dark:text-yellow-300">
                                    <strong>MUHIM:</strong> Scarcity va Urgency haqiqiy bo'lishi kerak. Yolg'on cheklovlar ishonchni yo'qotadi.
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Step 6: Review -->
                    <div v-if="currentStep === 6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl">
                                <CheckCircleIcon class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Ko'rib Chiqish</h2>
                        </div>

                        <div class="space-y-4">
                            <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700">
                                <h3 class="font-semibold text-gray-600 dark:text-gray-400 text-sm mb-1">Offer Nomi</h3>
                                <p class="text-gray-900 dark:text-gray-100 font-medium">{{ form.name || '-' }}</p>
                            </div>

                            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-700">
                                <h3 class="font-semibold text-green-600 dark:text-green-400 text-sm mb-1">Narx</h3>
                                <p class="text-gray-900 dark:text-gray-100 font-medium">
                                    {{ form.pricing ? new Intl.NumberFormat('uz-UZ').format(form.pricing) + ' so\'m' : '-' }}
                                    <span class="text-gray-500 dark:text-gray-400">({{ form.pricing_model }})</span>
                                </p>
                            </div>

                            <div v-if="form.guarantee_type" class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-700">
                                <h3 class="font-semibold text-blue-600 dark:text-blue-400 text-sm mb-1">Kafolat</h3>
                                <p class="text-gray-900 dark:text-gray-100 font-medium">{{ form.guarantee_type }} ({{ form.guarantee_period_days }} kun)</p>
                            </div>

                            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-700">
                                <h3 class="font-semibold text-yellow-600 dark:text-yellow-400 text-sm mb-3">Value Equation</h3>
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div class="flex items-center justify-between p-2 bg-white dark:bg-gray-800 rounded-lg">
                                        <span class="text-gray-600 dark:text-gray-400">Dream Outcome:</span>
                                        <span class="font-bold text-gray-900 dark:text-gray-100">{{ form.dream_outcome_score }}/10</span>
                                    </div>
                                    <div class="flex items-center justify-between p-2 bg-white dark:bg-gray-800 rounded-lg">
                                        <span class="text-gray-600 dark:text-gray-400">Likelihood:</span>
                                        <span class="font-bold text-gray-900 dark:text-gray-100">{{ form.perceived_likelihood_score }}/10</span>
                                    </div>
                                    <div class="flex items-center justify-between p-2 bg-white dark:bg-gray-800 rounded-lg">
                                        <span class="text-gray-600 dark:text-gray-400">Time Delay:</span>
                                        <span class="font-bold text-gray-900 dark:text-gray-100">{{ form.time_delay_days }} kun</span>
                                    </div>
                                    <div class="flex items-center justify-between p-2 bg-white dark:bg-gray-800 rounded-lg">
                                        <span class="text-gray-600 dark:text-gray-400">Effort:</span>
                                        <span class="font-bold text-gray-900 dark:text-gray-100">{{ form.effort_score }}/10</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Status
                                </label>
                                <select
                                    v-model="form.status"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-gray-100 transition-colors"
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
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                        Orqaga
                    </button>
                    <div v-else></div>

                    <div class="flex gap-3">
                        <button
                            v-if="currentStep < totalSteps"
                            @click="nextStep"
                            :disabled="!canGoNext"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-300 dark:disabled:bg-gray-700 text-white disabled:text-gray-500 dark:disabled:text-gray-400 font-semibold rounded-xl transition-all shadow-lg shadow-purple-500/30 disabled:shadow-none"
                        >
                            Keyingisi
                            <ArrowRightIcon class="w-5 h-5" />
                        </button>

                        <template v-else>
                            <button
                                @click="submitWithoutAI"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-semibold rounded-xl transition-all"
                            >
                                <CheckIcon class="w-5 h-5" />
                                AI-siz Saqlash
                            </button>
                            <button
                                @click="submitWithAI"
                                :disabled="form.processing || generatingAI"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-purple-500/30 transition-all"
                            >
                                <SparklesIcon class="w-5 h-5" />
                                <span v-if="generatingAI">AI Yaratmoqda...</span>
                                <span v-else>AI Offer Yaratish</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
