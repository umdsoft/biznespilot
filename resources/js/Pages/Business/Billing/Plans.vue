<template>
  <BusinessLayout title="Tarif va To'lov">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
          Tarif rejangizni tanlang
        </h1>
        <p class="text-gray-600 dark:text-gray-400 text-lg">
          Biznesingiz uchun to'g'ri tarifni tanlang. Istalgan vaqt o'zgartirish mumkin.
        </p>
      </div>

      <!-- Billing Toggle -->
      <div class="flex justify-center mb-8">
        <div class="inline-flex items-center p-1.5 bg-gray-100 dark:bg-gray-700 rounded-2xl">
          <button
            @click="isYearly = false"
            class="relative px-6 py-2.5 text-sm font-semibold rounded-xl transition-all duration-300"
            :class="!isYearly ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 shadow-lg' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
          >
            Oylik to'lov
          </button>
          <button
            @click="isYearly = true"
            class="relative px-6 py-2.5 text-sm font-semibold rounded-xl transition-all duration-300 flex items-center gap-2"
            :class="isYearly ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 shadow-lg' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
          >
            Yillik to'lov
            <span class="px-2 py-0.5 bg-gradient-to-r from-green-500 to-emerald-500 text-white text-xs font-bold rounded-full">
              -20%
            </span>
          </button>
        </div>
      </div>

      <!-- Current Subscription Info -->
      <div v-if="subscriptionStatus?.has_subscription" class="max-w-3xl mx-auto mb-8">
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-800 flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="font-semibold text-blue-900 dark:text-blue-100">
                Joriy tarif: {{ subscriptionStatus.plan?.name }}
              </p>
              <p class="text-sm text-blue-700 dark:text-blue-300">
                {{ subscriptionStatus.days_remaining }} kun qoldi
                <span v-if="subscriptionStatus.billing_cycle === 'yearly'" class="ml-2 px-2 py-0.5 bg-blue-200 dark:bg-blue-700 rounded text-xs">Yillik</span>
                <span v-else class="ml-2 px-2 py-0.5 bg-blue-200 dark:bg-blue-700 rounded text-xs">Oylik</span>
              </p>
            </div>
          </div>
          <a href="/business/billing/history" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
            To'lov tarixi &rarr;
          </a>
        </div>
      </div>

      <!-- Plans Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 max-w-7xl mx-auto">
        <div
          v-for="plan in plans"
          :key="plan.id"
          class="relative bg-white dark:bg-gray-800 rounded-2xl border-2 transition-all duration-300 flex flex-col"
          :class="plan.is_current
            ? 'border-blue-500 dark:border-blue-400 shadow-lg shadow-blue-500/10'
            : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-lg'"
        >
          <!-- Current Badge -->
          <div v-if="plan.is_current" class="absolute -top-3 left-1/2 -translate-x-1/2">
            <span class="px-4 py-1 bg-blue-600 text-white text-xs font-bold rounded-full shadow">
              Joriy tarif
            </span>
          </div>

          <div class="p-6 flex-1 flex flex-col">
            <!-- Plan Name -->
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">{{ plan.name }}</h3>
            <p v-if="plan.description" class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ plan.description }}</p>

            <!-- Price -->
            <div class="mb-6">
              <div class="flex items-baseline gap-1">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">
                  {{ formatPrice(isYearly ? plan.price_yearly : plan.price_monthly) }}
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                  so'm/{{ isYearly ? 'yil' : 'oy' }}
                </span>
              </div>
              <p v-if="isYearly && plan.price_monthly > 0" class="text-sm text-green-600 dark:text-green-400 mt-1">
                {{ formatPrice(Math.round(plan.price_yearly / 12)) }} so'm/oy
              </p>
            </div>

            <!-- Features -->
            <div class="space-y-2.5 flex-1 mb-6">
              <div v-for="(value, key) in plan.limits" :key="key" class="flex items-center gap-2 text-sm">
                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-gray-700 dark:text-gray-300">
                  {{ formatLimitLabel(key) }}: {{ value === -1 ? 'Cheksiz' : value }}
                </span>
              </div>
              <div v-for="(enabled, key) in plan.features" :key="key" class="flex items-center gap-2 text-sm">
                <svg v-if="enabled" class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else class="w-4 h-4 text-gray-300 dark:text-gray-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span :class="enabled ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 dark:text-gray-500'">
                  {{ formatFeatureLabel(key) }}
                </span>
              </div>
            </div>

            <!-- Action Button -->
            <div class="mt-auto">
              <button
                v-if="plan.is_current"
                disabled
                class="w-full py-3 px-4 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-semibold rounded-xl cursor-not-allowed"
              >
                Joriy tarif
              </button>
              <button
                v-else-if="plan.price_monthly > 0 || plan.price_yearly > 0"
                @click="openCheckoutModal(plan)"
                class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg hover:shadow-xl"
              >
                {{ plan.is_upgrade ? 'Upgrade' : 'Sotib olish' }}
              </button>
              <button
                v-else
                disabled
                class="w-full py-3 px-4 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-semibold rounded-xl cursor-not-allowed"
              >
                Bepul
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Checkout Modal -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
          <!-- Modal Header -->
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">To'lovni tasdiqlash</h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Plan Summary -->
          <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 mb-6">
            <div class="flex justify-between items-center mb-2">
              <span class="text-gray-600 dark:text-gray-400">Tarif:</span>
              <span class="font-semibold text-gray-900 dark:text-gray-100">{{ selectedPlan?.name }}</span>
            </div>
            <div class="flex justify-between items-center mb-2">
              <span class="text-gray-600 dark:text-gray-400">Davr:</span>
              <span class="font-semibold text-gray-900 dark:text-gray-100">{{ isYearly ? 'Yillik' : 'Oylik' }}</span>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-600 pt-2 mt-2 flex justify-between items-center">
              <span class="text-gray-900 dark:text-gray-100 font-bold">Jami:</span>
              <span class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">
                {{ formatPrice(isYearly ? selectedPlan?.price_yearly : selectedPlan?.price_monthly) }} so'm
              </span>
            </div>
          </div>

          <!-- Provider Selection -->
          <div class="space-y-3 mb-6">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">To'lov usuli</label>
            <div class="grid grid-cols-2 gap-3">
              <button
                @click="selectedProvider = 'click'"
                class="flex items-center justify-center gap-2 p-4 rounded-xl border-2 transition-all"
                :class="selectedProvider === 'click'
                  ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-400'
                  : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'"
              >
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                  <span class="text-white font-bold text-xs">Click</span>
                </div>
                <span class="font-semibold text-gray-900 dark:text-gray-100">Click</span>
              </button>
              <button
                @click="selectedProvider = 'payme'"
                class="flex items-center justify-center gap-2 p-4 rounded-xl border-2 transition-all"
                :class="selectedProvider === 'payme'
                  ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-400'
                  : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'"
              >
                <div class="w-8 h-8 bg-sky-500 rounded-lg flex items-center justify-center">
                  <span class="text-white font-bold text-xs">Payme</span>
                </div>
                <span class="font-semibold text-gray-900 dark:text-gray-100">Payme</span>
              </button>
            </div>
          </div>

          <!-- Submit Button -->
          <button
            @click="submitCheckout"
            :disabled="checkoutForm.processing"
            class="w-full py-3.5 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <svg v-if="checkoutForm.processing" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            {{ checkoutForm.processing ? 'Kutilmoqda...' : 'To\'lovga o\'tish' }}
          </button>

          <p class="text-xs text-gray-500 dark:text-gray-400 text-center mt-3">
            To'lov xavfsiz {{ selectedProvider === 'click' ? 'Click' : 'Payme' }} tizimi orqali amalga oshiriladi
          </p>
        </div>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';

