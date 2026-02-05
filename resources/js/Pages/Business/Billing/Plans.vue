<template>
  <BusinessLayout title="Tarif va To'lov">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

      <!-- Header -->
      <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Tarif va To'lov</h1>
          <p class="text-gray-500 dark:text-gray-400 mt-1">
            Biznesingizga mos tarifni tanlang. Istalgan vaqt o'zgartirish mumkin.
          </p>
        </div>
        <a
          href="/business/billing/history"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          To'lov tarixi
        </a>
      </div>

      <!-- Joriy obuna info -->
      <div v-if="subscriptionStatus?.has_subscription" class="mb-6 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
        <div class="flex items-center justify-between flex-wrap gap-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center"
              :class="subscriptionStatus.is_trial
                ? 'bg-blue-100 dark:bg-blue-900/30'
                : 'bg-green-100 dark:bg-green-900/30'"
            >
              <svg class="w-5 h-5" :class="subscriptionStatus.is_trial ? 'text-blue-600 dark:text-blue-400' : 'text-green-600 dark:text-green-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                Joriy tarif: {{ subscriptionStatus.plan?.name || 'Trial' }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ subscriptionStatus.days_remaining }} kun qoldi
              </p>
            </div>
          </div>
          <span
            :class="[
              'px-3 py-1 rounded-full text-xs font-semibold',
              subscriptionStatus.is_trial
                ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
            ]"
          >
            {{ subscriptionStatus.is_trial ? 'Sinov davri' : 'Faol' }}
          </span>
        </div>
      </div>

      <!-- Oylik / Yillik toggle -->
      <div class="flex justify-center mb-6">
        <div class="inline-flex items-center p-1 bg-gray-100 dark:bg-gray-800 rounded-xl">
          <button
            @click="isYearly = false"
            :class="[
              'px-6 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200',
              !isYearly
                ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
            ]"
          >
            Oylik
          </button>
          <button
            @click="isYearly = true"
            :class="[
              'px-6 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 flex items-center gap-2',
              isYearly
                ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
            ]"
          >
            Yillik
            <span class="px-2 py-0.5 bg-green-500 text-white text-xs font-bold rounded-full">-20%</span>
          </button>
        </div>
      </div>

      <!-- Comparison Table -->
      <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
        <table class="w-full text-sm">
          <!-- Header: Plan names + prices + CTA -->
          <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700">
              <th class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-900/60 backdrop-blur-sm px-5 py-5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider min-w-[190px]">
                Imkoniyatlar
              </th>
              <th
                v-for="plan in plans"
                :key="plan.id"
                :class="[
                  'px-4 py-5 text-center min-w-[150px]',
                  plan.is_current
                    ? 'bg-blue-50/60 dark:bg-blue-900/15'
                    : plan.slug === 'business' ? 'bg-purple-50/40 dark:bg-purple-900/10' : 'bg-gray-50 dark:bg-gray-900/60'
                ]"
              >
                <div class="space-y-2">
                  <!-- Badge -->
                  <div class="h-5 flex justify-center">
                    <span
                      v-if="plan.is_current"
                      class="px-2.5 py-0.5 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-[10px] font-bold rounded-full uppercase"
                    >Joriy tarif</span>
                    <span
                      v-else-if="plan.slug === 'business'"
                      class="px-2.5 py-0.5 bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 text-[10px] font-bold rounded-full uppercase"
                    >Mashhur</span>
                  </div>
                  <!-- Name -->
                  <div class="font-bold text-gray-900 dark:text-gray-100 text-base">{{ plan.name }}</div>
                  <!-- Price -->
                  <div>
                    <span class="text-xl font-extrabold text-gray-900 dark:text-gray-100">
                      {{ formatPrice(getPlanPrice(plan)) }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 block mt-0.5">so'm/oy</span>
                  </div>
                  <div v-if="isYearly && plan.price_yearly" class="text-[11px] text-green-600 dark:text-green-400 font-medium">
                    {{ formatPrice(plan.price_yearly) }} so'm/yil
                  </div>
                  <!-- CTA button -->
                  <div class="pt-1">
                    <button
                      v-if="!plan.is_current && (plan.price_monthly > 0 || plan.price_yearly > 0)"
                      @click="openCheckoutModal(plan)"
                      :class="[
                        'w-full py-2 px-3 rounded-lg font-semibold text-xs transition-all',
                        plan.slug === 'business'
                          ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white hover:from-purple-700 hover:to-indigo-700 shadow-sm'
                          : 'bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200'
                      ]"
                    >
                      {{ plan.is_upgrade ? 'Upgrade' : 'Sotib olish' }}
                    </button>
                    <div
                      v-else-if="plan.is_current"
                      class="w-full py-2 px-3 rounded-lg text-xs font-semibold text-center text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800"
                    >
                      Hozirgi tarif
                    </div>
                  </div>
                </div>
              </th>
            </tr>
          </thead>

          <tbody>
            <!-- ==================== Section 1: Asosiy limitlar ==================== -->
            <tr class="bg-gray-50/80 dark:bg-gray-700/30">
              <td :colspan="plans.length + 1" class="px-5 py-2.5 text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                Asosiy limitlar
              </td>
            </tr>
            <tr
              v-for="(row, idx) in basicLimitRows"
              :key="'bl-' + idx"
              class="border-b border-gray-100 dark:border-gray-700/40 hover:bg-gray-50/50 dark:hover:bg-gray-700/15 transition-colors"
            >
              <td class="sticky left-0 z-10 bg-white dark:bg-gray-800 px-5 py-3 text-gray-700 dark:text-gray-300 font-medium">
                {{ row.label }}
              </td>
              <td
                v-for="plan in plans"
                :key="plan.id"
                :class="cellClass(plan)"
              >
                <LimitValue :value="row.getValue(plan)" :format="row.format" :suffix="row.suffix" />
              </td>
            </tr>

            <!-- ==================== Section 2: Bot va kanallar ==================== -->
            <tr class="bg-gray-50/80 dark:bg-gray-700/30">
              <td :colspan="plans.length + 1" class="px-5 py-2.5 text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                Bot va kanallar
              </td>
            </tr>
            <tr
              v-for="(row, idx) in botChannelRows"
              :key="'bc-' + idx"
              class="border-b border-gray-100 dark:border-gray-700/40 hover:bg-gray-50/50 dark:hover:bg-gray-700/15 transition-colors"
            >
              <td class="sticky left-0 z-10 bg-white dark:bg-gray-800 px-5 py-3 text-gray-700 dark:text-gray-300 font-medium">
                {{ row.label }}
              </td>
              <td
                v-for="plan in plans"
                :key="plan.id"
                :class="cellClass(plan)"
              >
                <LimitValue :value="row.getValue(plan)" :format="row.format" :suffix="row.suffix" />
              </td>
            </tr>

            <!-- ==================== Section 3: AI imkoniyatlari ==================== -->
            <tr class="bg-gray-50/80 dark:bg-gray-700/30">
              <td :colspan="plans.length + 1" class="px-5 py-2.5 text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                AI imkoniyatlari
              </td>
            </tr>
            <tr
              v-for="(row, idx) in aiRows"
              :key="'ai-' + idx"
              class="border-b border-gray-100 dark:border-gray-700/40 hover:bg-gray-50/50 dark:hover:bg-gray-700/15 transition-colors"
            >
              <td class="sticky left-0 z-10 bg-white dark:bg-gray-800 px-5 py-3 text-gray-700 dark:text-gray-300 font-medium">
                {{ row.label }}
              </td>
              <td
                v-for="plan in plans"
                :key="plan.id"
                :class="cellClass(plan)"
              >
                <LimitValue :value="row.getValue(plan)" :format="row.format" :suffix="row.suffix" />
              </td>
            </tr>

            <!-- ==================== Section 4: Qo'shimcha funksiyalar ==================== -->
            <tr class="bg-gray-50/80 dark:bg-gray-700/30">
              <td :colspan="plans.length + 1" class="px-5 py-2.5 text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                Qo'shimcha funksiyalar
              </td>
            </tr>
            <tr
              v-for="(row, idx) in featureRows"
              :key="'f-' + idx"
              class="border-b border-gray-100 dark:border-gray-700/40 hover:bg-gray-50/50 dark:hover:bg-gray-700/15 transition-colors"
            >
              <td class="sticky left-0 z-10 bg-white dark:bg-gray-800 px-5 py-3 text-gray-700 dark:text-gray-300 font-medium">
                {{ row.label }}
              </td>
              <td
                v-for="plan in plans"
                :key="plan.id"
                :class="cellClass(plan)"
              >
                <BooleanValue :value="row.getValue(plan)" />
              </td>
            </tr>

            <!-- ==================== Section 5: Barcha tariflarda mavjud ==================== -->
            <tr class="bg-gray-50/80 dark:bg-gray-700/30">
              <td :colspan="plans.length + 1" class="px-5 py-2.5 text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                Barcha tariflarda mavjud
              </td>
            </tr>
            <tr
              v-for="(row, idx) in includedInAllRows"
              :key="'ia-' + idx"
              class="border-b border-gray-100 dark:border-gray-700/40 hover:bg-gray-50/50 dark:hover:bg-gray-700/15 transition-colors"
            >
              <td class="sticky left-0 z-10 bg-white dark:bg-gray-800 px-5 py-3 text-gray-700 dark:text-gray-300 font-medium">
                {{ row.label }}
              </td>
              <td
                v-for="plan in plans"
                :key="plan.id"
                :class="cellClass(plan)"
              >
                <BooleanValue :value="true" />
              </td>
            </tr>

            <!-- ==================== Section 6: Texnik yordam ==================== -->
            <tr class="bg-gray-50/80 dark:bg-gray-700/30">
              <td :colspan="plans.length + 1" class="px-5 py-2.5 text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                Texnik yordam
              </td>
            </tr>
            <tr
              v-for="(row, idx) in supportRows"
              :key="'s-' + idx"
              class="border-b border-gray-100 dark:border-gray-700/40 hover:bg-gray-50/50 dark:hover:bg-gray-700/15 transition-colors"
            >
              <td class="sticky left-0 z-10 bg-white dark:bg-gray-800 px-5 py-3 text-gray-700 dark:text-gray-300 font-medium">
                {{ row.label }}
              </td>
              <td
                v-for="plan in plans"
                :key="plan.id"
                :class="cellClass(plan)"
              >
                <template v-if="typeof row.getValue(plan) === 'boolean'">
                  <BooleanValue :value="row.getValue(plan)" />
                </template>
                <template v-else>
                  <span class="font-semibold text-gray-900 dark:text-gray-100">{{ row.getValue(plan) }}</span>
                </template>
              </td>
            </tr>

            <!-- Bottom CTA row -->
            <tr class="bg-gray-50/50 dark:bg-gray-700/10">
              <td class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-800 px-5 py-4"></td>
              <td
                v-for="plan in plans"
                :key="'b-' + plan.id"
                :class="[
                  'px-4 py-4 text-center',
                  plan.is_current ? 'bg-blue-50/30 dark:bg-blue-900/5' : ''
                ]"
              >
                <button
                  v-if="!plan.is_current && (plan.price_monthly > 0 || plan.price_yearly > 0)"
                  @click="openCheckoutModal(plan)"
                  :class="[
                    'w-full py-2.5 px-4 rounded-lg font-semibold text-sm transition-all',
                    plan.slug === 'business'
                      ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white hover:from-purple-700 hover:to-indigo-700 shadow-sm'
                      : 'bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200'
                  ]"
                >
                  {{ plan.is_upgrade ? 'Upgrade' : 'Sotib olish' }}
                </button>
                <div v-else-if="plan.is_current" class="text-sm font-semibold text-green-700 dark:text-green-400">
                  Hozirgi tarif
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Trust badges -->
      <div class="mt-8 text-center">
        <div class="flex flex-wrap items-center justify-center gap-6 text-sm text-gray-500 dark:text-gray-400">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>30 kunlik kafolat</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span>Xavfsiz to'lov</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            <span>Click orqali to'lov</span>
          </div>
        </div>
      </div>

      <!-- Checkout Modal -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6 z-10">
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">To'lovni tasdiqlash</h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Plan summary -->
          <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 mb-6">
            <div class="flex justify-between items-center mb-2">
              <span class="text-gray-500 dark:text-gray-400 text-sm">Tarif:</span>
              <span class="font-semibold text-gray-900 dark:text-gray-100">{{ selectedPlan?.name }}</span>
            </div>
            <div class="flex justify-between items-center mb-2">
              <span class="text-gray-500 dark:text-gray-400 text-sm">Davr:</span>
              <span class="font-semibold text-gray-900 dark:text-gray-100">{{ isYearly ? 'Yillik' : 'Oylik' }}</span>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-600 pt-3 mt-3 flex justify-between items-center">
              <span class="text-gray-900 dark:text-gray-100 font-bold">Jami:</span>
              <span class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">
                {{ formatPrice(isYearly ? selectedPlan?.price_yearly : selectedPlan?.price_monthly) }}
                <span class="text-sm font-normal text-gray-500">so'm</span>
              </span>
            </div>
          </div>

          <!-- To'lov tugmasi -->
          <button
            @click="submitCheckout"
            :disabled="checkoutForm.processing"
            class="w-full py-3.5 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <svg v-if="checkoutForm.processing" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div v-else class="w-6 h-6 bg-white/20 rounded flex items-center justify-center">
              <span class="text-white font-bold text-[10px]">Click</span>
            </div>
            {{ checkoutForm.processing ? 'Kutilmoqda...' : "Click orqali to'lash" }}
          </button>

          <p class="text-xs text-gray-400 text-center mt-3">
            To'lov xavfsiz Click tizimi orqali amalga oshiriladi
          </p>
        </div>
      </div>

    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, h } from 'vue';
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

