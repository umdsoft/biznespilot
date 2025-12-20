<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
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
    BeakerIcon
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
});

const generatingVariations = ref(false);
const variations = ref(null);

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-800 border-gray-200',
        active: 'bg-green-100 text-green-800 border-green-200',
        paused: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        archived: 'bg-red-100 text-red-800 border-red-200',
    };
    return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Qoralama',
        active: 'Faol',
        paused: 'Pauza',
        archived: 'Arxiv',
    };
    return labels[status] || status;
};

const duplicateOffer = () => {
    router.post(route('business.offers.duplicate', props.offer.id));
};

const deleteOffer = () => {
    if (confirm(`${props.offer.name} nomli offerni o'chirishni xohlaysizmi?`)) {
        router.delete(route('business.offers.destroy', props.offer.id));
    }
};

const generateVariations = async () => {
    generatingVariations.value = true;

    try {
        const response = await fetch(route('business.offers.generate-variations', props.offer.id), {
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
    return new Intl.NumberFormat('uz-UZ').format(price) + ' so\'m';
};

const calculateValueEquation = () => {
    const numerator = props.offer.dream_outcome_score * props.offer.perceived_likelihood_score;
    const denominator = props.offer.time_delay_days * props.offer.effort_score;
    return (numerator / denominator).toFixed(2);
};
</script>

<template>
    <BusinessLayout :title="offer.name">
        <Head :title="offer.name" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('business.offers.index')"
                        class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4"
                    >
                        <ArrowLeftIcon class="w-4 h-4 mr-1" />
                        Barcha Offers
                    </Link>

                    <!-- Hero Card -->
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-700 rounded-xl shadow-xl p-8 text-white">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <GiftIcon class="w-12 h-12" />
                                    <div>
                                        <h1 class="text-3xl font-bold">{{ offer.name }}</h1>
                                        <p v-if="offer.metadata?.headline" class="text-purple-100 mt-1">
                                            {{ offer.metadata.headline }}
                                        </p>
                                    </div>
                                </div>

                                <p v-if="offer.description" class="text-purple-100 mb-4">
                                    {{ offer.description }}
                                </p>

                                <div class="flex items-center gap-3">
                                    <span
                                        :class="getStatusColor(offer.status)"
                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold border"
                                    >
                                        {{ getStatusLabel(offer.status) }}
                                    </span>
                                    <span v-if="offer.metadata?.ai_generated" class="inline-flex items-center gap-1 px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-semibold">
                                        <SparklesIcon class="w-4 h-4" />
                                        AI-Generated
                                    </span>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <Link
                                    :href="route('business.offers.edit', offer.id)"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-medium rounded-lg transition-all"
                                >
                                    <PencilIcon class="w-4 h-4" />
                                    Tahrirlash
                                </Link>
                                <button
                                    @click="duplicateOffer"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-medium rounded-lg transition-all"
                                >
                                    <DocumentDuplicateIcon class="w-4 h-4" />
                                </button>
                                <button
                                    @click="deleteOffer"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all"
                                >
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column (2/3) -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Core Offer -->
                        <div v-if="offer.core_offer" class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <GiftIcon class="w-6 h-6 text-purple-600" />
                                Core Offer
                            </h2>
                            <p class="text-gray-800 leading-relaxed">{{ offer.core_offer }}</p>
                        </div>

                        <!-- Value Proposition -->
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <LightBulbIcon class="w-6 h-6 text-yellow-600" />
                                Value Proposition
                            </h2>
                            <p class="text-gray-800 leading-relaxed">{{ offer.value_proposition }}</p>
                        </div>

                        <!-- Pricing & Value -->
                        <div class="grid grid-cols-2 gap-6">
                            <div class="bg-white rounded-xl shadow-md p-6">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                        <CurrencyDollarIcon class="w-6 h-6 text-green-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Narx</p>
                                        <p class="text-2xl font-bold text-gray-900">{{ formatPrice(offer.pricing) }}</p>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600">{{ offer.pricing_model }}</p>
                            </div>

                            <div v-if="offer.total_value" class="bg-white rounded-xl shadow-md p-6">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <SparklesIcon class="w-6 h-6 text-blue-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Total Value</p>
                                        <p class="text-2xl font-bold text-gray-900">{{ formatPrice(offer.total_value) }}</p>
                                    </div>
                                </div>
                                <p class="text-sm text-green-600 font-medium">
                                    {{ Math.round((offer.total_value / offer.pricing - 1) * 100) }}% qo'shimcha qiymat
                                </p>
                            </div>
                        </div>

                        <!-- Value Equation -->
                        <div v-if="offer.dream_outcome_score" class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <ChartBarIcon class="w-6 h-6 text-indigo-600" />
                                Value Equation ($100M Offers)
                            </h2>

                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                                    <p class="text-sm font-medium text-green-700 mb-1">Dream Outcome</p>
                                    <p class="text-3xl font-bold text-green-800">{{ offer.dream_outcome_score }}/10</p>
                                </div>
                                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                    <p class="text-sm font-medium text-blue-700 mb-1">Perceived Likelihood</p>
                                    <p class="text-3xl font-bold text-blue-800">{{ offer.perceived_likelihood_score }}/10</p>
                                </div>
                                <div class="p-4 bg-orange-50 border border-orange-200 rounded-lg">
                                    <p class="text-sm font-medium text-orange-700 mb-1">Time Delay</p>
                                    <p class="text-3xl font-bold text-orange-800">{{ offer.time_delay_days }} kun</p>
                                </div>
                                <div class="p-4 bg-purple-50 border border-purple-200 rounded-lg">
                                    <p class="text-sm font-medium text-purple-700 mb-1">Effort Required</p>
                                    <p class="text-3xl font-bold text-purple-800">{{ offer.effort_score }}/10</p>
                                </div>
                            </div>

                            <div class="p-6 bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-300 rounded-lg">
                                <div class="text-center">
                                    <p class="text-sm font-semibold text-yellow-700 mb-2">FINAL VALUE SCORE</p>
                                    <p class="text-5xl font-bold text-yellow-800 mb-2">{{ offer.value_score }}</p>
                                    <p class="text-sm text-yellow-600">
                                        Formula: ({{ offer.dream_outcome_score }} × {{ offer.perceived_likelihood_score }}) / ({{ offer.time_delay_days }} × {{ offer.effort_score }})
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Offer Components / Bonuses -->
                        <div v-if="components.length > 0" class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <GiftIcon class="w-6 h-6 text-yellow-600" />
                                Offer Stack ({{ components.length }} ta bonus)
                            </h2>
                            <div class="space-y-3">
                                <div
                                    v-for="(component, index) in components"
                                    :key="component.id"
                                    class="p-4 border-2 rounded-lg transition-all"
                                    :class="component.is_highlighted
                                        ? 'border-yellow-400 bg-yellow-50'
                                        : 'border-gray-200'"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="text-sm font-bold text-purple-600">Bonus {{ index + 1 }}</span>
                                                <h3 class="font-bold text-gray-900">{{ component.name }}</h3>
                                            </div>
                                            <p v-if="component.description" class="text-sm text-gray-700">
                                                {{ component.description }}
                                            </p>
                                        </div>
                                        <div v-if="component.value" class="text-right ml-4">
                                            <p class="text-xs text-gray-500">Qiymat</p>
                                            <p class="text-lg font-bold text-green-600">{{ formatPrice(component.value) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Stack Value -->
                            <div class="mt-4 p-4 bg-green-50 border-2 border-green-300 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-green-800">Jami Stack Value:</span>
                                    <span class="text-2xl font-bold text-green-800">
                                        {{ formatPrice(components.reduce((sum, c) => sum + (c.value || 0), 0)) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Guarantee -->
                        <div v-if="offer.guarantee_type" class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <ShieldCheckIcon class="w-6 h-6 text-green-600" />
                                Kafolat (Risk Reversal)
                            </h2>

                            <div class="p-6 bg-green-50 border-2 border-green-300 rounded-lg">
                                <h3 class="text-lg font-bold text-green-800 mb-2">{{ offer.guarantee_type }}</h3>
                                <p v-if="offer.guarantee_terms" class="text-gray-800 mb-3">{{ offer.guarantee_terms }}</p>
                                <div class="flex items-center gap-2 text-sm text-green-700">
                                    <ClockIcon class="w-4 h-4" />
                                    <span class="font-medium">{{ offer.guarantee_period_days }} kun kafolat muddati</span>
                                </div>
                            </div>
                        </div>

                        <!-- Scarcity & Urgency -->
                        <div v-if="offer.scarcity || offer.urgency" class="grid grid-cols-2 gap-6">
                            <div v-if="offer.scarcity" class="bg-white rounded-xl shadow-md p-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center gap-2">
                                    <ClockIcon class="w-5 h-5 text-blue-600" />
                                    Scarcity
                                </h3>
                                <div class="p-4 bg-blue-50 border-2 border-blue-300 rounded-lg">
                                    <p class="text-gray-800">{{ offer.scarcity }}</p>
                                </div>
                            </div>

                            <div v-if="offer.urgency" class="bg-white rounded-xl shadow-md p-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center gap-2">
                                    <FireIcon class="w-5 h-5 text-red-600" />
                                    Urgency
                                </h3>
                                <div class="p-4 bg-red-50 border-2 border-red-300 rounded-lg">
                                    <p class="text-gray-800">{{ offer.urgency }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Marketing Copy -->
                        <div v-if="offer.metadata?.subheadline || offer.metadata?.main_cta" class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Marketing Copy</h2>

                            <div class="space-y-3">
                                <div v-if="offer.metadata.subheadline" class="p-4 bg-purple-50 border border-purple-200 rounded-lg">
                                    <p class="text-sm font-medium text-purple-700 mb-1">Subheadline</p>
                                    <p class="text-gray-800">{{ offer.metadata.subheadline }}</p>
                                </div>

                                <div v-if="offer.metadata.main_cta" class="p-4 bg-indigo-50 border border-indigo-200 rounded-lg">
                                    <p class="text-sm font-medium text-indigo-700 mb-1">Call to Action</p>
                                    <p class="text-lg font-bold text-indigo-900">{{ offer.metadata.main_cta }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column (1/3) -->
                    <div class="space-y-6">
                        <!-- Stats -->
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Performance</h3>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">Conversion Rate</p>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-200 rounded-full h-3">
                                            <div
                                                class="bg-purple-600 h-3 rounded-full transition-all"
                                                :style="{ width: (offer.conversion_rate || 0) + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-lg font-bold text-purple-600">{{ offer.conversion_rate || 0 }}%</span>
                                    </div>
                                </div>

                                <div class="pt-4 border-t">
                                    <p class="text-sm text-gray-500">Yaratilgan</p>
                                    <p class="text-sm font-medium text-gray-900">{{ offer.created_at }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- AI Tools -->
                        <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl shadow-md p-6 text-white">
                            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                                <BeakerIcon class="w-6 h-6" />
                                A/B Testing
                            </h3>
                            <button
                                @click="generateVariations"
                                :disabled="generatingVariations"
                                class="w-full px-4 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-medium rounded-lg transition-all"
                            >
                                <SparklesIcon class="w-5 h-5 inline mr-2" />
                                <span v-if="generatingVariations">Yaratilmoqda...</span>
                                <span v-else>Generate Variations</span>
                            </button>
                        </div>

                        <!-- Target Audience -->
                        <div v-if="offer.target_audience" class="bg-white rounded-xl shadow-md p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-3">Target Audience</h3>
                            <p class="text-gray-700 text-sm">{{ offer.target_audience }}</p>
                        </div>
                    </div>
                </div>

                <!-- Generated Variations -->
                <div v-if="variations?.length" class="mt-8 bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">A/B Test Variations</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div
                            v-for="(variant, index) in variations"
                            :key="index"
                            class="p-6 border-2 border-purple-200 rounded-lg hover:border-purple-400 transition-all"
                        >
                            <span class="inline-block px-3 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded-full mb-3">
                                {{ variant.variant_name }}
                            </span>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">{{ variant.headline }}</h3>
                            <p class="text-gray-700 mb-3 text-sm">{{ variant.subheadline }}</p>
                            <p class="text-purple-600 font-semibold mb-3">{{ variant.main_cta }}</p>
                            <p class="text-xs text-gray-600 italic">{{ variant.key_changes }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