const props = defineProps({
  plans: { type: Array, default: () => [] },
  subscriptionStatus: { type: Object, default: () => null },
});

const isYearly = ref(false);
const showModal = ref(false);
const selectedPlan = ref(null);
const selectedProvider = ref('click');

const checkoutForm = useForm({
  plan_id: null,
  billing_cycle: 'monthly',
  provider: 'click',
});

const formatPrice = (price) => {
  if (!price) return '0';
  return new Intl.NumberFormat('uz-UZ').format(price);
};

const limitLabels = {
  businesses: 'Bizneslar',
  team_members: 'Jamoa a\'zolari',
  leads: 'Lidlar',
  chatbot_channels: 'Chatbot kanallari',
  telegram_bots: 'Telegram botlar',
  audio_minutes: 'Audio daqiqalar',
  ai_requests: 'AI so\'rovlar',
  storage_mb: 'Xotira (MB)',
  instagram_dm: 'Instagram DM',
  content_posts: 'Kontent postlar',
};

const featureLabels = {
  has_instagram: 'Instagram integratsiya',
  has_amocrm: 'AmoCRM integratsiya',
  has_telegram: 'Telegram integratsiya',
  has_analytics: 'Kengaytirilgan analitika',
  has_ai: 'AI tahlil',
  has_reports: 'Hisobotlar',
  has_api: 'API kirish',
};

const formatLimitLabel = (key) => limitLabels[key] || key;
const formatFeatureLabel = (key) => featureLabels[key] || key;

const openCheckoutModal = (plan) => {
  selectedPlan.value = plan;
  showModal.value = true;
};

const submitCheckout = () => {
  checkoutForm.plan_id = selectedPlan.value.id;
  checkoutForm.billing_cycle = isYearly.value ? 'yearly' : 'monthly';
  checkoutForm.provider = selectedProvider.value;
  checkoutForm.post('/business/billing/checkout');
};
</script>