// ============================================================
// HELPERS
// ============================================================

const getPlanPrice = (plan) => {
  if (isYearly.value && plan.price_yearly) {
    return Math.round(plan.price_yearly / 12);
  }
  return plan.price_monthly;
};

const formatPrice = (price) => {
  if (!price) return '0';
  return new Intl.NumberFormat('uz-UZ').format(Math.round(price));
};

const formatNumber = (val) => {
  if (typeof val === 'number') return val.toLocaleString('uz-UZ');
  return val;
};

const formatStorage = (mb) => {
  if (mb === null) return null;
  if (mb >= 1000) return (mb / 1000) + ' GB';
  return mb + ' MB';
};

const cellClass = (plan) => [
  'px-4 py-3 text-center',
  plan.is_current ? 'bg-blue-50/30 dark:bg-blue-900/5' : '',
];

// ============================================================
// INLINE COMPONENTS
// ============================================================

const LimitValue = {
  props: {
    value: [Number, String, null],
    format: Function,
    suffix: String,
  },
  setup(props) {
    return () => {
      if (props.value === null || props.value === undefined) {
        return h('span', { class: 'font-semibold text-blue-600 dark:text-blue-400' }, 'Cheksiz');
      }
      if (props.format) {
        return h('span', { class: 'font-semibold text-gray-900 dark:text-gray-100' }, props.format(props.value));
      }
      const text = typeof props.value === 'number' ? props.value.toLocaleString('uz-UZ') : props.value;
      const children = [h('span', { class: 'font-semibold text-gray-900 dark:text-gray-100' }, text)];
      if (props.suffix) {
        children.push(h('span', { class: 'text-gray-400 dark:text-gray-500 text-xs ml-0.5' }, props.suffix));
      }
      return h('span', children);
    };
  },
};

