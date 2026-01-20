<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    GiftIcon,
    ShieldCheckIcon,
    CheckCircleIcon,
    ClockIcon,
    FireIcon,
    PhoneIcon,
    ChatBubbleLeftRightIcon,
    StarIcon,
    SparklesIcon,
    TrophyIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon as StarSolidIcon } from '@heroicons/vue/24/solid';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    assignment: Object,
    offer: Object,
    lead: Object,
    business: Object,
});

const showCallbackModal = ref(false);
const showRejectModal = ref(false);
const callbackForm = ref({
    phone: '',
    preferred_time: '',
    message: '',
});
const rejectReason = ref('');
const isSubmitting = ref(false);
const successMessage = ref('');

// Countdown timer
const timeRemaining = ref(null);
const countdownInterval = ref(null);

const formatTimeRemaining = computed(() => {
    if (!timeRemaining.value || timeRemaining.value <= 0) return null;

    const days = Math.floor(timeRemaining.value / (1000 * 60 * 60 * 24));
    const hours = Math.floor((timeRemaining.value % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((timeRemaining.value % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((timeRemaining.value % (1000 * 60)) / 1000);

    if (days > 0) {
        return t('public_offer.time_days_hours', { days, hours });
    }
    if (hours > 0) {
        return t('public_offer.time_hours_minutes', { hours, minutes });
    }
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
});

onMounted(() => {
    if (props.assignment.expires_at?.timestamp) {
        updateCountdown();
        countdownInterval.value = setInterval(updateCountdown, 1000);
    }
});

onUnmounted(() => {
    if (countdownInterval.value) {
        clearInterval(countdownInterval.value);
    }
});

const updateCountdown = () => {
    const now = Date.now();
    const expires = props.assignment.expires_at.timestamp;
    timeRemaining.value = Math.max(0, expires - now);
};

const formatPrice = (price) => {
    if (!price) return '0';
    return new Intl.NumberFormat('uz-UZ').format(price);
};

const finalPrice = computed(() => {
    const base = props.assignment.offered_price || props.offer.pricing || 0;
    const discount = props.assignment.discount_amount || 0;
    return Math.max(0, base - discount);
});

const hasDiscount = computed(() => {
    return props.assignment.discount_amount && props.assignment.discount_amount > 0;
});

const recordClick = async (action) => {
    try {
        await axios.post(route('offers.public.click', props.assignment.tracking_code), { action });
    } catch (error) {
        console.error('Click tracking failed:', error);
    }
};

const showInterest = async () => {
    isSubmitting.value = true;
    try {
        const response = await axios.post(route('offers.public.interested', props.assignment.tracking_code));
        successMessage.value = response.data.message;
        await recordClick('interested');
    } catch (error) {
        console.error('Interest submission failed:', error);
    } finally {
        isSubmitting.value = false;
    }
};

const submitCallback = async () => {
    isSubmitting.value = true;
    try {
        const response = await axios.post(route('offers.public.callback', props.assignment.tracking_code), callbackForm.value);
        successMessage.value = response.data.message;
        showCallbackModal.value = false;
        await recordClick('callback');
    } catch (error) {
        console.error('Callback submission failed:', error);
    } finally {
        isSubmitting.value = false;
    }
};

const submitReject = async () => {
    isSubmitting.value = true;
    try {
        await axios.post(route('offers.public.reject', props.assignment.tracking_code), { reason: rejectReason.value });
        showRejectModal.value = false;
        successMessage.value = t('public_offer.feedback_thanks');
    } catch (error) {
        console.error('Reject submission failed:', error);
    } finally {
        isSubmitting.value = false;
    }
};

const guaranteeLabels = computed(() => ({
    unconditional: t('public_offer.guarantee_unconditional'),
    conditional: t('public_offer.guarantee_conditional'),
    performance: t('public_offer.guarantee_performance'),
    hybrid: t('public_offer.guarantee_hybrid'),
}));
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-indigo-50">
        <Head :title="offer.name" />

        <!-- Success Message -->
        <div v-if="successMessage" class="fixed top-4 left-1/2 -translate-x-1/2 z-50 px-6 py-4 bg-green-600 text-white rounded-xl shadow-lg flex items-center gap-3">
            <CheckCircleIcon class="w-6 h-6" />
            {{ successMessage }}
        </div>

        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 text-white">
            <div class="max-w-4xl mx-auto px-4 py-8 text-center">
                <p v-if="lead.name" class="text-purple-200 mb-2">{{ t('public_offer.greeting', { name: lead.name }) }}</p>
                <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <GiftIcon class="w-8 h-8" />
                </div>
                <h1 class="text-3xl md:text-4xl font-bold mb-4">{{ offer.name }}</h1>
                <p v-if="offer.description" class="text-lg text-purple-100 max-w-2xl mx-auto">
                    {{ offer.description }}
                </p>
            </div>
        </div>

        <!-- Countdown Timer -->
        <div v-if="formatTimeRemaining" class="bg-gradient-to-r from-red-500 to-orange-500 text-white py-3">
            <div class="max-w-4xl mx-auto px-4 flex items-center justify-center gap-3">
                <ClockIcon class="w-5 h-5 animate-pulse" />
                <span class="font-semibold">{{ t('public_offer.offer_deadline') }}</span>
                <span class="text-xl font-bold">{{ formatTimeRemaining }}</span>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 py-8">
            <!-- Value Proposition -->
            <div v-if="offer.value_proposition" class="bg-white rounded-2xl shadow-lg p-6 mb-6 border-l-4 border-purple-500">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <SparklesIcon class="w-6 h-6 text-purple-600" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">{{ t('public_offer.value_proposition_title') }}</h2>
                        <p class="text-gray-600 text-lg">{{ offer.value_proposition }}</p>
                    </div>
                </div>
            </div>

            <!-- Pricing Card -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-6">
                <div class="p-6 bg-gradient-to-r from-green-50 to-emerald-50 border-b border-green-100">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <p class="text-sm text-green-700 font-medium mb-1">{{ t('public_offer.special_price') }}</p>
                            <div class="flex items-baseline gap-3">
                                <span v-if="hasDiscount" class="text-2xl text-gray-400 line-through">
                                    {{ formatPrice(assignment.offered_price || offer.pricing) }} {{ t('common.currency') }}
                                </span>
                                <span class="text-4xl font-black text-green-700">
                                    {{ formatPrice(finalPrice) }} {{ t('common.currency') }}
                                </span>
                            </div>
                            <p v-if="hasDiscount" class="text-green-600 font-semibold mt-1">
                                {{ t('public_offer.discount_amount', { amount: formatPrice(assignment.discount_amount) }) }}
                            </p>
                        </div>

                        <div v-if="offer.total_value && offer.total_value > finalPrice" class="text-right">
                            <p class="text-sm text-gray-500">{{ t('public_offer.total_value') }}</p>
                            <p class="text-2xl font-bold text-gray-900">{{ formatPrice(offer.total_value) }} {{ t('common.currency') }}</p>
                            <p class="text-green-600 font-semibold">
                                {{ t('public_offer.more_value', { percent: Math.round((offer.total_value / finalPrice - 1) * 100) }) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Bonus Stack -->
                <div v-if="offer.components?.length > 0" class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <GiftIcon class="w-6 h-6 text-amber-500" />
                        {{ t('public_offer.bonuses') }}
                    </h3>
                    <div class="space-y-3">
                        <div
                            v-for="(component, index) in offer.components"
                            :key="component.id"
                            class="flex items-start gap-3 p-4 bg-amber-50 rounded-xl"
                        >
                            <div class="w-8 h-8 bg-amber-500 text-white rounded-lg flex items-center justify-center font-bold text-sm flex-shrink-0">
                                {{ index + 1 }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-bold text-gray-900">{{ component.name }}</h4>
                                    <span v-if="component.value" class="text-green-600 font-semibold">
                                        {{ formatPrice(component.value) }} {{ t('common.currency') }}
                                    </span>
                                </div>
                                <p v-if="component.description" class="text-gray-600 text-sm mt-1">
                                    {{ component.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guarantee -->
            <div v-if="offer.guarantee_type" class="bg-white rounded-2xl shadow-lg p-6 mb-6 border-2 border-green-200">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 bg-green-500 rounded-xl flex items-center justify-center flex-shrink-0">
                        <ShieldCheckIcon class="w-7 h-7 text-white" />
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-green-800 mb-2">
                            {{ guaranteeLabels[offer.guarantee_type] || offer.guarantee_type }}
                        </h3>
                        <p v-if="offer.guarantee_terms" class="text-gray-600 mb-3">
                            {{ offer.guarantee_terms }}
                        </p>
                        <div v-if="offer.guarantee_period_days" class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 text-green-700 rounded-lg">
                            <ClockIcon class="w-5 h-5" />
                            <span class="font-semibold">{{ t('public_offer.guarantee_days', { days: offer.guarantee_period_days }) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scarcity/Urgency -->
            <div v-if="offer.scarcity || offer.urgency" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div v-if="offer.scarcity" class="bg-blue-50 border-2 border-blue-200 rounded-xl p-4">
                    <div class="flex items-center gap-3 text-blue-700 font-semibold mb-2">
                        <ClockIcon class="w-5 h-5" />
                        {{ t('public_offer.scarcity') }}
                    </div>
                    <p class="text-gray-700">{{ offer.scarcity }}</p>
                </div>
                <div v-if="offer.urgency" class="bg-red-50 border-2 border-red-200 rounded-xl p-4">
                    <div class="flex items-center gap-3 text-red-700 font-semibold mb-2">
                        <FireIcon class="w-5 h-5" />
                        {{ t('public_offer.urgency') }}
                    </div>
                    <p class="text-gray-700">{{ offer.urgency }}</p>
                </div>
            </div>

            <!-- Value Score -->
            <div v-if="offer.value_score" class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-2xl p-6 mb-6 border-2 border-yellow-200">
                <div class="text-center">
                    <div class="flex items-center justify-center gap-3 mb-2">
                        <TrophyIcon class="w-8 h-8 text-yellow-600" />
                        <span class="text-3xl font-black text-yellow-800">{{ offer.value_score }}</span>
                    </div>
                    <p class="text-yellow-700 font-medium">{{ t('public_offer.value_score') }}</p>
                </div>
            </div>

            <!-- CTA Buttons -->
            <div class="sticky bottom-4 bg-white rounded-2xl shadow-2xl p-4 border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <button
                        @click="showInterest"
                        :disabled="isSubmitting"
                        class="flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold rounded-xl hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg disabled:opacity-50"
                    >
                        <CheckCircleIcon class="w-6 h-6" />
                        {{ t('public_offer.interested') }}
                    </button>

                    <button
                        @click="showCallbackModal = true"
                        class="flex items-center justify-center gap-2 px-6 py-4 bg-green-600 text-white font-bold rounded-xl hover:bg-green-700 transition-all shadow-lg"
                    >
                        <PhoneIcon class="w-6 h-6" />
                        {{ t('public_offer.call_me') }}
                    </button>
                </div>

                <button
                    @click="showRejectModal = true"
                    class="w-full mt-3 text-sm text-gray-400 hover:text-gray-600 transition-colors"
                >
                    {{ t('public_offer.not_interested') }}
                </button>
            </div>

            <!-- Business Info -->
            <div class="text-center mt-8 text-gray-500 text-sm">
                <p>{{ business.name }}</p>
                <p v-if="business.phone">{{ business.phone }}</p>
            </div>
        </div>

        <!-- Callback Modal -->
        <Teleport to="body">
            <div v-if="showCallbackModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showCallbackModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-gray-900">{{ t('public_offer.callback_title') }}</h3>
                            <button @click="showCallbackModal = false" class="text-gray-400 hover:text-gray-600">
                                <XMarkIcon class="w-6 h-6" />
                            </button>
                        </div>

                        <form @submit.prevent="submitCallback" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('public_offer.phone_number') }} *</label>
                                <input
                                    v-model="callbackForm.phone"
                                    type="tel"
                                    required
                                    placeholder="+998 90 123 45 67"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('public_offer.preferred_time') }}</label>
                                <select
                                    v-model="callbackForm.preferred_time"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                >
                                    <option value="">{{ t('public_offer.time_any') }}</option>
                                    <option value="morning">{{ t('public_offer.time_morning') }}</option>
                                    <option value="afternoon">{{ t('public_offer.time_afternoon') }}</option>
                                    <option value="evening">{{ t('public_offer.time_evening') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('public_offer.message') }}</label>
                                <textarea
                                    v-model="callbackForm.message"
                                    rows="2"
                                    :placeholder="t('public_offer.message_placeholder')"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                ></textarea>
                            </div>
                            <button
                                type="submit"
                                :disabled="isSubmitting"
                                class="w-full py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-all disabled:opacity-50"
                            >
                                {{ isSubmitting ? t('public_form.submitting') : t('public_form.submit') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Reject Modal -->
        <Teleport to="body">
            <div v-if="showRejectModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showRejectModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-gray-900">{{ t('public_offer.feedback_title') }}</h3>
                            <button @click="showRejectModal = false" class="text-gray-400 hover:text-gray-600">
                                <XMarkIcon class="w-6 h-6" />
                            </button>
                        </div>

                        <form @submit.prevent="submitReject" class="space-y-4">
                            <p class="text-gray-600">{{ t('public_offer.feedback_question') }}</p>
                            <select
                                v-model="rejectReason"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            >
                                <option value="">{{ t('public_offer.select_reason') }}</option>
                                <option value="price">{{ t('public_offer.reason_price') }}</option>
                                <option value="no_need">{{ t('public_offer.reason_no_need') }}</option>
                                <option value="no_budget">{{ t('public_offer.reason_no_budget') }}</option>
                                <option value="competitor">{{ t('public_offer.reason_competitor') }}</option>
                                <option value="other">{{ t('public_offer.reason_other') }}</option>
                            </select>
                            <button
                                type="submit"
                                :disabled="isSubmitting"
                                class="w-full py-3 bg-gray-600 text-white font-semibold rounded-xl hover:bg-gray-700 transition-all disabled:opacity-50"
                            >
                                {{ isSubmitting ? t('public_form.submitting') : t('public_form.submit') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
