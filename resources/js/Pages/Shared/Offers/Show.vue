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
    ShieldCheckIcon,
    ClockIcon,
    FireIcon,
    XMarkIcon,
    UserGroupIcon,
    CurrencyDollarIcon,
    ChartBarIcon,
    GiftIcon,
    CheckCircleIcon,
    TagIcon,
} from '@heroicons/vue/24/outline';

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

const { getRoute } = useOfferRoutes(props.panelType);

const generatingVariations = ref(false);
const variations = ref(null);
const showDeleteModal = ref(false);

const statusMap = {
    draft: { label: 'Qoralama', class: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', dot: 'bg-gray-400' },
    active: { label: 'Faol', class: 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400', dot: 'bg-green-500' },
    paused: { label: 'Pauza', class: 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400', dot: 'bg-yellow-500' },
    archived: { label: 'Arxiv', class: 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400', dot: 'bg-red-500' },
};

const duplicateOffer = () => {
    router.post(getRoute('duplicate', props.offer.id));
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
    if (!price) return '0 so\'m';
    return new Intl.NumberFormat('uz-UZ').format(price) + ' so\'m';
};

const valueScore = computed(() => {
    if (!props.offer.dream_outcome_score) return null;
    const dream = props.offer.dream_outcome_score || 0;
    const likelihood = props.offer.perceived_likelihood_score || 0;
    const time = props.offer.time_delay_days || 1;
    const effort = props.offer.effort_score || 1;
    return ((dream * likelihood) / (time * effort) * 100).toFixed(1);
});

const pricingModelLabels = {
    'one-time': 'Bir martalik',
    'monthly': 'Oylik',
    'yearly': 'Yillik',
    'payment-plan': 'Bo\'lib to\'lash',
};

const bonusTotal = computed(() => {
    return props.components.reduce((sum, c) => sum + (parseFloat(c.value) || 0), 0);
});

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>

<template>
    <component :is="layoutComponent" :title="offer.name">
        <Head :title="offer.name" />

        <div class="py-6">
            <div class="mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Breadcrumb + Actions -->
                <div class="mb-8">
                    <Link
                        :href="getRoute('index')"
                        class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors mb-4"
                    >
                        <ArrowLeftIcon class="w-4 h-4 mr-1.5" />
                        Barcha takliflar
                    </Link>

                    <div class="flex items-start justify-between gap-6">
                        <div class="min-w-0">
                            <div class="flex items-center gap-3 mb-2">
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">{{ offer.name }}</h1>
                                <span
                                    :class="statusMap[offer.status]?.class"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap"
                                >
                                    <span :class="statusMap[offer.status]?.dot" class="w-1.5 h-1.5 rounded-full"></span>
                                    {{ statusMap[offer.status]?.label }}
                                </span>
                                <span v-if="offer.metadata?.ai_generated" class="inline-flex items-center gap-1 px-2 py-0.5 bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-full text-xs font-medium">
                                    <SparklesIcon class="w-3 h-3" />
                                    AI
                                </span>
                            </div>
                            <p v-if="offer.description" class="text-sm text-gray-500 dark:text-gray-400 max-w-2xl leading-relaxed">{{ offer.description }}</p>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            <Link
                                :href="getRoute('edit', offer.id)"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all hover:shadow"
                            >
                                <PencilIcon class="w-4 h-4" />
                                Tahrirlash
                            </Link>
                            <button
                                @click="duplicateOffer"
                                class="inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors"
                            >
                                <DocumentDuplicateIcon class="w-4 h-4" />
                                Nusxa
                            </button>
                            <button
                                @click="showDeleteModal = true"
                                class="inline-flex items-center p-2 border border-gray-200 dark:border-gray-600 hover:bg-red-50 dark:hover:bg-red-900/20 hover:border-red-300 dark:hover:border-red-700 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg transition-all"
                            >
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-sm transition-shadow">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                                <CurrencyDollarIcon class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Narx</span>
                        </div>
                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ formatPrice(offer.pricing) }}</p>
                        <span v-if="offer.pricing_model" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 block">
                            {{ pricingModelLabels[offer.pricing_model] || offer.pricing_model }}
                        </span>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-sm transition-shadow">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center">
                                <TagIcon class="w-4 h-4 text-green-600 dark:text-green-400" />
                            </div>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Umumiy qiymat</span>
                        </div>
                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            {{ offer.total_value ? formatPrice(offer.total_value) : '—' }}
                        </p>
                        <span v-if="offer.total_value && offer.pricing && offer.total_value > offer.pricing" class="text-xs text-green-600 dark:text-green-400 font-semibold mt-0.5 block">
                            {{ Math.round((offer.total_value / offer.pricing - 1) * 100) }}% ko'proq qiymat
                        </span>
                        <span v-else class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 block">Bonuslar bilan</span>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-sm transition-shadow">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center">
                                <ChartBarIcon class="w-4 h-4 text-violet-600 dark:text-violet-400" />
                            </div>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Qiymat bahosi</span>
                        </div>
                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ valueScore || '—' }}</p>
                        <span class="text-xs mt-0.5 block" :class="valueScore && parseFloat(valueScore) >= 50 ? 'text-green-600 dark:text-green-400 font-medium' : 'text-gray-400 dark:text-gray-500'">
                            {{ valueScore ? (parseFloat(valueScore) >= 50 ? 'Kuchli taklif' : 'Yaxshilash mumkin') : 'Ma\'lumot kerak' }}
                        </span>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-sm transition-shadow">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center">
                                <ChartBarIcon class="w-4 h-4 text-orange-600 dark:text-orange-400" />
                            </div>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Konversiya</span>
                        </div>
                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ offer.conversion_rate ? offer.conversion_rate + '%' : '—' }}</p>
                        <span class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 block">Sotuvga nisbat</span>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Left Column (2/3) -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Value Proposition — Hero Card -->
                        <div v-if="offer.value_proposition" class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/10 dark:to-indigo-900/10 rounded-xl border border-blue-100 dark:border-blue-800/30 p-6">
                            <div class="flex items-center gap-2 mb-3">
                                <CheckCircleIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-300">Mijoz nimaga erishadi</h3>
                            </div>
                            <p class="text-gray-800 dark:text-gray-200 leading-relaxed text-[15px]">{{ offer.value_proposition }}</p>
                        </div>

                        <!-- Core Offer -->
                        <div v-if="offer.core_offer" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                            <div class="flex items-center gap-2 mb-3">
                                <GiftIcon class="w-5 h-5 text-gray-400 dark:text-gray-500" />
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Mijoz nimani oladi</h3>
                            </div>
                            <p class="text-gray-600 dark:text-gray-300 leading-relaxed">{{ offer.core_offer }}</p>
                        </div>

                        <!-- Bonus Stack -->
                        <div v-if="components.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <GiftIcon class="w-5 h-5 text-gray-400 dark:text-gray-500" />
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Bonuslar</h3>
                                    <span class="ml-1 px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-medium text-gray-500 dark:text-gray-400">{{ components.length }}</span>
                                </div>
                                <span v-if="bonusTotal > 0" class="text-sm font-bold text-green-600 dark:text-green-400">
                                    {{ formatPrice(bonusTotal) }}
                                </span>
                            </div>
                            <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                <div
                                    v-for="(component, index) in components"
                                    :key="component.id"
                                    class="flex items-center justify-between gap-4 px-6 py-4 hover:bg-gray-50/50 dark:hover:bg-gray-750 transition-colors"
                                >
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <span class="w-7 h-7 flex items-center justify-center bg-blue-600 dark:bg-blue-500 rounded-lg text-xs font-bold text-white flex-shrink-0">
                                            {{ index + 1 }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-900 dark:text-gray-100 text-sm">{{ component.name }}</p>
                                            <p v-if="component.description" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1">{{ component.description }}</p>
                                        </div>
                                    </div>
                                    <span v-if="component.value" class="text-sm font-semibold text-green-600 dark:text-green-400 whitespace-nowrap tabular-nums">
                                        {{ formatPrice(component.value) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- AI Variations -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <SparklesIcon class="w-5 h-5 text-violet-500 dark:text-violet-400" />
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">AI variatsiyalar</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Turli xil versiyalarni sinab ko'ring</p>
                                    </div>
                                </div>
                                <button
                                    @click="generateVariations"
                                    :disabled="generatingVariations"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-violet-600 hover:bg-violet-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white text-sm font-medium rounded-lg shadow-sm transition-all hover:shadow"
                                >
                                    <SparklesIcon class="w-4 h-4" :class="{ 'animate-spin': generatingVariations }" />
                                    <span v-if="generatingVariations">Yaratilmoqda...</span>
                                    <span v-else>Yaratish</span>
                                </button>
                            </div>

                            <div v-if="variations?.length" class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div
                                    v-for="(variant, index) in variations"
                                    :key="index"
                                    class="group relative p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-100 dark:border-gray-600 hover:border-violet-200 dark:hover:border-violet-700 transition-colors"
                                >
                                    <span class="inline-block text-[10px] font-bold text-violet-600 dark:text-violet-400 uppercase tracking-wider mb-2">{{ variant.variant_name }}</span>
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 text-sm leading-snug">{{ variant.headline }}</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 line-clamp-2 leading-relaxed">{{ variant.subheadline }}</p>
                                    <div class="mt-3 px-3 py-2 bg-violet-50 dark:bg-violet-900/20 rounded-lg text-center">
                                        <p class="text-xs font-semibold text-violet-700 dark:text-violet-300">{{ variant.main_cta }}</p>
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-2 line-clamp-2 leading-relaxed">{{ variant.key_changes }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Sidebar (1/3) -->
                    <div class="space-y-5">

                        <!-- Target Audience -->
                        <div v-if="offer.target_audience" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <UserGroupIcon class="w-4.5 h-4.5 text-blue-500 dark:text-blue-400" />
                                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Kimlar uchun</h3>
                            </div>
                            <p class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed">{{ offer.target_audience }}</p>
                        </div>

                        <!-- Guarantee -->
                        <div v-if="offer.guarantee_type" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <ShieldCheckIcon class="w-4.5 h-4.5 text-green-500 dark:text-green-400" />
                                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Kafolat</h3>
                            </div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ offer.guarantee_type }}</p>
                            <p v-if="offer.guarantee_terms" class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">{{ offer.guarantee_terms }}</p>
                            <div v-if="offer.guarantee_period_days" class="mt-3 inline-flex items-center gap-1.5 px-2.5 py-1 bg-green-50 dark:bg-green-900/20 rounded-lg text-xs font-semibold text-green-700 dark:text-green-400">
                                <ClockIcon class="w-3.5 h-3.5" />
                                {{ offer.guarantee_period_days }} kun
                            </div>
                        </div>

                        <!-- Scarcity -->
                        <div v-if="offer.scarcity" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <ClockIcon class="w-4.5 h-4.5 text-amber-500 dark:text-amber-400" />
                                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Cheklangan miqdor</h3>
                            </div>
                            <p class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed">{{ offer.scarcity }}</p>
                        </div>

                        <!-- Urgency -->
                        <div v-if="offer.urgency" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <FireIcon class="w-4.5 h-4.5 text-red-500 dark:text-red-400" />
                                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Vaqt cheklovi</h3>
                            </div>
                            <p class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed">{{ offer.urgency }}</p>
                        </div>

                        <!-- Meta Info -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Ma'lumot</h3>
                            <dl class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Yaratilgan</dt>
                                    <dd class="text-xs text-gray-900 dark:text-gray-100 font-medium">{{ formatDate(offer.created_at) }}</dd>
                                </div>
                                <div v-if="offer.updated_at" class="flex justify-between items-center">
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Yangilangan</dt>
                                    <dd class="text-xs text-gray-900 dark:text-gray-100 font-medium">{{ formatDate(offer.updated_at) }}</dd>
                                </div>
                                <div v-if="offer.pricing_model" class="flex justify-between items-center">
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">To'lov turi</dt>
                                    <dd class="text-xs text-gray-900 dark:text-gray-100 font-medium">{{ pricingModelLabels[offer.pricing_model] || offer.pricing_model }}</dd>
                                </div>
                                <div v-if="components.length" class="flex justify-between items-center">
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Bonuslar</dt>
                                    <dd class="text-xs text-gray-900 dark:text-gray-100 font-medium">{{ components.length }} ta</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                        <Transition
                            enter-active-class="transition duration-200 ease-out"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition duration-150 ease-in"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div v-if="showDeleteModal" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-sm w-full p-6">
                                <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center">
                                    <TrashIcon class="w-6 h-6 text-red-500" />
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 text-center mb-1">O'chirish</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-1">
                                    <strong>"{{ offer.name }}"</strong> taklifini o'chirmoqchimisiz?
                                </p>
                                <p class="text-xs text-red-500 text-center mb-6">Bu amalni qaytarib bo'lmaydi.</p>
                                <div class="flex gap-3">
                                    <button
                                        @click="showDeleteModal = false"
                                        class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 text-sm transition-colors"
                                    >
                                        Bekor qilish
                                    </button>
                                    <button
                                        @click="deleteOffer"
                                        class="flex-1 px-4 py-2.5 bg-red-600 text-white font-medium rounded-xl hover:bg-red-700 text-sm transition-colors shadow-sm"
                                    >
                                        O'chirish
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </component>
</template>