const BooleanValue = {
  props: {
    value: Boolean,
  },
  setup(props) {
    return () => {
      if (props.value) {
        return h('svg', {
          class: 'w-5 h-5 mx-auto text-green-500',
          fill: 'currentColor',
          viewBox: '0 0 20 20',
        }, [
          h('path', {
            'fill-rule': 'evenodd',
            d: 'M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z',
            'clip-rule': 'evenodd',
          }),
        ]);
      }
      return h('svg', {
        class: 'w-5 h-5 mx-auto text-gray-300 dark:text-gray-600',
        fill: 'currentColor',
        viewBox: '0 0 20 20',
      }, [
        h('path', {
          'fill-rule': 'evenodd',
          d: 'M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z',
          'clip-rule': 'evenodd',
        }),
      ]);
    };
  },
};

// ============================================================
// SECTION 1: Asosiy limitlar (from DB limits)
// ============================================================
const basicLimitRows = [
  {
    label: 'Foydalanuvchilar soni',
    getValue: (plan) => plan.limits?.users ?? null,
  },
  {
    label: 'Filiallar soni',
    getValue: (plan) => plan.limits?.branches ?? null,
  },
  {
    label: 'Oylik lidlar',
    getValue: (plan) => plan.limits?.monthly_leads ?? null,
  },
  {
    label: 'Saqlash hajmi',
    getValue: (plan) => plan.limits?.storage_mb ?? null,
    format: formatStorage,
  },
];

