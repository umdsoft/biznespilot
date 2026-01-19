<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
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
    PencilIcon,
    TrashIcon,
    DocumentDuplicateIcon,
    SparklesIcon,
    GiftIcon,
    ShieldCheckIcon,
    CurrencyDollarIcon,
    ClockIcon,
    FireIcon,
    LightBulbIcon,
    ChartBarIcon,
    BeakerIcon,
    UserGroupIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    StarIcon,
    TrophyIcon,
    RocketLaunchIcon,
    CalendarDaysIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';
import { StarIcon as StarSolidIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    offer: {
        type: Object,
        required: true,
    },
    components: {
        type: Array,
        default: () => [],
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

const generatingVariations = ref(false);
const variations = ref(null);
const showDeleteModal = ref(false);

const getStatusConfig = (status) => {
    const configs = {
        draft: {
            label: t('offers.status.draft'),
            color: 'bg-gray-100 text-gray-700 border-gray-300',
            icon: PencilIcon,
            iconColor: 'text-gray-500'
        },
        active: {
            label: t('offers.status.active'),
            color: 'bg-green-100 text-green-700 border-green-300',
            icon: CheckCircleIcon,
            iconColor: 'text-green-500'
        },
        paused: {
            label: t('offers.status.paused'),
            color: 'bg-yellow-100 text-yellow-700 border-yellow-300',
            icon: ExclamationTriangleIcon,
            iconColor: 'text-yellow-500'
        },
        archived: {
            label: t('offers.status.archived'),
            color: 'bg-red-100 text-red-700 border-red-300',
            icon: TrashIcon,
            iconColor: 'text-red-500'
        },
    };
    return configs[status] || configs.draft;
};

const duplicateOffer = () => {
    router.post(getRoute('duplicate', props.offer.id));
};

const confirmDelete = () => {
    showDeleteModal.value = true;
};

const deleteOffer = () => {
    router.delete(getRoute('destroy', props.offer.id));
    showDeleteModal.value = false;
};

const generateVariations = async () => {
    generatingVariations.value = true;

    try {
        const response = await fetch(getRoute('generate-variations', props.offer.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const data = await response.json();
        variations.value = data.variations;
    } catch (error) {
        console.error('Variations generation error:', error);
    } finally {
        generatingVariations.value = false;
    }
};

const formatPrice = (price) => {
    if (!price) return '0 ' + t('offers.currency');
    return new Intl.NumberFormat('uz-UZ').format(price) + ' ' + t('offers.currency');
};

const calculateValueScore = computed(() => {
    if (!props.offer.dream_outcome_score) return null;
    const dream = props.offer.dream_outcome_score || 0;
    const likelihood = props.offer.perceived_likelihood_score || 0;
    const time = props.offer.time_delay_days || 1;
    const effort = props.offer.effort_score || 1;
    return ((dream * likelihood) / (time * effort)).toFixed(2);
});

const getValueScoreLevel = computed(() => {
    const score = parseFloat(calculateValueScore.value);
    if (score >= 8) return { label: t('offers.score_excellent'), color: "text-green-600", bg: "bg-green-50" };
    if (score >= 5) return { label: t('offers.score_good'), color: "text-blue-600", bg: "bg-blue-50" };
    if (score >= 3) return { label: t('offers.score_average'), color: "text-yellow-600", bg: "bg-yellow-50" };
    return { label: t('offers.score_needs_improvement'), color: "text-red-600", bg: "bg-red-50" };
});

const getGuaranteeLabel = (type) => {
    const labels = {
        'unconditional': t('offers.guarantee_type.unconditional'),
        'conditional': t('offers.guarantee_type.conditional'),
        'performance': t('offers.guarantee_type.performance'),
        'hybrid': t('offers.guarantee_type.hybrid'),
        'anti-guarantee': t('offers.guarantee_type.anti'),
    };
    return labels[type] || type;
};
</script>

<template>
    <component :is="layoutComponent" :title="offer.name">
        <Head :title="offer.name" />

        <div class="py-6 min-h-screen bg-gradient-to-br from-gray-50 via-white to-purple-50/30 dark:from-gray-900 dark:via-gray-800 dark:to-purple-900/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Orqaga qaytish -->
                <div class="mb-6">
                    <Link
                        :href="getRoute('index')"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600 transition-all"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                        {{ t('offers.back') }}
                    </Link>
                </div>

                <!-- Hero Card -->
                <div class="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 rounded-2xl shadow-2xl p-8 text-white mb-8 relative overflow-hidden">
                    <!-- Decorative elements -->
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-32 translate-x-32"></div>
                    <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full translate-y-24 -translate-x-24"></div>

                    <div class="relative z-10">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                            <div class="flex-1">
                                <!-- Status va AI badge -->
                                <div class="flex flex-wrap items-center gap-3 mb-4">
                                    <span
                                        :class="getStatusConfig(offer.status).color"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold border backdrop-blur-sm"
                                    >
                                        <component :is="getStatusConfig(offer.status).icon" class="w-4 h-4" />
                                        {{ getStatusConfig(offer.status).label }}
                                    </span>
                                    <span v-if="offer.metadata?.ai_generated" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/20 backdrop-blur-sm rounded-full text-sm font-semibold">
                                        <SparklesIcon class="w-4 h-4" />
                                        {{ t('offers.ai_generated') }}
                                    </span>
                                </div>

                                <!-- Taklif nomi va sarlavha -->
                                <div class="flex items-start gap-4 mb-4">
                                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center flex-shrink-0">
                                        <GiftIcon class="w-8 h-8" />
                                    </div>
                                    <div>
                                        <h1 class="text-3xl lg:text-4xl font-bold mb-2">{{ offer.name }}</h1>
                                        <p v-if="offer.metadata?.headline" class="text-lg text-purple-100">
                                            {{ offer.metadata.headline }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Tavsif -->
                                <p v-if="offer.description" class="text-purple-100 text-lg leading-relaxed max-w-3xl">
                                    {{ offer.description }}
                                </p>
                            </div>

                            <!-- Amallar -->
                            <div class="flex flex-wrap gap-2">
                                <Link
                                    :href="getRoute('edit', offer.id)"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-purple-700 font-semibold rounded-xl hover:bg-purple-50 transition-all shadow-lg"
                                >
                                    <PencilIcon class="w-5 h-5" />
                                    {{ t('offers.edit') }}
                                </Link>
                                <button
                                    @click="duplicateOffer"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-medium rounded-xl transition-all"
                                    :title="t('offers.duplicate')"
                                >
                                    <DocumentDuplicateIcon class="w-5 h-5" />
                                    {{ t('offers.duplicate') }}
                                </button>
                                <button
                                    @click="confirmDelete"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-500/80 hover:bg-red-600 text-white font-medium rounded-xl transition-all"
                                    :title="t('common.delete')"
                                >
                                    <TrashIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column (2/3) -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Asosiy Taklif -->
                        <div v-if="offer.core_offer" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 border-b border-purple-100 dark:border-purple-800">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center">
                                        <GiftIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    {{ t('offers.core_offer') }}
                                </h2>
                            </div>
                            <div class="p-6">
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed text-lg">{{ offer.core_offer }}</p>
                            </div>
                        </div>

                        <!-- Qiymat Taklifi -->
                        <div v-if="offer.value_proposition" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="px-6 py-4 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/30 dark:to-orange-900/30 border-b border-yellow-100 dark:border-yellow-800">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900/50 rounded-lg flex items-center justify-center">
                                        <LightBulbIcon class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                                    </div>
                                    {{ t('offers.value_proposition') }}
                                </h2>
                            </div>
                            <div class="p-6">
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed text-lg">{{ offer.value_proposition }}</p>
                            </div>
                        </div>

                        <!-- Narx va Qiymat -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <CurrencyDollarIcon class="w-7 h-7 text-white" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('offers.selling_price') }}</p>
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatPrice(offer.pricing) }}</p>
                                    </div>
                                </div>
                                <p v-if="offer.pricing_model" class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 px-3 py-2 rounded-lg">
                                    {{ offer.pricing_model }}
                                </p>
                            </div>

                            <div v-if="offer.total_value" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <TrophyIcon class="w-7 h-7 text-white" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('offers.total_value') }}</p>
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatPrice(offer.total_value) }}</p>
                                    </div>
                                </div>
                                <div v-if="offer.total_value && offer.pricing" class="text-sm text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30 px-3 py-2 rounded-lg font-semibold">
                                    {{ t('offers.more_value', { percent: Math.round((offer.total_value / offer.pricing - 1) * 100) }) }}
                                </div>
                            </div>
                        </div>

                        <!-- Qiymat Tenglamasi ($100M Offers) -->
                        <div v-if="offer.dream_outcome_score" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/30 dark:to-purple-900/30 border-b border-indigo-100 dark:border-indigo-800">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                                        <ChartBarIcon class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                                    </div>
                                    {{ t('offers.value_equation') }}
                                    <span class="text-xs bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 px-2 py-1 rounded-full ml-2">$100M Offers</span>
                                </h2>
                            </div>
                            <div class="p-6">
                                <!-- Formula ko'rsatish -->
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 mb-6 text-center">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ t('offers.value_formula') }}</p>
                                    <p class="text-lg font-mono font-semibold text-gray-800 dark:text-gray-200">
                                        {{ t('offers.formula_text') }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div class="p-4 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-700 rounded-xl">
                                        <div class="flex items-center gap-2 mb-2">
                                            <StarSolidIcon class="w-5 h-5 text-green-500" />
                                            <p class="text-sm font-semibold text-green-700 dark:text-green-400">{{ t('offers.dream_outcome') }}</p>
                                        </div>
                                        <p class="text-4xl font-bold text-green-800 dark:text-green-300">{{ offer.dream_outcome_score }}<span class="text-lg">/10</span></p>
                                    </div>
                                    <div class="p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-2 border-blue-200 dark:border-blue-700 rounded-xl">
                                        <div class="flex items-center gap-2 mb-2">
                                            <RocketLaunchIcon class="w-5 h-5 text-blue-500" />
                                            <p class="text-sm font-semibold text-blue-700 dark:text-blue-400">{{ t('offers.success_likelihood') }}</p>
                                        </div>
                                        <p class="text-4xl font-bold text-blue-800 dark:text-blue-300">{{ offer.perceived_likelihood_score }}<span class="text-lg">/10</span></p>
                                    </div>
                                    <div class="p-4 bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 border-2 border-orange-200 dark:border-orange-700 rounded-xl">
                                        <div class="flex items-center gap-2 mb-2">
                                            <ClockIcon class="w-5 h-5 text-orange-500" />
                                            <p class="text-sm font-semibold text-orange-700 dark:text-orange-400">{{ t('offers.time_to_result') }}</p>
                                        </div>
                                        <p class="text-4xl font-bold text-orange-800 dark:text-orange-300">{{ offer.time_delay_days }} <span class="text-lg">{{ t('offers.days') }}</span></p>
                                    </div>
                                    <div class="p-4 bg-gradient-to-br from-purple-50 to-fuchsia-50 dark:from-purple-900/20 dark:to-fuchsia-900/20 border-2 border-purple-200 dark:border-purple-700 rounded-xl">
                                        <div class="flex items-center gap-2 mb-2">
                                            <FireIcon class="w-5 h-5 text-purple-500" />
                                            <p class="text-sm font-semibold text-purple-700 dark:text-purple-400">{{ t('offers.required_effort') }}</p>
                                        </div>
                                        <p class="text-4xl font-bold text-purple-800 dark:text-purple-300">{{ offer.effort_score }}<span class="text-lg">/10</span></p>
                                    </div>
                                </div>

                                <!-- Yakuniy Ball -->
                                <div class="p-6 bg-gradient-to-r from-yellow-100 via-orange-100 to-red-100 dark:from-yellow-900/30 dark:via-orange-900/30 dark:to-red-900/30 border-2 border-yellow-300 dark:border-yellow-600 rounded-xl">
                                    <div class="text-center">
                                        <p class="text-sm font-bold text-yellow-700 dark:text-yellow-400 uppercase tracking-wide mb-2">{{ t('offers.final_value_score') }}</p>
                                        <div class="flex items-center justify-center gap-3">
                                            <TrophyIcon class="w-10 h-10 text-yellow-600" />
                                            <p class="text-6xl font-black text-yellow-800 dark:text-yellow-300">{{ calculateValueScore || offer.value_score }}</p>
                                        </div>
                                        <p :class="[getValueScoreLevel.color, 'text-sm font-semibold mt-2']">
                                            {{ getValueScoreLevel.label }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bonus Stack -->
                        <div v-if="components.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="px-6 py-4 bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/30 dark:to-yellow-900/30 border-b border-amber-100 dark:border-amber-800">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <div class="w-8 h-8 bg-amber-100 dark:bg-amber-900/50 rounded-lg flex items-center justify-center">
                                        <GiftIcon class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                                    </div>
                                    {{ t('offers.bonus_stack') }}
                                    <span class="text-sm bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200 px-2.5 py-0.5 rounded-full font-semibold ml-2">
                                        {{ t('offers.bonus_count', { count: components.length }) }}
                                    </span>
                                </h2>
                            </div>
                            <div class="p-6">
                                <div class="space-y-4">
                                    <div
                                        v-for="(component, index) in components"
                                        :key="component.id"
                                        class="p-5 border-2 rounded-xl transition-all hover:shadow-md"
                                        :class="component.is_highlighted
                                            ? 'border-yellow-400 bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 shadow-yellow-100 dark:shadow-none'
                                            : 'border-gray-200 dark:border-gray-600 hover:border-purple-200 dark:hover:border-purple-600'"
                                    >
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <span class="inline-flex items-center justify-center w-8 h-8 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 font-bold text-sm rounded-lg">
                                                        {{ index + 1 }}
                                                    </span>
                                                    <h3 class="font-bold text-gray-900 dark:text-white text-lg">{{ component.name }}</h3>
                                                    <StarSolidIcon v-if="component.is_highlighted" class="w-5 h-5 text-yellow-500" />
                                                </div>
                                                <p v-if="component.description" class="text-gray-600 dark:text-gray-400 ml-11">
                                                    {{ component.description }}
                                                </p>
                                            </div>
                                            <div v-if="component.value" class="text-right flex-shrink-0">
                                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ t('offers.value') }}</p>
                                                <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ formatPrice(component.value) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kafolat -->
                        <div v-if="offer.guarantee_type" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 border-b border-green-100 dark:border-green-800">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/50 rounded-lg flex items-center justify-center">
                                        <ShieldCheckIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                                    </div>
                                    {{ t('offers.guarantee') }}
                                </h2>
                            </div>
                            <div class="p-6">
                                <div class="p-6 bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50 dark:from-green-900/20 dark:via-emerald-900/20 dark:to-teal-900/20 border-2 border-green-300 dark:border-green-600 rounded-xl">
                                    <div class="flex items-start gap-4">
                                        <div class="w-14 h-14 bg-green-500 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg">
                                            <ShieldCheckIcon class="w-7 h-7 text-white" />
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-xl font-bold text-green-800 dark:text-green-300 mb-2">
                                                {{ getGuaranteeLabel(offer.guarantee_type) }}
                                            </h3>
                                            <p v-if="offer.guarantee_terms" class="text-gray-700 dark:text-gray-300 mb-4 leading-relaxed">
                                                {{ offer.guarantee_terms }}
                                            </p>
                                            <div class="flex items-center gap-2 text-sm text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/50 px-4 py-2 rounded-lg inline-flex">
                                                <CalendarDaysIcon class="w-5 h-5" />
                                                <span class="font-semibold">{{ t('offers.guarantee_period', { days: offer.guarantee_period_days }) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cheklov va Shoshilinchlik -->
                        <div v-if="offer.scarcity || offer.urgency" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div v-if="offer.scarcity" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                                <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/30 dark:to-cyan-900/30 border-b border-blue-100 dark:border-blue-800">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center">
                                            <ClockIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        {{ t('offers.scarcity') }}
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-600 rounded-xl">
                                        <p class="text-gray-800 dark:text-gray-200 leading-relaxed">{{ offer.scarcity }}</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="offer.urgency" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                                <div class="px-6 py-4 bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/30 dark:to-orange-900/30 border-b border-red-100 dark:border-red-800">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <div class="w-8 h-8 bg-red-100 dark:bg-red-900/50 rounded-lg flex items-center justify-center">
                                            <FireIcon class="w-5 h-5 text-red-600 dark:text-red-400" />
                                        </div>
                                        {{ t('offers.urgency') }}
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <div class="p-4 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-600 rounded-xl">
                                        <p class="text-gray-800 dark:text-gray-200 leading-relaxed">{{ offer.urgency }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column (1/3) -->
                    <div class="space-y-6">
                        <!-- Performance Stats -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-700/50 dark:to-slate-700/50 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <ChartBarIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                                    {{ t('offers.stats') }}
                                </h3>
                            </div>
                            <div class="p-6 space-y-5">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ t('offers.conversion_rate') }}</p>
                                        <span class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ offer.conversion_rate || 0 }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                        <div
                                            class="h-full bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full transition-all duration-500"
                                            :style="{ width: (offer.conversion_rate || 0) + '%' }"
                                        ></div>
                                    </div>
                                </div>

                                <div class="pt-4 border-t dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <CalendarDaysIcon class="w-5 h-5 text-gray-400" />
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('offers.created_date') }}</p>
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ offer.created_at }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- AI Testing Tools -->
                        <div class="bg-gradient-to-br from-purple-600 via-indigo-600 to-blue-600 rounded-2xl shadow-xl p-6 text-white relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-16 translate-x-16"></div>
                            <div class="relative z-10">
                                <h3 class="text-lg font-bold mb-2 flex items-center gap-2">
                                    <BeakerIcon class="w-6 h-6" />
                                    {{ t('offers.ab_test') }}
                                </h3>
                                <p class="text-purple-100 text-sm mb-4">{{ t('offers.ab_test_desc') }}</p>
                                <button
                                    @click="generateVariations"
                                    :disabled="generatingVariations"
                                    class="w-full px-4 py-3 bg-white text-purple-700 font-semibold rounded-xl hover:bg-purple-50 transition-all shadow-lg flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
                                >
                                    <SparklesIcon class="w-5 h-5" :class="{ 'animate-spin': generatingVariations }" />
                                    <span v-if="generatingVariations">{{ t('offers.generating') }}</span>
                                    <span v-else>{{ t('offers.generate_variation') }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Maqsadli Auditoriya -->
                        <div v-if="offer.target_audience" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="px-6 py-4 bg-gradient-to-r from-teal-50 to-cyan-50 dark:from-teal-900/30 dark:to-cyan-900/30 border-b border-teal-100 dark:border-teal-800">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <div class="w-8 h-8 bg-teal-100 dark:bg-teal-900/50 rounded-lg flex items-center justify-center">
                                        <UserGroupIcon class="w-5 h-5 text-teal-600 dark:text-teal-400" />
                                    </div>
                                    {{ t('offers.target_audience') }}
                                </h3>
                            </div>
                            <div class="p-6">
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ offer.target_audience }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generated Variations -->
                <div v-if="variations?.length" class="mt-8 bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 border-b border-purple-100 dark:border-purple-800">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <BeakerIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            {{ t('offers.variations') }}
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div
                                v-for="(variant, index) in variations"
                                :key="index"
                                class="p-6 border-2 border-purple-200 dark:border-purple-700 rounded-xl hover:border-purple-400 dark:hover:border-purple-500 hover:shadow-lg transition-all bg-gradient-to-br from-white to-purple-50/50 dark:from-gray-800 dark:to-purple-900/20"
                            >
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300 text-xs font-semibold rounded-full mb-3">
                                    <SparklesIcon class="w-3 h-3" />
                                    {{ variant.variant_name }}
                                </span>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">{{ variant.headline }}</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-4 text-sm leading-relaxed">{{ variant.subheadline }}</p>
                                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg mb-3">
                                    <p class="text-purple-700 dark:text-purple-300 font-semibold text-center">{{ variant.main_cta }}</p>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-500 italic">{{ variant.key_changes }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ t('offers.delete_title') }}</h3>
                            <button @click="showDeleteModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <XMarkIcon class="w-6 h-6" />
                            </button>
                        </div>

                        <div class="mb-6">
                            <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                                <TrashIcon class="w-8 h-8 text-red-600 dark:text-red-400" />
                            </div>
                            <p class="text-center text-gray-600 dark:text-gray-400">
                                {{ t('offers.delete_confirm', { name: offer.name }) }}
                            </p>
                            <p class="text-center text-sm text-red-500 mt-2">{{ t('offers.delete_warning') }}</p>
                        </div>

                        <div class="flex gap-3">
                            <button
                                @click="showDeleteModal = false"
                                class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-all"
                            >
                                {{ t('common.cancel') }}
                            </button>
                            <button
                                @click="deleteOffer"
                                class="flex-1 px-4 py-3 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700 transition-all"
                            >
                                {{ t('common.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </component>
</template>