// ============================================================
// SECTION 2: Bot va kanallar (from DB limits)
// ============================================================
const botChannelRows = [
  {
    label: 'Instagram akkauntlar',
    getValue: (plan) => plan.limits?.instagram_accounts ?? null,
  },
  {
    label: 'Chatbot kanallari',
    getValue: (plan) => plan.limits?.chatbot_channels ?? null,
  },
  {
    label: 'Telegram botlar',
    getValue: (plan) => plan.limits?.telegram_bots ?? null,
  },
];

// ============================================================
// SECTION 3: AI imkoniyatlari (from DB limits)
// ============================================================
const aiRows = [
  {
    label: "Qo'ng'iroqlar AI tahlili",
    getValue: (plan) => plan.limits?.ai_call_minutes ?? null,
    suffix: 'daq',
  },
  {
    label: "Qo'shimcha daqiqa narxi",
    getValue: (plan) => plan.limits?.extra_call_price ?? null,
    format: (val) => val === 0 ? 'Bepul' : formatPrice(val) + " so'm",
  },
  {
    label: "AI so'rovlar",
    getValue: (plan) => plan.limits?.ai_requests ?? null,
  },
];

// ============================================================
// SECTION 4: Qo'shimcha funksiyalar (from DB features - boolean)
// ============================================================
const featureRows = [
  {
    label: 'HR vazifalar',
    getValue: (plan) => plan.features?.hr_tasks ?? false,
  },
  {
    label: 'Ishga olish boti (HR Bot)',
    getValue: (plan) => plan.features?.hr_bot ?? false,
  },
  {
    label: 'SMS ogohlantirish (Anti-fraud)',
    getValue: (plan) => plan.features?.anti_fraud ?? false,
  },
];

// ============================================================
// SECTION 5: Barcha tariflarda mavjud (hardcoded - all true)
// ============================================================
const includedInAllRows = [
  { label: 'Instagram/Facebook integratsiya' },
  { label: 'Vizual voronka (Flow Builder)' },
  { label: 'Marketing ROI hisoboti' },
  { label: 'CRM va Lidlar boshqaruvi' },
  { label: 'Kanban doska' },
];

// ============================================================
// SECTION 6: Texnik yordam (hardcoded by plan slug)
// ============================================================
const supportDataBySlug = {
  start: { response: '24 soat', onboarding: false, personal_manager: false },
  standard: { response: '12 soat', onboarding: true, personal_manager: false },
  business: { response: '8 soat', onboarding: true, personal_manager: false },
  premium: { response: '2 soat', onboarding: 'Shaxsiy', personal_manager: true },
  enterprise: { response: '1 soat', onboarding: 'Shaxsiy', personal_manager: true },
};

const supportRows = [
  {
    label: 'Telegram support',
    getValue: () => true,
  },
  {
    label: 'Javob vaqti',
    getValue: (plan) => supportDataBySlug[plan.slug]?.response || '24 soat',
  },
  {
    label: "Video qo'llanmalar",
    getValue: () => true,
  },
  {
    label: 'Onboarding yordam',
    getValue: (plan) => {
      const val = supportDataBySlug[plan.slug]?.onboarding;
      if (typeof val === 'string') return val;
      return val ?? false;
    },
  },
  {
    label: 'Shaxsiy menejer',
    getValue: (plan) => supportDataBySlug[plan.slug]?.personal_manager ?? false,
  },
];

// ============================================================
// MODAL
// ============================================================
const openCheckoutModal = (plan) => {
  selectedPlan.value = plan;
  selectedProvider.value = 'click';
  showModal.value = true;
};

const submitCheckout = () => {
  checkoutForm.plan_id = selectedPlan.value.id;
  checkoutForm.billing_cycle = isYearly.value ? 'yearly' : 'monthly';
  checkoutForm.provider = selectedProvider.value;
  checkoutForm.post('/business/billing/checkout');
};
</script>
